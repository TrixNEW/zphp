const NativeResult = @import("../runtime/native_result.zig").NativeResult;
const std = @import("std");
const platform = @import("../platform.zig");
const Value = @import("../runtime/value.zig").Value;
const PhpArray = @import("../runtime/value.zig").PhpArray;
const PhpObject = @import("../runtime/value.zig").PhpObject;
const NativeContext = @import("../runtime/vm.zig").NativeContext;
const RuntimeError = error{ RuntimeError, OutOfMemory };

pub const entries = .{
    .{ "gethostbyname", native_gethostbyname },
    .{ "gethostbynamel", native_gethostbynamel },
    .{ "gethostbyaddr", native_gethostbyaddr },
    .{ "gethostname", native_gethostname },
    .{ "inet_pton", native_inet_pton },
    .{ "inet_ntop", native_inet_ntop },
    .{ "ip2long", native_ip2long },
    .{ "long2ip", native_long2ip },
    .{ "fsockopen", native_fsockopen },
    .{ "pfsockopen", native_fsockopen },
    .{ "stream_socket_client", native_stream_socket_client },
    .{ "stream_context_create", native_stream_context_create },
    .{ "stream_context_get_options", native_stream_context_get_options },
    .{ "stream_context_get_params", native_stream_context_get_params },
    .{ "stream_context_set_options", native_stream_context_set_options },
    .{ "stream_context_set_option", native_stream_context_set_options },
    .{ "stream_context_set_params", native_stream_context_set_params },
    .{ "stream_context_get_default", native_stream_context_get_default },
    .{ "stream_context_set_default", native_stream_context_set_default },
    .{ "checkdnsrr", native_checkdnsrr },
    .{ "dns_get_record", native_dns_get_record },
    .{ "stream_select", native_stream_select },
    .{ "stream_socket_pair", native_stream_socket_pair },
    .{ "stream_socket_server", native_stream_socket_server },
    .{ "stream_socket_accept", native_stream_socket_accept },
    .{ "stream_socket_get_name", native_stream_socket_get_name },
    .{ "stream_socket_shutdown", native_stream_socket_shutdown },
};

fn streamFd(v: Value) ?i64 {
    if (v != .object) return null;
    const fdv = v.object.get("__fd");
    if (fdv != .int or fdv.int < 0) return null;
    return fdv.int;
}

fn isNetStream(v: Value) bool {
    const net = v.object.get("__net");
    return net == .bool and net.bool;
}

const poll_events = [_]i16{ std.posix.POLL.IN, std.posix.POLL.OUT, std.posix.POLL.PRI };

// one stream from one of the three select sets. sockets sit in the poll
// list; on windows every other descriptor is a crt handle that WSAPoll
// cannot wait on, so it is checked by hand
const Watched = struct {
    slot: u8,
    val: Value,
    fd: i64,
    poll_index: ?usize,
    ready: bool = false,
};

const WatchList = std.ArrayListUnmanaged(Watched);
const PollList = std.ArrayListUnmanaged(std.posix.pollfd);

// stream_select(&$read, &$write, &$except, ?int $seconds, int $microseconds = 0): int|false
// waits on the streams in each (by-ref) array and rewrites each array to the
// ready subset, returning the number ready (false on error). null $seconds blocks
fn native_stream_select(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    var watched: WatchList = .{};
    defer watched.deinit(ctx.allocator);
    var pollfds: PollList = .{};
    defer pollfds.deinit(ctx.allocator);

    const total_streams = try watchStreams(ctx, args, &watched, &pollfds);
    if (total_streams == 0) {
        try ctx.vm.setPendingException("ValueError", "No stream arrays were passed");
        return error.RuntimeError;
    }

    const block = args.len < 4 or args[3] == .null;
    const sec: i64 = if (!block) Value.toInt(args[3]) else 0;
    const usec: i64 = if (args.len > 4) Value.toInt(args[4]) else 0;
    const timeout_ms: i32 = if (block) -1 else @intCast(@max(0, sec * 1000 + @divTrunc(usec, 1000)));

    awaitReady(watched.items, pollfds.items, timeout_ms) catch return NativeResult.scalar(.{ .bool = false });

    var out = [_]?*PhpArray{ null, null, null };
    var count: i64 = 0;
    for (watched.items) |w| {
        if (!w.ready) continue;
        if (out[w.slot] == null) out[w.slot] = try ctx.createArray();
        try out[w.slot].?.append(ctx.allocator, w.val);
        count += 1;
    }
    var slot: usize = 0;
    while (slot < 3) : (slot += 1) {
        if (slot >= args.len or args[slot] != .array) continue;
        const arr = out[slot] orelse try ctx.createArray();
        ctx.setCallerVar(slot, args.len, .{ .array = arr });
    }
    return NativeResult.scalar(.{ .int = count });
}

