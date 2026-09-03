const std = @import("std");

pub fn build(b: *std.Build) void {
    const target = b.standardTargetOptions(.{});
    const optimize = b.standardOptimizeOption(.{});

    // fastLoop compiled as a separate object so LLVM optimizes it
    // independently of runLoop (prevents codegen perturbation)
    const fast_loop_mod = b.createModule(.{
        .root_source_file = b.path("src/fast_loop.zig"),
        .target = target,
        .optimize = optimize,
    });
    fast_loop_mod.link_libc = true;

    const fast_loop_obj = b.addObject(.{
        .name = "fast_loop",
        .root_module = fast_loop_mod,
        .use_llvm = true,
    });

    const known_folders_mod = b.dependency("known_folders", .{
        .target = target,
        .optimize = optimize,
    }).module("known-folders");

    const exe_mod = b.createModule(.{
        .root_source_file = b.path("src/main.zig"),
        .target = target,
        .optimize = optimize,
    });

    exe_mod.linkSystemLibrary("pcre2-8", .{ .preferred_link_mode = .static });
    exe_mod.linkSystemLibrary("sqlite3", .{ .preferred_link_mode = .static });
    exe_mod.linkSystemLibrary("z", .{ .preferred_link_mode = .static });
    exe_mod.linkSystemLibrary("mysqlclient", .{});
    exe_mod.linkSystemLibrary("pq", .{});
    exe_mod.linkSystemLibrary("ssl", .{ .preferred_link_mode = .static, .use_pkg_config = .no });
    exe_mod.linkSystemLibrary("crypto", .{ .preferred_link_mode = .static, .use_pkg_config = .no });
    exe_mod.linkSystemLibrary("nghttp2", .{ .preferred_link_mode = .static });
    exe_mod.linkSystemLibrary("curl", .{});
    exe_mod.addImport("known-folders", known_folders_mod);

    var threaded: std.Io.Threaded = .init_single_threaded;
    defer threaded.deinit();

    const io = threaded.io();

    addLibxml2(io, b, exe_mod);
    addLibicu(io, b, exe_mod);
    addIcuShim(io, b, exe_mod);
    addLibgmp(io, b, exe_mod);
    addLibgd(io, b, exe_mod);
    addLibsodium(io, b, exe_mod);
    addLibldap(io, b, exe_mod);
    addXxhashShim(b, exe_mod);
    addFsSpaceShim(b, exe_mod);
    exe_mod.link_libc = true;
    exe_mod.addObject(fast_loop_obj);

    const exe = b.addExecutable(.{
        .name = "zphp",
        .root_module = exe_mod,
        .use_llvm = true,
    });
    exe.stack_size = 64 * 1024 * 1024;
    b.installArtifact(exe);

    const run_cmd = b.addRunArtifact(exe);
    run_cmd.step.dependOn(b.getInstallStep());
    if (b.args) |args| {
        run_cmd.addArgs(args);
    }

    const run_step = b.step("run", "Run zphp");
    run_step.dependOn(&run_cmd.step);

    const fast_loop_test_obj = b.addObject(.{
        .name = "fast_loop_test",
        .root_module = fast_loop_mod,
        .use_llvm = true,
    });

    const test_mod = b.createModule(.{
        .root_source_file = b.path("src/main.zig"),
        .target = target,
        .optimize = optimize,
    });

    test_mod.linkSystemLibrary("pcre2-8", .{ .preferred_link_mode = .static });
    test_mod.linkSystemLibrary("sqlite3", .{ .preferred_link_mode = .static });
    test_mod.linkSystemLibrary("z", .{ .preferred_link_mode = .static });
    test_mod.linkSystemLibrary("mysqlclient", .{});
    test_mod.linkSystemLibrary("pq", .{});
    test_mod.linkSystemLibrary("ssl", .{ .preferred_link_mode = .static, .use_pkg_config = .no });
    test_mod.linkSystemLibrary("crypto", .{ .preferred_link_mode = .static, .use_pkg_config = .no });
    test_mod.linkSystemLibrary("nghttp2", .{ .preferred_link_mode = .static });
    test_mod.linkSystemLibrary("curl", .{});
    addLibxml2(io, b, test_mod);
    addLibicu(io, b, test_mod);
    addIcuShim(io, b, test_mod);
    addLibgmp(io, b, test_mod);
    addLibgd(io, b, test_mod);
    addLibsodium(io, b, test_mod);
    addLibldap(io, b, test_mod);
    addXxhashShim(b, test_mod);
    addFsSpaceShim(b, test_mod);
    test_mod.link_libc = true;
    test_mod.addObject(fast_loop_test_obj);
    test_mod.addImport("known-folders", known_folders_mod);

    const unit_tests = b.addTest(.{
        .root_module = test_mod,
        .use_llvm = true,
    });
    unit_tests.stack_size = 64 * 1024 * 1024;

    const run_unit_tests = b.addRunArtifact(unit_tests);
    const test_step = b.step("test", "Run unit tests");
    test_step.dependOn(&run_unit_tests.step);
}

