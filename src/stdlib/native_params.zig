const std = @import("std");
const generated = @import("native_params_generated.zig");

// the parameter list of a native function or method, which natives do not
// declare themselves: named arguments are placed by it and a skipped optional
// parameter receives its default. natives php shares come from php's own
// reflection (scripts/gen-native-params); the ones only zphp has are below

pub const Default = union(enum) {
    required,
    // optional, but php does not publish the value, so it cannot be skipped
    unknown,
    null,
    bool: bool,
    int: i64,
    float: f64,
    string: []const u8,
    empty_array,
    class_constant: struct { class: []const u8, name: []const u8 },
};

pub const Param = struct {
    name: []const u8,
    default: Default = .required,
    variadic: bool = false,
};

const zphp_only = std.StaticStringMap([]const Param).initComptime(.{
    .{ "Zphp\\Pool::__construct", &[_]Param{ .{ .name = "workers", .default = .null }, .{ .name = "bootstrap", .default = .null }, .{ .name = "queue", .default = .null } } },
    .{ "Zphp\\Pool::submit", &[_]Param{ .{ .name = "callable" }, .{ .name = "args", .default = .empty_array } } },
    .{ "Zphp\\Pool::trySubmit", &[_]Param{ .{ .name = "callable" }, .{ .name = "args", .default = .empty_array } } },
    .{ "Zphp\\Pool::collect", &[_]Param{ .{ .name = "timeout", .default = .null } } },
    .{ "Zphp\\Pool::shutdown", &[_]Param{ .{ .name = "timeout", .default = .null } } },
    .{ "Zphp\\Future::await", &[_]Param{ .{ .name = "timeout", .default = .null } } },
    .{ "Zphp\\Channel::__construct", &[_]Param{ .{ .name = "capacity", .default = .{ .int = 1 } } } },
    .{ "Zphp\\Channel::send", &[_]Param{ .{ .name = "value" }, .{ .name = "timeout", .default = .null } } },
    .{ "Zphp\\Channel::trySend", &[_]Param{ .{ .name = "value" } } },
    .{ "Zphp\\Channel::recv", &[_]Param{ .{ .name = "timeout", .default = .null } } },
    .{ "Zphp\\select", &[_]Param{ .{ .name = "sources" }, .{ .name = "timeout", .default = .null } } },
    .{ "Zphp\\Buffer::__construct", &[_]Param{ .{ .name = "length" } } },
    .{ "Zphp\\Buffer::fromString", &[_]Param{ .{ .name = "bytes" } } },
    .{ "Zphp\\Buffer::slice", &[_]Param{ .{ .name = "offset" }, .{ .name = "length", .default = .null } } },
    .{ "Zphp\\Buffer::write", &[_]Param{ .{ .name = "offset" }, .{ .name = "data" } } },
    .{ "Zphp\\Buffer::readFrom", &[_]Param{ .{ .name = "stream" } } },
    .{ "Zphp\\Buffer::writeTo", &[_]Param{ .{ .name = "stream" } } },
    .{ "Zphp\\Buffer::readInt8", &[_]Param{ .{ .name = "offset" } } },
    .{ "Zphp\\Buffer::writeInt8", &[_]Param{ .{ .name = "offset" }, .{ .name = "value" } } },
    .{ "Zphp\\Buffer::readUInt8", &[_]Param{ .{ .name = "offset" } } },
    .{ "Zphp\\Buffer::writeUInt8", &[_]Param{ .{ .name = "offset" }, .{ .name = "value" } } },
    .{ "Zphp\\Buffer::readInt16LE", &[_]Param{ .{ .name = "offset" } } },
    .{ "Zphp\\Buffer::writeInt16LE", &[_]Param{ .{ .name = "offset" }, .{ .name = "value" } } },
    .{ "Zphp\\Buffer::readInt16BE", &[_]Param{ .{ .name = "offset" } } },
    .{ "Zphp\\Buffer::writeInt16BE", &[_]Param{ .{ .name = "offset" }, .{ .name = "value" } } },
    .{ "Zphp\\Buffer::readUInt16LE", &[_]Param{ .{ .name = "offset" } } },
    .{ "Zphp\\Buffer::writeUInt16LE", &[_]Param{ .{ .name = "offset" }, .{ .name = "value" } } },
    .{ "Zphp\\Buffer::readUInt16BE", &[_]Param{ .{ .name = "offset" } } },
    .{ "Zphp\\Buffer::writeUInt16BE", &[_]Param{ .{ .name = "offset" }, .{ .name = "value" } } },
    .{ "Zphp\\Buffer::readInt32LE", &[_]Param{ .{ .name = "offset" } } },
    .{ "Zphp\\Buffer::writeInt32LE", &[_]Param{ .{ .name = "offset" }, .{ .name = "value" } } },
    .{ "Zphp\\Buffer::readInt32BE", &[_]Param{ .{ .name = "offset" } } },
    .{ "Zphp\\Buffer::writeInt32BE", &[_]Param{ .{ .name = "offset" }, .{ .name = "value" } } },
    .{ "Zphp\\Buffer::readUInt32LE", &[_]Param{ .{ .name = "offset" } } },
    .{ "Zphp\\Buffer::writeUInt32LE", &[_]Param{ .{ .name = "offset" }, .{ .name = "value" } } },
    .{ "Zphp\\Buffer::readUInt32BE", &[_]Param{ .{ .name = "offset" } } },
    .{ "Zphp\\Buffer::writeUInt32BE", &[_]Param{ .{ .name = "offset" }, .{ .name = "value" } } },
    .{ "Zphp\\Buffer::readInt64LE", &[_]Param{ .{ .name = "offset" } } },
    .{ "Zphp\\Buffer::writeInt64LE", &[_]Param{ .{ .name = "offset" }, .{ .name = "value" } } },
    .{ "Zphp\\Buffer::readInt64BE", &[_]Param{ .{ .name = "offset" } } },
    .{ "Zphp\\Buffer::writeInt64BE", &[_]Param{ .{ .name = "offset" }, .{ .name = "value" } } },
    .{ "Zphp\\Buffer::readFloat32LE", &[_]Param{ .{ .name = "offset" } } },
    .{ "Zphp\\Buffer::writeFloat32LE", &[_]Param{ .{ .name = "offset" }, .{ .name = "value" } } },
    .{ "Zphp\\Buffer::readFloat32BE", &[_]Param{ .{ .name = "offset" } } },
    .{ "Zphp\\Buffer::writeFloat32BE", &[_]Param{ .{ .name = "offset" }, .{ .name = "value" } } },
    .{ "Zphp\\Buffer::readFloat64LE", &[_]Param{ .{ .name = "offset" } } },
    .{ "Zphp\\Buffer::writeFloat64LE", &[_]Param{ .{ .name = "offset" }, .{ .name = "value" } } },
    .{ "Zphp\\Buffer::readFloat64BE", &[_]Param{ .{ .name = "offset" } } },
    .{ "Zphp\\Buffer::writeFloat64BE", &[_]Param{ .{ .name = "offset" }, .{ .name = "value" } } },
});

pub fn get(name: []const u8) ?[]const Param {
    return zphp_only.get(name) orelse generated.map.get(name);
}
