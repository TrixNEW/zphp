const std = @import("std");

pub const Options = struct {
    io: std.Io,
    allocator: std.mem.Allocator,
    arena_allocator: std.mem.Allocator,
    enviorn: *const std.process.Environ.Map,
};