// libxml2 ships its headers under a libxml2/ subdirectory. pkg-config name is
// libxml-2.0 (not "xml2"), and on macos pkg-config returns the parent include
// dir without the libxml2/ suffix that the headers actually live in. resolve
// the includedir via pkg-config / xml2-config and append libxml2/ explicitly
fn addLibxml2(io: std.Io, b: *std.Build, mod: *std.Build.Module) void {
    mod.linkSystemLibrary("xml2", .{ .use_pkg_config = .no });

    if (pkgConfigVariable(io, b, "libxml-2.0", "includedir")) |inc| {
        const sub = std.fs.path.join(b.allocator, &.{ inc, "libxml2" }) catch return;
        mod.addSystemIncludePath(.{ .cwd_relative = sub });
        mod.addSystemIncludePath(.{ .cwd_relative = inc });
        return;
    }
    if (xml2ConfigIncludeDir(io, b)) |inc| {
        const sub = std.fs.path.join(b.allocator, &.{ inc, "libxml2" }) catch return;
        mod.addSystemIncludePath(.{ .cwd_relative = sub });
        mod.addSystemIncludePath(.{ .cwd_relative = inc });
    }
}

fn pkgConfigVariable(io: std.Io, b: *std.Build, pkg: []const u8, name: []const u8) ?[]const u8 {
    const arg = std.fmt.allocPrint(b.allocator, "--variable={s}", .{name}) catch return null;
    var child = std.process.spawn(io, .{
        .argv = &.{ "pkg-config", arg, pkg },
    }) catch return null;

    const term = child.wait(io) catch return null;
    if (term != .exited or term.exited != 0) return null;

    const stdout_file = child.stdout orelse return null;
    var reader_buf: [1024]u8 = undefined;
    var reader = stdout_file.reader(io, &reader_buf).interface;

    const stdout = reader.allocRemaining(b.allocator, .limited(4096)) catch return null;
    return std.mem.trim(u8, stdout, " \t\r\n");
}

// libicu is split across three libraries (icuuc, icui18n, icudata) with pkg-config
// names icu-uc, icu-i18n. on macos it's keg-only (brew install icu4c) so its
// pkg-config dir must be on PKG_CONFIG_PATH. on alpine, icu-dev / icu-static
fn addLibicu(io: std.Io, b: *std.Build, mod: *std.Build.Module) void {
    mod.linkSystemLibrary("icui18n", .{ .use_pkg_config = .no });
    mod.linkSystemLibrary("icuuc", .{ .use_pkg_config = .no });
    mod.linkSystemLibrary("icudata", .{ .use_pkg_config = .no });
    if (pkgConfigVariable(io, b, "icu-i18n", "libdir")) |lib| {
        mod.addLibraryPath(.{ .cwd_relative = lib });
    }
}

// icu_shim.c wraps every ICU function we use behind a zphp_* name. compiling
// it through the C preprocessor lets libicu's rename macros (u_strFromUTF8 ->
// u_strFromUTF8_77) be applied so the resulting object file links to the right
// versioned symbols. zig's @cImport doesn't apply these renames, which is why
// intl.zig declares the zphp_* symbols as plain externs instead of @cImport-ing
// libicu headers
// vendored xxhash (single-header); compiled inline so xxh128 doesn't add a
// system library dependency on every CI runner
fn addFsSpaceShim(b: *std.Build, mod: *std.Build.Module) void {
    mod.addCSourceFile(.{
        .file = b.path("src/stdlib/fs_space_shim.c"),
        .flags = &.{"-std=c11"},
    });
}

fn addXxhashShim(b: *std.Build, mod: *std.Build.Module) void {
    mod.addCSourceFile(.{
        .file = b.path("src/stdlib/xxhash_shim.c"),
        .flags = &.{"-std=c11"},
    });
}

fn addIcuShim(io: std.Io, b: *std.Build, mod: *std.Build.Module) void {
    var flags = std.ArrayList([]const u8).empty;
    defer flags.deinit(b.allocator);
    flags.append(b.allocator, "-std=c11") catch return;
    if (pkgConfigCflagsIncludes(io, b, "icu-i18n")) |inc| {
        const flag = std.fmt.allocPrint(b.allocator, "-I{s}", .{inc}) catch return;
        flags.append(b.allocator, flag) catch return;
    }
    mod.addCSourceFile(.{
        .file = b.path("src/stdlib/icu_shim.c"),
        .flags = flags.items,
    });

    // MessageFormat shim is C++ since ICU's named-arg MessageFormat is only
    // exposed in the C++ API. link libc++ once for the whole module
    var cpp_flags = std.ArrayList([]const u8).empty;
    defer cpp_flags.deinit(b.allocator);
    cpp_flags.append(b.allocator, "-std=c++17") catch return;
    if (pkgConfigCflagsIncludes(io, b, "icu-i18n")) |inc| {
        const flag = std.fmt.allocPrint(b.allocator, "-I{s}", .{inc}) catch return;
        cpp_flags.append(b.allocator, flag) catch return;
    }
    mod.addCSourceFile(.{
        .file = b.path("src/stdlib/icu_msg_shim.cpp"),
        .flags = cpp_flags.items,
    });
    mod.link_libcpp = true;
}

