// the php release zphp reports itself as. zphp tracks the latest stable php,
// so bump this when the compat suite moves to a new release
pub const major = 8;
pub const minor = 5;
pub const release = 11;

pub const string = std.fmt.comptimePrint("{d}.{d}.{d}", .{ major, minor, release });
pub const id = major * 10000 + minor * 100 + release;

const std = @import("std");