fn watchStreams(ctx: *NativeContext, args: []const Value, watched: *WatchList, pollfds: *PollList) !usize {
    var total: usize = 0;
    var slot: usize = 0;
    while (slot < 3) : (slot += 1) {
        if (slot >= args.len or args[slot] != .array) continue;
        for (args[slot].array.entries.items) |entry| {
            total += 1;
            const fd = streamFd(entry.value) orelse continue;
            var poll_index: ?usize = null;
            if (!platform.is_windows or isNetStream(entry.value)) {
                const sock = platform.socketFromInt(fd) orelse continue;
                poll_index = pollfds.items.len;
                try pollfds.append(ctx.allocator, .{ .fd = sock, .events = poll_events[slot], .revents = 0 });
            }
            try watched.append(ctx.allocator, .{ .slot = @intCast(slot), .val = entry.value, .fd = fd, .poll_index = poll_index });
        }
    }
    return total;
}

fn hasHandles(watched: []const Watched) bool {
    for (watched) |w| if (w.poll_index == null) return true;
    return false;
}

fn anyReady(watched: []const Watched) bool {
    for (watched) |w| if (w.ready) return true;
    return false;
}

// sockets wait in poll. with crt handles in the mix (windows only) the wait
// is sliced: each slice peeks the handles, then polls or sleeps for the
// slice, until something is ready or the timeout runs out
fn awaitReady(watched: []Watched, pollfds: []std.posix.pollfd, timeout_ms: i32) !void {
    if (!platform.is_windows or !hasHandles(watched)) {
        try pollSockets(pollfds, timeout_ms);
        markSocketsReady(watched, pollfds);
        return;
    }
    const slice_ms: i32 = 10;
    var remaining = timeout_ms;
    while (true) {
        const handle_ready = markHandlesReady(watched);
        const wait_ms: i32 = if (handle_ready or remaining == 0) 0 else if (remaining < 0) slice_ms else @min(slice_ms, remaining);
        try pollSockets(pollfds, wait_ms);
        markSocketsReady(watched, pollfds);
        if (anyReady(watched) or remaining == 0) return;
        if (pollfds.len == 0) std.Thread.sleep(@as(u64, @intCast(wait_ms)) * std.time.ns_per_ms);
        if (remaining > 0) remaining -= wait_ms;
    }
}

fn pollSockets(pollfds: []std.posix.pollfd, timeout_ms: i32) !void {
    if (pollfds.len == 0) return;
    _ = std.posix.poll(pollfds, timeout_ms) catch return error.PollFailed;
}

fn markSocketsReady(watched: []Watched, pollfds: []const std.posix.pollfd) void {
    for (watched) |*w| {
        const idx = w.poll_index orelse continue;
        const mask = poll_events[w.slot] | std.posix.POLL.ERR | std.posix.POLL.HUP;
        if ((pollfds[idx].revents & mask) != 0) w.ready = true;
    }
}

fn markHandlesReady(watched: []Watched) bool {
    var any = false;
    for (watched) |*w| {
        if (w.poll_index != null) continue;
        w.ready = switch (w.slot) {
            0 => handleReadable(w.fd),
            1 => true,
            else => false,
        };
        if (w.ready) any = true;
    }
    return any;
}

const win = std.os.windows;
const FILE_TYPE_PIPE: u32 = 3;
extern "kernel32" fn GetFileType(handle: win.HANDLE) callconv(.winapi) u32;
extern "kernel32" fn PeekNamedPipe(pipe: win.HANDLE, buffer: ?*anyopaque, size: u32, read: ?*u32, available: ?*u32, left: ?*u32) callconv(.winapi) win.BOOL;

// a pipe is readable once bytes are pending or its writer is gone; a disk
// file or console reads without waiting, which is what php's own select
// emulation on windows reports
fn handleReadable(fd: i64) bool {
    const file = platform.fileFromFd(fd) orelse return true;
    if (GetFileType(file.handle) != FILE_TYPE_PIPE) return true;
    var available: u32 = 0;
    if (PeekNamedPipe(file.handle, null, 0, null, &available, null) != 0) return available > 0;
    return win.GetLastError() == .BROKEN_PIPE;
}

// stream_socket_pair(int $domain, int $type, int $protocol): array|false
// a connected pair of stream sockets (a unix pair, or the loopback tcp pair
// php itself uses on windows) returned as two net stream objects
fn native_stream_socket_pair(ctx: *NativeContext, _: []const Value) RuntimeError!NativeResult {
    const pair = platform.socketPair() catch return NativeResult.scalar(.{ .bool = false });
    const arr = try ctx.createArray();
    for (pair) |sock| {
        const obj = try socketStream(ctx, sock);
        try arr.append(ctx.allocator, .{ .object = obj });
    }
    return NativeResult.borrowed(.{ .array = arr });
}

