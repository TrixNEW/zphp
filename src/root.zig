pub const Value = @import("runtime/value.zig").Value;
pub const PhpArray = @import("runtime/value.zig").PhpArray;
pub const PhpObject = @import("runtime/value.zig").PhpObject;
pub const Generator = @import("runtime/value.zig").Generator;
pub const Fiber = @import("runtime/value.zig").Fiber;
pub const VM = @import("runtime/vm.zig").VM;
pub const NativeContext = @import("runtime/vm.zig").NativeContext;
pub const ClassDef = @import("runtime/vm.zig").ClassDef;
pub const RuntimeError = @import("runtime/vm.zig").RuntimeError;

// fn(*NativeContext, []const Value) RuntimeError!Value
pub const NativeFn = @import("runtime/vm.zig").NativeFn;
