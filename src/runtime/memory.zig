const std = @import("std");
const Allocator = std.mem.Allocator;

// php's view of a VM's memory: the bytes its allocator hands out, counted
// from a baseline (what the VM holds before a script or request runs), and
// memory_limit enforced on that count. an allocation that would cross the
// limit fails, and so does every one after it until the VM reports the
// exhaustion, so a swallowed failure cannot let a script run on unlimited
// the allocator under every account: memory that outlives the VM that
// allocated it, like values in flight between threads, comes from here
pub fn root(allocator: Allocator) Allocator {
    var current = allocator;
    while (current.vtable == &MemoryAccount.vtable) {
        const account: *MemoryAccount = @ptrCast(@alignCast(current.ptr));
        current = account.parent;
    }
    return current;
}

pub const MemoryAccount = struct {
    parent: Allocator,
    // frees can come from other threads when values cross into worker pools
    used: std.atomic.Value(usize) = .init(0),
    peak: std.atomic.Value(usize) = .init(0),
    baseline: usize = 0,
    // bytes allowed above the baseline; 0 is unlimited
    limit: usize = 0,
    // the size of the allocation the limit refused
    refused: ?usize = null,
    // set while the VM reports the exhaustion, which needs memory of its own
    reporting: bool = false,

    const vtable: Allocator.VTable = .{
        .alloc = alloc,
        .resize = resize,
        .remap = remap,
        .free = free,
    };

    pub fn allocator(self: *MemoryAccount) Allocator {
        return .{ .ptr = self, .vtable = &vtable };
    }

    // bytes in use above the baseline
    pub fn usage(self: *const MemoryAccount) usize {
        return self.used.load(.monotonic) -| self.baseline;
    }

    pub fn peakUsage(self: *const MemoryAccount) usize {
        return self.peak.load(.monotonic) -| self.baseline;
    }

    pub fn resetPeak(self: *MemoryAccount) void {
        self.peak.store(self.used.load(.monotonic), .monotonic);
    }

    // start counting from what is held now, as a fresh request does
    pub fn rebase(self: *MemoryAccount) void {
        self.baseline = self.used.load(.monotonic);
        self.resetPeak();
        self.refused = null;
        self.reporting = false;
    }

    // memory the VM commits itself rather than through the allocator (its
    // call stack regions); false when memory_limit refuses it
    pub fn chargeExternal(self: *MemoryAccount, len: usize) bool {
        if (!self.admit(len)) return false;
        self.charge(len);
        return true;
    }

    fn admit(self: *MemoryAccount, len: usize) bool {
        if (self.limit == 0 or self.reporting) return true;
        if (self.refused == null and self.usage() + len <= self.limit) return true;
        if (self.refused == null) self.refused = len;
        return false;
    }

    fn charge(self: *MemoryAccount, len: usize) void {
        const now = self.used.fetchAdd(len, .monotonic) + len;
        _ = self.peak.fetchMax(now, .monotonic);
    }

    fn discharge(self: *MemoryAccount, len: usize) void {
        var current = self.used.load(.monotonic);
        while (self.used.cmpxchgWeak(current, current -| len, .monotonic, .monotonic)) |seen| current = seen;
    }

    fn alloc(ctx: *anyopaque, len: usize, alignment: std.mem.Alignment, ret_addr: usize) ?[*]u8 {
        const self: *MemoryAccount = @ptrCast(@alignCast(ctx));
        if (!self.admit(len)) return null;
        const ptr = self.parent.rawAlloc(len, alignment, ret_addr) orelse return null;
        self.charge(len);
        return ptr;
    }

    fn resize(ctx: *anyopaque, memory: []u8, alignment: std.mem.Alignment, new_len: usize, ret_addr: usize) bool {
        const self: *MemoryAccount = @ptrCast(@alignCast(ctx));
        if (new_len > memory.len and !self.admit(new_len - memory.len)) return false;
        if (!self.parent.rawResize(memory, alignment, new_len, ret_addr)) return false;
        self.adjust(memory.len, new_len);
        return true;
    }

    fn remap(ctx: *anyopaque, memory: []u8, alignment: std.mem.Alignment, new_len: usize, ret_addr: usize) ?[*]u8 {
        const self: *MemoryAccount = @ptrCast(@alignCast(ctx));
        if (new_len > memory.len and !self.admit(new_len - memory.len)) return null;
        const ptr = self.parent.rawRemap(memory, alignment, new_len, ret_addr) orelse return null;
        self.adjust(memory.len, new_len);
        return ptr;
    }

    fn free(ctx: *anyopaque, memory: []u8, alignment: std.mem.Alignment, ret_addr: usize) void {
        const self: *MemoryAccount = @ptrCast(@alignCast(ctx));
        self.parent.rawFree(memory, alignment, ret_addr);
        self.discharge(memory.len);
    }

    fn adjust(self: *MemoryAccount, old_len: usize, new_len: usize) void {
        if (new_len > old_len) self.charge(new_len - old_len) else self.discharge(old_len - new_len);
    }
};