pub fn socketStream(ctx: *NativeContext, sock: std.posix.socket_t) !*PhpObject {
    const obj = try ctx.allocator.create(PhpObject);
    obj.* = .{ .class_name = "FileHandle" };
    try ctx.vm.objects.append(ctx.allocator, obj);
    try obj.set(ctx.allocator, "__fd", .{ .int = platform.socketToInt(sock) });
    try obj.set(ctx.allocator, "__open", .{ .bool = true });
    try obj.set(ctx.allocator, "__mode", .{ .string = Value.String.borrowed("r+") });
    try obj.set(ctx.allocator, "__net", .{ .bool = true });
    return obj;
}

fn native_stream_context_create(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    const obj = try ctx.vm.allocator.create(PhpObject);
    obj.* = .{ .class_name = "StreamContext" };
    try ctx.vm.objects.append(ctx.vm.allocator, obj);
    if (args.len >= 1 and args[0] == .array) {
        try obj.set(ctx.allocator, "options", args[0]);
    }
    if (args.len >= 2 and args[1] == .array) {
        try obj.set(ctx.allocator, "params", args[1]);
    }
    return NativeResult.borrowed(.{ .object = obj });
}

fn native_stream_context_get_options(_: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    if (args.len < 1 or args[0] != .object) return NativeResult.scalar(.{ .bool = false });
    const opts = args[0].object.get("options");
    if (opts == .array) return NativeResult.borrowed(opts);
    return NativeResult.scalar(.{ .bool = false });
}

fn native_stream_context_get_params(_: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    if (args.len < 1 or args[0] != .object) return NativeResult.scalar(.{ .bool = false });
    return NativeResult.share(args[0].object.get("params"));
}

fn native_stream_context_set_options(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    if (args.len < 2 or args[0] != .object) return NativeResult.scalar(.{ .bool = false });
    // 4-arg form: stream_context_set_option($ctx, $wrapper, $option, $value)
    // merges the (wrapper, option) into the existing options array
    if (args.len >= 4 and args[1] == .string and args[2] == .string) {
        const opts_v = args[0].object.get("options");
        var opts: *PhpArray = undefined;
        if (opts_v == .array) {
            opts = opts_v.array;
        } else {
            opts = try ctx.createArray();
            try args[0].object.set(ctx.allocator, "options", .{ .array = opts });
        }
        const wrap_key: PhpArray.Key = .{ .string = args[1].string };
        const wrap_v = opts.get(wrap_key);
        var wrap_arr: *PhpArray = undefined;
        if (wrap_v == .array) {
            wrap_arr = wrap_v.array;
        } else {
            wrap_arr = try ctx.createArray();
            try ctx.vm.arraySetOwned(opts, wrap_key, .{ .array = wrap_arr });
        }
        try ctx.vm.arraySetOwned(wrap_arr, PhpArray.Key{ .string = args[2].string }, args[3]);
        return NativeResult.scalar(.{ .bool = true });
    }
    // 3-arg form: stream_context_set_option($ctx, $wrapper, $options_assoc) -
    // PHP also accepts this; merge per-wrapper
    if (args.len == 3 and args[1] == .string and args[2] == .array) {
        const opts_v = args[0].object.get("options");
        var opts: *PhpArray = undefined;
        if (opts_v == .array) opts = opts_v.array else {
            opts = try ctx.createArray();
            try args[0].object.set(ctx.allocator, "options", .{ .array = opts });
        }
        try ctx.vm.arraySetOwned(opts, PhpArray.Key{ .string = args[1].string }, args[2]);
        return NativeResult.scalar(.{ .bool = true });
    }
    if (args[1] == .array) {
        try args[0].object.set(ctx.allocator, "options", args[1]);
        return NativeResult.scalar(.{ .bool = true });
    }
    return NativeResult.scalar(.{ .bool = false });
}

fn native_stream_context_set_params(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    if (args.len < 2 or args[0] != .object) return NativeResult.scalar(.{ .bool = false });
    if (args[1] == .array) {
        try args[0].object.set(ctx.allocator, "params", args[1]);
        return NativeResult.scalar(.{ .bool = true });
    }
    return NativeResult.scalar(.{ .bool = false });
}

fn native_stream_context_get_default(ctx: *NativeContext, _: []const Value) RuntimeError!NativeResult {
    const obj = try ctx.vm.allocator.create(PhpObject);
    obj.* = .{ .class_name = "StreamContext" };
    try ctx.vm.objects.append(ctx.vm.allocator, obj);
    return NativeResult.borrowed(.{ .object = obj });
}

fn native_stream_context_set_default(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    return native_stream_context_create(ctx, args);
}