// libgmp ships a clean pkg-config and uses static `mpz_*` -> `__gmpz_*` macros
// (no per-version renaming). zig's translate-c handles simple macro renames
fn addLibgmp(io: std.Io, b: *std.Build, mod: *std.Build.Module) void {
    mod.linkSystemLibrary("gmp", .{ .use_pkg_config = .no });
    if (pkgConfigCflagsIncludes(io, b, "gmp")) |inc| {
        mod.addSystemIncludePath(.{ .cwd_relative = inc });
    }
    if (pkgConfigVariable(io, b, "gmp", "libdir")) |lib| {
        mod.addLibraryPath(.{ .cwd_relative = lib });
    }

    // shim file: compiled by C compiler so the `mpz_*` -> `__gmpz_*` macros
    // resolve correctly. zig then links against the unversioned zphp_mpz_*
    var flags = std.ArrayList([]const u8).empty;
    defer flags.deinit(b.allocator);
    flags.append(b.allocator, "-std=c11") catch return;
    if (pkgConfigCflagsIncludes(io, b, "gmp")) |inc| {
        const f = std.fmt.allocPrint(b.allocator, "-I{s}", .{inc}) catch return;
        flags.append(b.allocator, f) catch return;
    }
    mod.addCSourceFile(.{
        .file = b.path("src/stdlib/gmp_shim.c"),
        .flags = flags.items,
    });
}

// libgd uses simple unversioned symbols. pkg-config name is "gdlib"
fn addLibgd(io: std.Io, b: *std.Build, mod: *std.Build.Module) void {
    mod.linkSystemLibrary("gd", .{ .use_pkg_config = .no });
    if (pkgConfigCflagsIncludes(io, b, "gdlib")) |inc| {
        mod.addSystemIncludePath(.{ .cwd_relative = inc });
    }
    if (pkgConfigVariable(io, b, "gdlib", "libdir")) |lib| {
        mod.addLibraryPath(.{ .cwd_relative = lib });
    }
}

fn addLibsodium(io: std.Io, b: *std.Build, mod: *std.Build.Module) void {
    mod.linkSystemLibrary("sodium", .{ .use_pkg_config = .no });
    if (pkgConfigCflagsIncludes(io, b, "libsodium")) |inc| {
        mod.addSystemIncludePath(.{ .cwd_relative = inc });
    }
    if (pkgConfigVariable(io, b, "libsodium", "libdir")) |lib| {
        mod.addLibraryPath(.{ .cwd_relative = lib });
    }
}

fn addLibldap(io: std.Io, b: *std.Build, mod: *std.Build.Module) void {
    mod.linkSystemLibrary("ldap", .{ .use_pkg_config = .no });
    mod.linkSystemLibrary("lber", .{ .use_pkg_config = .no });
    if (pkgConfigCflagsIncludes(io, b, "ldap")) |inc| {
        mod.addSystemIncludePath(.{ .cwd_relative = inc });
    }
    if (pkgConfigVariable(io, b, "ldap", "libdir")) |lib| {
        mod.addLibraryPath(.{ .cwd_relative = lib });
    }
}

fn pkgConfigCflagsIncludes(io: std.Io, b: *std.Build, pkg: []const u8) ?[]const u8 {
    var child = std.process.spawn(io, .{
        .argv = &.{ "pkg-config", "--cflags-only-I", pkg },
    }) catch return null;

    const term = child.wait(io) catch return null;
    if (term != .exited or term.exited != 0) return null;

    const stdout_file = child.stdout orelse return null;
    var reader_buf: [1024]u8 = undefined;
    var reader = stdout_file.reader(io, &reader_buf).interface;

    const stdout = reader.allocRemaining(b.allocator, .limited(4096)) catch return null;

    var it = std.mem.tokenizeAny(u8, stdout, " \t\r\n");
    while (it.next()) |tok| {
        if (std.mem.startsWith(u8, tok, "-I")) return tok[2..];
    }
    return null;
}

fn xml2ConfigIncludeDir(io: std.Io, b: *std.Build) ?[]const u8 {
    var child = std.process.spawn(io, .{
        .argv = &.{ "xml2-config", "--cflags" },
    }) catch return null;

    const term = child.wait(io) catch return null;
    if (term != .exited or term.exited != 0) return null;

    const stdout_file = child.stdout orelse return null;
    var reader_buf: [1024]u8 = undefined;
    var reader = stdout_file.reader(io, &reader_buf).interface;

    const stdout = reader.allocRemaining(b.allocator, .limited(4096)) catch return null;

    var it = std.mem.tokenizeAny(u8, stdout, " \t\r\n");
    while (it.next()) |tok| {
        if (std.mem.startsWith(u8, tok, "-I")) return tok[2..];
    }

    return null;
}