// zend_ini_parse_quantity: a number (decimal, or 0x/0o/0b prefixed) whose
// last character may be a K, M or G multiplier. what php warns about comes
// back in `problem`; the value is what php uses anyway
pub const Quantity = struct {
    value: i64,
    problem: ?Problem = null,

    pub const Problem = union(enum) {
        no_digits,
        unknown_multiplier: struct { multiplier: u8, used: []const u8 },
        out_of_range,
    };
};

pub fn parseQuantity(text: []const u8) Quantity {
    const trimmed = std.mem.trim(u8, text, " \t\n\r\x0b\x0c");
    if (trimmed.len == 0) return .{ .value = 0 };
    var i: usize = 0;
    var negative = false;
    if (trimmed[0] == '-' or trimmed[0] == '+') {
        negative = trimmed[0] == '-';
        i = 1;
    }
    var base: u8 = 10;
    if (i + 1 < trimmed.len and trimmed[i] == '0') {
        switch (trimmed[i + 1]) {
            'x', 'X' => base = 16,
            'o', 'O' => base = 8,
            'b', 'B' => base = 2,
            else => {},
        }
        if (base != 10) i += 2;
    }
    const digits_start = i;
    var magnitude: u64 = 0;
    var overflow = false;
    while (i < trimmed.len) : (i += 1) {
        const d = std.fmt.charToDigit(trimmed[i], base) catch break;
        const next = @mulWithOverflow(magnitude, base);
        const sum = @addWithOverflow(next[0], d);
        overflow = overflow or next[1] != 0 or sum[1] != 0;
        magnitude = sum[0];
    }
    if (i == digits_start) return .{ .value = 0, .problem = .no_digits };
    var value: i64 = @bitCast(if (negative) 0 -% magnitude else magnitude);
    const last = trimmed[trimmed.len - 1];
    if (i == trimmed.len) return .{ .value = value, .problem = if (overflow) .out_of_range else null };
    const shift: u6 = switch (last) {
        'k', 'K' => 10,
        'm', 'M' => 20,
        'g', 'G' => 30,
        else => return .{ .value = value, .problem = .{ .unknown_multiplier = .{ .multiplier = last, .used = trimmed[0..i] } } },
    };
    const scaled = @shlWithOverflow(value, shift);
    value = scaled[0];
    overflow = overflow or scaled[1] != 0;
    return .{ .value = value, .problem = if (overflow) .out_of_range else null };
}

test "the account counts, limits and rebases" {
    var account = MemoryAccount{ .parent = std.testing.allocator };
    const a = account.allocator();
    const first = try a.alloc(u8, 100);
    try std.testing.expectEqual(@as(usize, 100), account.usage());
    account.rebase();
    account.limit = 150;
    const second = try a.alloc(u8, 150);
    try std.testing.expectError(error.OutOfMemory, a.alloc(u8, 1));
    try std.testing.expectEqual(@as(?usize, 1), account.refused);
    a.free(second);
    try std.testing.expectError(error.OutOfMemory, a.alloc(u8, 1));
    account.reporting = true;
    const third = try a.alloc(u8, 8);
    a.free(third);
    a.free(first);
    try std.testing.expectEqual(@as(usize, 0), account.used.load(.monotonic));
    try std.testing.expectEqual(@as(i64, 128 << 20), parseQuantity("128M").value);
    try std.testing.expectEqual(@as(i64, -1), parseQuantity("-1").value);
    try std.testing.expectEqual(@as(i64, 512), parseQuantity(" 512 ").value);
    try std.testing.expectEqual(@as(i64, 16 << 20), parseQuantity("0x10M").value);
    try std.testing.expectEqual(@as(i64, 256), parseQuantity("256MB").value);
    try std.testing.expect(parseQuantity("abc").problem.? == .no_digits);
}
