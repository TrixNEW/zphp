// the fast loop links as its own object and compiles the runtime a second
// time; these symbols hand it the main object's per-thread state instead of
// its own copies
const value = @import("value.zig");

export fn zphp_value_hooks() callconv(.c) *anyopaque {
    return value.hooks();
}