fn native_gethostbyname(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    if (args.len == 0 or args[0] != .string) return NativeResult.scalar(.{ .bool = false });
    const host = args[0].string.bytes();
    var list = std.net.getAddressList(ctx.allocator, host, 0) catch return NativeResult.share(args[0]);
    defer list.deinit();
    if (list.addrs.len == 0) return NativeResult.share(args[0]);
    for (list.addrs) |addr| {
        if (addr.any.family == std.posix.AF.INET) {
            var buf: [32]u8 = undefined;
            const written = std.fmt.bufPrint(&buf, "{f}", .{addr}) catch return NativeResult.share(args[0]);
            // strip port if present (Address.format adds :port)
            const colon = std.mem.lastIndexOfScalar(u8, written, ':') orelse written.len;
            return NativeResult.copyString(ctx.allocator, written[0..colon]);
        }
    }
    return NativeResult.share(args[0]);
}

// gethostbynamel: like gethostbyname but returns all resolved IPv4 addresses
// as a numerically-indexed array, or false on failure
fn native_gethostbynamel(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    if (args.len == 0 or args[0] != .string) return NativeResult.scalar(.{ .bool = false });
    const host = args[0].string.bytes();
    var list = std.net.getAddressList(ctx.allocator, host, 0) catch return NativeResult.scalar(.{ .bool = false });
    defer list.deinit();
    if (list.addrs.len == 0) return NativeResult.scalar(.{ .bool = false });
    const arr = try ctx.createArray();
    var seen = std.StringHashMapUnmanaged(void){};
    defer seen.deinit(ctx.allocator);
    for (list.addrs) |addr| {
        if (addr.any.family != std.posix.AF.INET) continue;
        var buf: [32]u8 = undefined;
        const written = std.fmt.bufPrint(&buf, "{f}", .{addr}) catch continue;
        const colon = std.mem.lastIndexOfScalar(u8, written, ':') orelse written.len;
        const ip_str = written[0..colon];
        // dedup - the same IP can come back multiple times for different ports
        if (seen.contains(ip_str)) continue;
        const owned = try Value.String.create(ctx.allocator, ip_str);
        defer owned.release();
        try seen.put(ctx.allocator, owned.bytes(), {});
        try arr.append(ctx.allocator, .{ .string = owned });
    }
    if (arr.entries.items.len == 0) return NativeResult.scalar(.{ .bool = false });
    return NativeResult.borrowed(.{ .array = arr });
}

fn native_gethostbyaddr(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    if (args.len == 0 or args[0] != .string) return NativeResult.scalar(.{ .bool = false });
    // best-effort: just echo back the IP if we can't reverse-resolve
    return NativeResult.copyString(ctx.allocator, args[0].string.bytes());
}

fn native_gethostname(ctx: *NativeContext, _: []const Value) RuntimeError!NativeResult {
    var buf: [256]u8 = undefined;
    const name = platform.hostname(&buf) orelse return NativeResult.scalar(.{ .bool = false });
    return NativeResult.copyString(ctx.allocator, name);
}

fn native_inet_pton(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    if (args.len == 0 or args[0] != .string) return NativeResult.scalar(.{ .bool = false });
    const s = args[0].string.bytes();
    // try IPv4
    if (std.net.Address.parseIp4(s, 0)) |addr| {
        const bytes = std.mem.toBytes(addr.in.sa.addr);
        return NativeResult.copyString(ctx.allocator, &bytes);
    } else |_| {}
    // try IPv6
    if (std.net.Address.parseIp6(s, 0)) |addr| {
        return NativeResult.copyString(ctx.allocator, &addr.in6.sa.addr);
    } else |_| {}
    return NativeResult.scalar(.{ .bool = false });
}

fn native_inet_ntop(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    if (args.len == 0 or args[0] != .string) return NativeResult.scalar(.{ .bool = false });
    const bytes = args[0].string.bytes();
    if (bytes.len == 4) {
        var buf: [32]u8 = undefined;
        const out = std.fmt.bufPrint(&buf, "{d}.{d}.{d}.{d}", .{ bytes[0], bytes[1], bytes[2], bytes[3] }) catch return NativeResult.scalar(.{ .bool = false });
        return NativeResult.copyString(ctx.allocator, out);
    }
    if (bytes.len == 16) {
        var addr: [16]u8 = undefined;
        @memcpy(&addr, bytes);
        const ip = std.net.Address.initIp6(addr, 0, 0, 0);
        var buf: [64]u8 = undefined;
        const out = std.fmt.bufPrint(&buf, "{f}", .{ip}) catch return NativeResult.scalar(.{ .bool = false });
        // strip [...]:port wrapping
        var s = out;
        if (s.len > 0 and s[0] == '[') {
            const close = std.mem.indexOfScalar(u8, s, ']') orelse return NativeResult.scalar(.{ .bool = false });
            s = s[1..close];
        }
        return NativeResult.copyString(ctx.allocator, s);
    }
    return NativeResult.scalar(.{ .bool = false });
}

