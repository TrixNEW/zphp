// php class, interface, and trait names are case-insensitive (ascii only), so
// the maps keyed by them hash and compare folded names. the key keeps the
// spelling of the declaration, which is the name php reports back

const std = @import("std");

pub const Context = struct {
    pub fn hash(_: Context, name: []const u8) u64 {
        var buf: [256]u8 = undefined;
        if (name.len <= buf.len) return std.hash.Wyhash.hash(0, fold(buf[0..name.len], name));
        var h = std.hash.Wyhash.init(0);
        var i: usize = 0;
        while (i < name.len) : (i += buf.len) {
            const n = @min(buf.len, name.len - i);
            h.update(fold(buf[0..n], name[i .. i + n]));
        }
        return h.final();
    }

    pub fn eql(_: Context, a: []const u8, b: []const u8) bool {
        // names are almost always written the way they were declared
        return a.len == b.len and (std.mem.eql(u8, a, b) or std.ascii.eqlIgnoreCase(a, b));
    }
};

// ascii lowercase of src into dst, eight bytes at a time
fn fold(dst: []u8, src: []const u8) []const u8 {
    const ones: u64 = 0x0101010101010101;
    const high: u64 = 0x8080808080808080;
    var i: usize = 0;
    while (i + 8 <= src.len) : (i += 8) {
        const w = std.mem.readInt(u64, src[i..][0..8], .little);
        const low7 = w & ~high;
        // high bit of each byte: at least 'A', and above 'Z', among ascii bytes
        const ge_a = low7 + ones * (0x80 - 'A');
        const gt_z = low7 + ones * (0x80 - 'Z' - 1);
        const upper = ge_a & ~gt_z & ~w & high;
        std.mem.writeInt(u64, dst[i..][0..8], w | (upper >> 2), .little);
    }
    while (i < src.len) : (i += 1) dst[i] = std.ascii.toLower(src[i]);
    return dst[0..src.len];
}

pub fn Map(comptime V: type) type {
    return std.HashMapUnmanaged([]const u8, V, Context, std.hash_map.default_max_load_percentage);
}

// method names ignore case as well, except property hook entries
// (prop$hook_get), which follow their property's case-sensitive name
pub const MethodContext = struct {
    pub fn hash(_: MethodContext, name: []const u8) u64 {
        if (std.mem.indexOfScalar(u8, name, '$') != null) return std.hash.Wyhash.hash(0, name);
        return (Context{}).hash(name);
    }

    pub fn eql(_: MethodContext, a: []const u8, b: []const u8) bool {
        if (a.len != b.len) return false;
        if (std.mem.eql(u8, a, b)) return true;
        return std.mem.indexOfScalar(u8, a, '$') == null and std.ascii.eqlIgnoreCase(a, b);
    }
};

pub fn MethodMap(comptime V: type) type {
    return std.HashMapUnmanaged([]const u8, V, MethodContext, std.hash_map.default_max_load_percentage);
}

test "lookups ignore ascii case and keep the declared spelling" {
    const a = std.testing.allocator;
    var m: Map(u8) = .{};
    defer m.deinit(a);
    try m.put(a, "Foo\\BarBaz", 1);
    try std.testing.expectEqual(@as(?u8, 1), m.get("foo\\barbaz"));
    try std.testing.expectEqual(@as(?u8, 1), m.get("FOO\\BARBAZ"));
    try std.testing.expectEqualStrings("Foo\\BarBaz", m.getKey("foo\\BARBAZ").?);
    try std.testing.expect(m.get("Foo\\BarBa") == null);
    // names longer than the fold buffer hash the same in any case
    const long = "Very\\Long\\Namespace\\Path\\That\\Exceeds\\The\\Sixty\\Four\\Byte\\Buffer\\ClassName";
    try m.put(a, long, 2);
    var upper: [long.len]u8 = undefined;
    _ = std.ascii.upperString(&upper, long);
    try std.testing.expectEqual(@as(?u8, 2), m.get(&upper));
    // non-ascii bytes compare exactly, as in php
    try m.put(a, "Ünicode", 3);
    try std.testing.expect(m.get("ünicode") == null);
}

test "method names ignore case, hook entries don't" {
    const a = std.testing.allocator;
    var m: MethodMap(u8) = .{};
    defer m.deinit(a);
    try m.put(a, "getName", 1);
    try m.put(a, "name$hook_get", 2);
    try m.put(a, "Name$hook_get", 3);
    try std.testing.expectEqual(@as(?u8, 1), m.get("GETNAME"));
    try std.testing.expectEqualStrings("getName", m.getKey("getname").?);
    try std.testing.expectEqual(@as(?u8, 2), m.get("name$hook_get"));
    try std.testing.expectEqual(@as(?u8, 3), m.get("Name$hook_get"));
    try std.testing.expect(m.get("NAME$hook_get") == null);
}

test "fold lowercases exactly the ascii capitals" {
    var src: [256]u8 = undefined;
    for (&src, 0..) |*c, i| c.* = @intCast(i);
    var dst: [256]u8 = undefined;
    // every byte value, at every offset within the eight-byte words
    for (0..8) |shift| {
        const folded = fold(dst[0 .. 256 - shift], src[shift..]);
        for (folded, src[shift..]) |got, c| try std.testing.expectEqual(std.ascii.toLower(c), got);
    }
}
