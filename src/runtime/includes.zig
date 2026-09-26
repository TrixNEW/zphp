const std = @import("std");
const platform = @import("../platform.zig");
const VM = @import("vm.zig").VM;

pub const default_include_path = if (platform.is_windows) ".;C:\\php\\pear" else ".:/usr/local/lib/php";
const list_separator: u8 = if (platform.is_windows) ';' else ':';

pub fn includePath(vm: *const VM) []const u8 {
    return vm.ini_settings.get("include_path") orelse default_include_path;
}

// zend_resolve_path: a path that names its own location (absolute, or led by
// ./ or ../) resolves against the working directory alone; any other tries
// each include_path entry, then the directory of the running file. the result
// is the file's canonical path, interned for the life of the VM, so every
// spelling of one file is one entry for require_once and get_included_files
pub fn resolve(vm: *VM, path: []const u8) error{OutOfMemory}!?[]const u8 {
    if (path.len == 0 or std.mem.indexOfScalar(u8, path, 0) != null) return null;
    if (hasScheme(path)) return try intern(vm, path);
    if (namesOwnLocation(path)) return canonical(vm, path);
    var entries = std.mem.splitScalar(u8, includePath(vm), list_separator);
    while (entries.next()) |entry| {
        if (entry.len == 0 or hasScheme(entry)) continue;
        if (try joined(vm, entry, path)) |found| return found;
    }
    const running = std.fs.path.dirname(vm.frameFile(vm.frame_count -| 1)) orelse return null;
    return joined(vm, running, path);
}

// the canonical path of an existing regular file: its directory through the
// per-VM realpath cache plus one lstat, or a full realpath when the file is
// itself a symlink
pub fn canonical(vm: *VM, path: []const u8) error{OutOfMemory}!?[]const u8 {
    const base = std.fs.path.basename(path);
    if (base.len > 0 and !std.mem.eql(u8, base, ".") and !std.mem.eql(u8, base, "..")) {
        const real_dir = realDir(vm, std.fs.path.dirname(path) orelse ".") orelse return null;
        var buf: [std.fs.max_path_bytes]u8 = undefined;
        const abs = join(&buf, real_dir, base) orelse return null;
        switch (entryKind(abs)) {
            .file => return try intern(vm, abs),
            .symlink => {},
            .other => return null,
        }
    }
    var buf: [std.fs.max_path_bytes]u8 = undefined;
    const real = std.fs.cwd().realpath(path, &buf) catch return null;
    if (entryKind(real) != .file) return null;
    return try intern(vm, real);
}

fn joined(vm: *VM, dir: []const u8, path: []const u8) error{OutOfMemory}!?[]const u8 {
    var buf: [std.fs.max_path_bytes]u8 = undefined;
    return canonical(vm, join(&buf, dir, path) orelse return null);
}

fn join(buf: []u8, dir: []const u8, name: []const u8) ?[]const u8 {
    const sep: []const u8 = if (dir.len > 0 and std.fs.path.isSep(dir[dir.len - 1])) "" else std.fs.path.sep_str;
    return std.fmt.bufPrint(buf, "{s}{s}{s}", .{ dir, sep, name }) catch null;
}

pub fn hasScheme(path: []const u8) bool {
    const end = std.mem.indexOf(u8, path, "://") orelse return false;
    // a drive letter is not a scheme
    if (end < 2) return false;
    for (path[0..end]) |c| {
        if (!(std.ascii.isAlphanumeric(c) or c == '+' or c == '-' or c == '.')) return false;
    }
    return true;
}

fn namesOwnLocation(path: []const u8) bool {
    return std.fs.path.isAbsolute(path) or ledBy(path, ".") or ledBy(path, "..");
}

fn ledBy(path: []const u8, dots: []const u8) bool {
    return path.len > dots.len and std.mem.startsWith(u8, path, dots) and std.fs.path.isSep(path[dots.len]);
}

const EntryKind = enum { file, symlink, other };

fn entryKind(path: []const u8) EntryKind {
    if (platform.is_windows) {
        const st = std.fs.cwd().statFile(path) catch return .other;
        return if (st.kind == .file) .file else .other;
    }
    const st = std.posix.fstatat(std.posix.AT.FDCWD, path, std.posix.AT.SYMLINK_NOFOLLOW) catch return .other;
    const mode: u32 = @intCast(st.mode);
    if (std.posix.S.ISREG(mode)) return .file;
    if (std.posix.S.ISLNK(mode)) return .symlink;
    return .other;
}

// directories are canonicalized once per VM; chdir clears the cache because
// relative keys name a different directory afterwards
pub fn realDir(vm: *VM, dir: []const u8) ?[]const u8 {
    if (vm.realdir_cache.get(dir)) |real| return real;
    const real = std.fs.cwd().realpathAlloc(vm.allocator, dir) catch return null;
    const key = vm.allocator.dupe(u8, dir) catch {
        vm.allocator.free(real);
        return null;
    };
    vm.realdir_cache.put(vm.allocator, key, real) catch {
        vm.allocator.free(key);
        vm.allocator.free(real);
        return null;
    };
    return real;
}

pub fn intern(vm: *VM, path: []const u8) error{OutOfMemory}![]const u8 {
    const paths = &vm.ic.?.include_paths;
    const gop = try paths.getOrPut(vm.allocator, path);
    if (!gop.found_existing) {
        gop.key_ptr.* = vm.allocator.dupe(u8, path) catch |err| {
            paths.removeByPtr(gop.key_ptr);
            return err;
        };
    }
    return gop.key_ptr.*;
}

test "schemes and self-locating paths" {
    try std.testing.expect(hasScheme("phar://a.phar/x.php"));
    try std.testing.expect(!hasScheme("C://x"));
    try std.testing.expect(!hasScheme("dir/x.php"));
    try std.testing.expect(namesOwnLocation("./x.php"));
    try std.testing.expect(namesOwnLocation("../x.php"));
    try std.testing.expect(!namesOwnLocation(".hidden.php"));
    try std.testing.expect(!namesOwnLocation("x.php"));
}