fn native_ip2long(_: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    if (args.len == 0 or args[0] != .string) return NativeResult.scalar(.{ .bool = false });
    var parts: [4]u32 = undefined;
    var idx: usize = 0;
    var it = std.mem.splitScalar(u8, args[0].string.bytes(), '.');
    while (it.next()) |part| {
        if (idx >= 4) return NativeResult.scalar(.{ .bool = false });
        parts[idx] = std.fmt.parseUnsigned(u8, part, 10) catch return NativeResult.scalar(.{ .bool = false });
        idx += 1;
    }
    if (idx != 4) return NativeResult.scalar(.{ .bool = false });
    const long: i64 = @intCast((parts[0] << 24) | (parts[1] << 16) | (parts[2] << 8) | parts[3]);
    return NativeResult.scalar(.{ .int = long });
}

fn native_long2ip(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    if (args.len == 0) return NativeResult.scalar(.{ .bool = false });
    const n = Value.toInt(args[0]);
    const u: u32 = @truncate(@as(u64, @bitCast(n)));
    var buf: [32]u8 = undefined;
    const out = std.fmt.bufPrint(&buf, "{d}.{d}.{d}.{d}", .{ (u >> 24) & 0xff, (u >> 16) & 0xff, (u >> 8) & 0xff, u & 0xff }) catch return NativeResult.scalar(.{ .bool = false });
    return NativeResult.copyString(ctx.allocator, out);
}

// a transport address as stream_socket_client/server take it
const Endpoint = union(enum) {
    tcp: std.net.Address,
    unix: []const u8,
};

const SocketError = struct { code: i64, message: []const u8 };

// errno values and strerror text for the failures sockets report
fn socketError(err: anyerror) SocketError {
    const E = std.posix.E;
    const mac = @import("builtin").os.tag.isDarwin();
    return switch (err) {
        error.AddressInUse => .{ .code = @intFromEnum(E.ADDRINUSE), .message = "Address already in use" },
        error.ConnectionRefused => .{ .code = @intFromEnum(E.CONNREFUSED), .message = "Connection refused" },
        error.AccessDenied, error.PermissionDenied => .{ .code = @intFromEnum(E.ACCES), .message = "Permission denied" },
        error.NetworkUnreachable => .{ .code = @intFromEnum(E.NETUNREACH), .message = "Network is unreachable" },
        error.AddressNotAvailable => .{ .code = @intFromEnum(E.ADDRNOTAVAIL), .message = "Can't assign requested address" },
        error.ConnectionTimedOut, error.Timeout => .{ .code = @intFromEnum(E.TIMEDOUT), .message = if (mac) "Operation timed out" else "Connection timed out" },
        error.FileNotFound => .{ .code = @intFromEnum(E.NOENT), .message = "No such file or directory" },
        error.UnknownHostName, error.HostNotFound => .{ .code = 0, .message = "php_network_getaddresses: getaddrinfo failed" },
        else => .{ .code = 0, .message = @errorName(err) },
    };
}

// tcp://host:port (the default transport), [v6]:port, unix:///path
fn parseEndpoint(allocator: std.mem.Allocator, target: []const u8) !Endpoint {
    var scheme: []const u8 = "tcp";
    var rest = target;
    if (std.mem.indexOf(u8, target, "://")) |idx| {
        scheme = target[0..idx];
        rest = target[idx + 3 ..];
    }
    if (std.ascii.eqlIgnoreCase(scheme, "unix")) return .{ .unix = rest };
    if (!std.ascii.eqlIgnoreCase(scheme, "tcp")) return error.UnsupportedTransport;
    const colon = std.mem.lastIndexOfScalar(u8, rest, ':') orelse return error.MissingPort;
    var host = rest[0..colon];
    const port = std.fmt.parseUnsigned(u16, rest[colon + 1 ..], 10) catch return error.MissingPort;
    if (host.len >= 2 and host[0] == '[' and host[host.len - 1] == ']') host = host[1 .. host.len - 1];
    if (std.net.Address.parseIp(host, port)) |addr| return .{ .tcp = addr } else |_| {}
    const list = try std.net.getAddressList(allocator, host, port);
    defer list.deinit();
    if (list.addrs.len == 0) return error.UnknownHostName;
    return .{ .tcp = list.addrs[0] };
}

fn endpointAddress(endpoint: Endpoint) !std.net.Address {
    return switch (endpoint) {
        .tcp => |addr| addr,
        .unix => |path| std.net.Address.initUnix(path),
    };
}

fn newSocket(family: u32) !std.posix.socket_t {
    if (family == std.posix.AF.UNIX) return std.posix.socket(family, std.posix.SOCK.STREAM | std.posix.SOCK.CLOEXEC, 0);
    return platform.tcpSocket(family);
}

