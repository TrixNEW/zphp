const std = @import("std");

// natives whose first parameter is by reference: the caller's array is passed
// in place and the native mutates it. the compiler separates a shared array
// before the call, and the VM treats the native's hold on it as an alias of
// the reference set rather than a value copy
const arg0_by_ref = [_][]const u8{
    "sort",         "rsort",      "asort",                "arsort",          "ksort",      "krsort",    "natsort",     "natcasesort",
    "usort",        "uasort",     "uksort",               "shuffle",         "array_push", "array_pop", "array_shift", "array_unshift",
    "array_splice", "array_walk", "array_walk_recursive", "array_multisort", "end",        "reset",     "next",        "prev",
    "each",
};

// an unqualified builtin call inside a namespace resolves to the namespaced
// name at compile time (e.g. Foo\Bar\array_shift) but falls back to the
// global native at runtime, so match on the basename
pub fn arg0IsByRef(name: []const u8) bool {
    const base = if (std.mem.lastIndexOfScalar(u8, name, '\\')) |i| name[i + 1 ..] else name;
    for (arg0_by_ref) |candidate| if (std.mem.eql(u8, candidate, base)) return true;
    return false;
}