// connects, waiting at most timeout_ms (null blocks); async leaves the
// connection in progress on a non-blocking socket, as STREAM_CLIENT_ASYNC_CONNECT does
fn connectSocket(addr: std.net.Address, timeout_ms: ?i32, async_connect: bool) !std.posix.socket_t {
    const sock = try newSocket(addr.any.family);
    errdefer platform.closeSocket(platform.socketToInt(sock));
    if (!async_connect and timeout_ms == null) {
        try std.posix.connect(sock, &addr.any, addr.getOsSockLen());
        return sock;
    }
    try platform.setNonBlocking(sock, true);
    std.posix.connect(sock, &addr.any, addr.getOsSockLen()) catch |err| switch (err) {
        error.WouldBlock, error.ConnectionPending => {},
        else => return err,
    };
    if (async_connect) return sock;
    var fds = [_]std.posix.pollfd{.{ .fd = sock, .events = std.posix.POLL.OUT, .revents = 0 }};
    if (try std.posix.poll(&fds, timeout_ms.?) == 0) return error.ConnectionTimedOut;
    try std.posix.getsockoptError(sock);
    try platform.setNonBlocking(sock, false);
    return sock;
}

fn timeoutMs(ctx: *NativeContext, v: ?Value) ?i32 {
    const seconds: f64 = if (v) |t| (if (t == .null) defaultSocketTimeout(ctx) else Value.toFloat(t)) else defaultSocketTimeout(ctx);
    if (seconds < 0) return null;
    return @intFromFloat(@min(seconds * 1000.0, @as(f64, std.math.maxInt(i32))));
}

fn defaultSocketTimeout(ctx: *NativeContext) f64 {
    const v = ctx.vm.ini_settings.get("default_socket_timeout") orelse return 60;
    return std.fmt.parseFloat(f64, v) catch 60;
}

// the by-reference $error_code / $error_message outputs; unconnected they
// read 0 and ""
fn reportSocketError(ctx: *NativeContext, args: []const Value, code_index: usize, err: ?SocketError) !void {
    const e = err orelse SocketError{ .code = 0, .message = "" };
    ctx.setCallerVar(code_index, args.len, .{ .int = e.code });
    const message = try ctx.createString(e.message);
    ctx.setCallerVar(code_index + 1, args.len, .{ .string = Value.String.borrowed(message) });
}

fn failConnect(ctx: *NativeContext, args: []const Value, code_index: usize, comptime func: []const u8, target: []const u8, err: anyerror) RuntimeError!NativeResult {
    const e = socketError(err);
    try reportSocketError(ctx, args, code_index, e);
    const msg = try std.fmt.allocPrint(ctx.allocator, func ++ "(): Unable to connect to {s} ({s})", .{ target, e.message });
    defer ctx.allocator.free(msg);
    try ctx.vm.emitWarning(msg);
    return NativeResult.scalar(.{ .bool = false });
}

fn endpointStream(ctx: *NativeContext, sock: std.posix.socket_t, endpoint: Endpoint, blocking: bool, uri: ?[]const u8) !*PhpObject {
    const obj = try socketStream(ctx, sock);
    if (uri) |u| try obj.set(ctx.allocator, "__path", .{ .string = Value.String.borrowed(try ctx.createString(u)) });
    try obj.set(ctx.allocator, "__sock_type", .{ .string = Value.String.borrowed(if (endpoint == .unix) "unix_socket" else "tcp_socket/ssl") });
    if (!blocking) try obj.set(ctx.allocator, "__blocking", .{ .bool = false });
    return obj;
}

fn native_fsockopen(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    if (args.len < 1 or args[0] != .string) return NativeResult.scalar(.{ .bool = false });
    const host = args[0].string.bytes();
    const port: i64 = if (args.len >= 2 and args[1] != .null) Value.toInt(args[1]) else -1;
    const target = if (port >= 0)
        try std.fmt.allocPrint(ctx.allocator, "{s}:{d}", .{ host, port })
    else
        try ctx.allocator.dupe(u8, host);
    defer ctx.allocator.free(target);
    return connectStream(ctx, args, 2, target, if (args.len > 4) args[4] else null, false, "fsockopen");
}

// stream_socket_client(string $address, &$error_code, &$error_message, ?float $timeout, int $flags, $context)
fn native_stream_socket_client(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    if (args.len < 1 or args[0] != .string) return NativeResult.scalar(.{ .bool = false });
    const flags: i64 = if (args.len > 4) Value.toInt(args[4]) else 4;
    return connectStream(ctx, args, 1, args[0].string.bytes(), if (args.len > 3) args[3] else null, (flags & 2) != 0, "stream_socket_client");
}

fn connectStream(ctx: *NativeContext, args: []const Value, code_index: usize, target: []const u8, timeout: ?Value, async_connect: bool, comptime func: []const u8) RuntimeError!NativeResult {
    const endpoint = parseEndpoint(ctx.allocator, target) catch |err| return failConnect(ctx, args, code_index, func, target, err);
    const addr = endpointAddress(endpoint) catch |err| return failConnect(ctx, args, code_index, func, target, err);
    const sock = connectSocket(addr, timeoutMs(ctx, timeout), async_connect) catch |err| return failConnect(ctx, args, code_index, func, target, err);
    try reportSocketError(ctx, args, code_index, null);
    return NativeResult.borrowed(.{ .object = try endpointStream(ctx, sock, endpoint, !async_connect, target) });
}

// stream_socket_server(string $address, &$error_code, &$error_message, int $flags = BIND|LISTEN, $context)
fn native_stream_socket_server(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    if (args.len < 1 or args[0] != .string) return NativeResult.scalar(.{ .bool = false });
    const target = args[0].string.bytes();
    const flags: i64 = if (args.len > 3) Value.toInt(args[3]) else 12;
    const endpoint = parseEndpoint(ctx.allocator, target) catch |err| return failConnect(ctx, args, 1, "stream_socket_server", target, err);
    const sock = listenSocket(endpoint, flags, contextBacklog(args)) catch |err| return failConnect(ctx, args, 1, "stream_socket_server", target, err);
    try reportSocketError(ctx, args, 1, null);
    const obj = try endpointStream(ctx, sock, endpoint, true, target);
    try obj.set(ctx.allocator, "__server", .{ .bool = true });
    return NativeResult.borrowed(.{ .object = obj });
}

fn listenSocket(endpoint: Endpoint, flags: i64, backlog: u31) !std.posix.socket_t {
    const addr = try endpointAddress(endpoint);
    const sock = try newSocket(addr.any.family);
    errdefer platform.closeSocket(platform.socketToInt(sock));
    if (endpoint == .tcp and !platform.is_windows) try std.posix.setsockopt(sock, std.posix.SOL.SOCKET, std.posix.SO.REUSEADDR, &std.mem.toBytes(@as(c_int, 1)));
    if ((flags & 4) != 0) try std.posix.bind(sock, &addr.any, addr.getOsSockLen());
    if ((flags & 8) != 0) try std.posix.listen(sock, backlog);
    return sock;
}

// the context's socket.backlog option, php's default otherwise
fn contextBacklog(args: []const Value) u31 {
    if (args.len < 5 or args[4] != .object) return 32;
    const options = args[4].object.get("options");
    if (options != .array) return 32;
    const socket_opts = options.array.get(.{ .string = Value.String.borrowed("socket") });
    if (socket_opts != .array) return 32;
    const backlog = socket_opts.array.get(.{ .string = Value.String.borrowed("backlog") });
    if (backlog == .null) return 32;
    return @intCast(std.math.clamp(Value.toInt(backlog), 1, std.math.maxInt(u31)));
}

// stream_socket_accept($socket, ?float $timeout = null, &$peer_name): a new
// stream for the next connection, or false once the timeout passes
fn native_stream_socket_accept(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    const fd = if (args.len > 0) streamFd(args[0]) else null;
    const server = platform.socketFromInt(fd orelse return NativeResult.scalar(.{ .bool = false })) orelse return NativeResult.scalar(.{ .bool = false });
    var fds = [_]std.posix.pollfd{.{ .fd = server, .events = std.posix.POLL.IN, .revents = 0 }};
    const ready = std.posix.poll(&fds, timeoutMs(ctx, if (args.len > 1) args[1] else null) orelse -1) catch 0;
    if (ready == 0) {
        try ctx.vm.emitWarning("stream_socket_accept(): Accept failed: " ++ comptime socketError(error.Timeout).message);
        return NativeResult.scalar(.{ .bool = false });
    }
    var peer: std.net.Address = undefined;
    var len: std.posix.socklen_t = @sizeOf(std.net.Address);
    const sock = std.posix.accept(server, &peer.any, &len, std.posix.SOCK.CLOEXEC) catch |err| {
        const msg = try std.fmt.allocPrint(ctx.allocator, "stream_socket_accept(): Accept failed: {s}", .{socketError(err).message});
        defer ctx.allocator.free(msg);
        try ctx.vm.emitWarning(msg);
        return NativeResult.scalar(.{ .bool = false });
    };
    // like php, the stream copies the listener's blocking flag while the
    // descriptor keeps whatever mode the OS gives it (BSDs inherit O_NONBLOCK
    // from the listener, linux does not)
    const listener_blocks = args[0].object.get("__blocking") != .bool or args[0].object.get("__blocking").bool;
    const is_unix = peer.any.family == std.posix.AF.UNIX;
    const obj = try endpointStream(ctx, sock, if (is_unix) .{ .unix = "" } else .{ .tcp = peer }, listener_blocks, null);
    if (args.len > 2) {
        const name = try addressName(ctx, peer, len);
        ctx.setCallerVar(2, args.len, .{ .string = Value.String.borrowed(name) });
    }
    return NativeResult.borrowed(.{ .object = obj });
}

// stream_socket_get_name($socket, bool $remote): "host:port", a unix path,
// or false when there is no such end
fn native_stream_socket_get_name(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    const fd = if (args.len > 0) streamFd(args[0]) else null;
    const sock = platform.socketFromInt(fd orelse return NativeResult.scalar(.{ .bool = false })) orelse return NativeResult.scalar(.{ .bool = false });
    const remote = args.len > 1 and args[1].isTruthy();
    var addr: std.net.Address = undefined;
    var len: std.posix.socklen_t = @sizeOf(std.net.Address);
    // not connected (a listener asked for its peer) is an answer, not a fault
    const rc = if (remote) std.c.getpeername(sock, &addr.any, &len) else std.c.getsockname(sock, &addr.any, &len);
    if (rc != 0) return NativeResult.scalar(.{ .bool = false });
    const name = try addressName(ctx, addr, len);
    return NativeResult.copyString(ctx.allocator, name);
}

fn addressName(ctx: *NativeContext, addr: std.net.Address, len: std.posix.socklen_t) ![]const u8 {
    if (addr.any.family == std.posix.AF.UNIX) {
        const path_len = @as(usize, len) -| @offsetOf(std.posix.sockaddr.un, "path");
        const path = std.mem.sliceTo(addr.un.path[0..@min(path_len, addr.un.path.len)], 0);
        return ctx.createString(path);
    }
    const text = try std.fmt.allocPrint(ctx.allocator, "{f}", .{addr});
    try ctx.strings.append(ctx.allocator, text);
    return text;
}

// stream_socket_shutdown($stream, int $mode): STREAM_SHUT_RD, _WR or _RDWR
fn native_stream_socket_shutdown(_: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    const fd = if (args.len > 0) streamFd(args[0]) else null;
    const sock = platform.socketFromInt(fd orelse return NativeResult.scalar(.{ .bool = false })) orelse return NativeResult.scalar(.{ .bool = false });
    const how: std.posix.ShutdownHow = switch (if (args.len > 1) Value.toInt(args[1]) else 2) {
        0 => .recv,
        1 => .send,
        else => .both,
    };
    std.posix.shutdown(sock, how) catch return NativeResult.scalar(.{ .bool = false });
    return NativeResult.scalar(.{ .bool = true });
}

fn native_checkdnsrr(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    if (args.len == 0 or args[0] != .string) return NativeResult.scalar(.{ .bool = false });
    var list = std.net.getAddressList(ctx.allocator, args[0].string.bytes(), 0) catch return NativeResult.scalar(.{ .bool = false });
    defer list.deinit();
    return NativeResult.scalar(.{ .bool = list.addrs.len > 0 });
}

fn native_dns_get_record(ctx: *NativeContext, args: []const Value) RuntimeError!NativeResult {
    if (args.len == 0 or args[0] != .string) return NativeResult.scalar(.{ .bool = false });
    const result = try ctx.createArray();
    var list = std.net.getAddressList(ctx.allocator, args[0].string.bytes(), 0) catch return NativeResult.borrowed(.{ .array = result });
    defer list.deinit();
    for (list.addrs) |addr| {
        const entry = try ctx.createArray();
        try entry.set(ctx.allocator, .{ .string = Value.String.borrowed("host") }, args[0]);
        try entry.set(ctx.allocator, .{ .string = Value.String.borrowed("class") }, .{ .string = Value.String.borrowed("IN") });
        if (addr.any.family == std.posix.AF.INET) {
            const bytes = std.mem.toBytes(addr.in.sa.addr);
            var buf: [32]u8 = undefined;
            const ip = std.fmt.bufPrint(&buf, "{d}.{d}.{d}.{d}", .{ bytes[0], bytes[1], bytes[2], bytes[3] }) catch continue;
            try entry.set(ctx.allocator, .{ .string = Value.String.borrowed("type") }, .{ .string = Value.String.borrowed("A") });
            const owned = try Value.String.create(ctx.allocator, ip);
            defer owned.release();
            try entry.set(ctx.allocator, .{ .string = Value.String.borrowed("ip") }, .{ .string = owned });
        } else if (addr.any.family == std.posix.AF.INET6) {
            try entry.set(ctx.allocator, .{ .string = Value.String.borrowed("type") }, .{ .string = Value.String.borrowed("AAAA") });
            var buf: [64]u8 = undefined;
            const raw = std.fmt.bufPrint(&buf, "{f}", .{addr}) catch continue;
            var out: []const u8 = raw;
            if (out.len > 0 and out[0] == '[') {
                if (std.mem.indexOfScalar(u8, out, ']')) |ci| out = out[1..ci];
            }
            const owned = try Value.String.create(ctx.allocator, out);
            defer owned.release();
            try entry.set(ctx.allocator, .{ .string = Value.String.borrowed("ipv6") }, .{ .string = owned });
        }
        try result.append(ctx.allocator, .{ .array = entry });
    }
    return NativeResult.borrowed(.{ .array = result });
}
