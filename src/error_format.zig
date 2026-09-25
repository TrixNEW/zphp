const std = @import("std");
const Chunk = @import("pipeline/bytecode.zig").Chunk;
const SourceLocation = @import("pipeline/bytecode.zig").SourceLocation;
const Ast = @import("pipeline/ast.zig").Ast;
const Token = @import("pipeline/token.zig").Token;
const Value = @import("runtime/value.zig").Value;
const VM = @import("runtime/vm.zig").VM;
const Diagnostic = @import("pipeline/compiler.zig").Diagnostic;

const Writer = std.ArrayListUnmanaged(u8);

fn write(buf: *Writer, alloc: std.mem.Allocator, data: []const u8) void {
    buf.appendSlice(alloc, data) catch {};
}

fn writeFmt(buf: *Writer, alloc: std.mem.Allocator, comptime fmt: []const u8, args: anytype) void {
    std.fmt.format(buf.writer(alloc), fmt, args) catch {};
}

fn writeSourceSnippet(buf: *Writer, alloc: std.mem.Allocator, source: []const u8, loc: SourceLocation, highlight_len: u32) void {
    // bytecode mode has empty source and loc.column == 0. nothing to render,
    // and `loc.column - 1` would overflow a u32 below
    if (source.len == 0 or loc.column == 0) return;

    const gutter_width = digitCount(loc.line + 1);

    if (loc.line > 1) {
        if (findLineByNumber(source, loc.line - 1)) |prev| {
            writeGutterLine(buf, alloc, gutter_width, loc.line - 1, prev);
        }
    }

    const current_line_text = source[loc.line_start..loc.line_end];
    writeGutterLine(buf, alloc, gutter_width, loc.line, current_line_text);

    writeGutterBlank(buf, alloc, gutter_width);
    for (0..loc.column - 1) |i| {
        const ch = if (loc.line_start + i < source.len and source[loc.line_start + i] == '\t') @as(u8, '\t') else @as(u8, ' ');
        buf.append(alloc, ch) catch {};
    }
    const caret_len = @max(1, highlight_len);
    for (0..caret_len) |_| buf.append(alloc, '^') catch {};
    write(buf, alloc, "\n");

    if (loc.line_end < source.len) {
        if (findLineByNumber(source, loc.line + 1)) |next| {
            writeGutterLine(buf, alloc, gutter_width, loc.line + 1, next);
        }
    }
}

fn writeGutterLine(buf: *Writer, alloc: std.mem.Allocator, gutter_width: u32, line_num: u32, text: []const u8) void {
    // strip trailing \r so CRLF source files don't emit a literal CR that the
    // terminal interprets as a carriage return, overwriting the gutter
    var trimmed = text;
    if (trimmed.len > 0 and trimmed[trimmed.len - 1] == '\r') {
        trimmed = trimmed[0 .. trimmed.len - 1];
    }
    writeFmt(buf, alloc, " {d: >[1]} | ", .{ line_num, gutter_width });
    write(buf, alloc, trimmed);
    write(buf, alloc, "\n");
}

fn writeGutterBlank(buf: *Writer, alloc: std.mem.Allocator, gutter_width: u32) void {
    for (0..gutter_width + 2) |_| buf.append(alloc, ' ') catch {};
    write(buf, alloc, "| ");
}

fn findLineByNumber(source: []const u8, target_line: u32) ?[]const u8 {
    var line: u32 = 1;
    var start: usize = 0;
    for (source, 0..) |c, i| {
        if (c == '\n') {
            if (line == target_line) return source[start..i];
            line += 1;
            start = i + 1;
        }
    }
    if (line == target_line and start <= source.len) return source[start..];
    return null;
}

fn digitCount(n: u32) u32 {
    if (n == 0) return 1;
    var v = n;
    var count: u32 = 0;
    while (v > 0) : (v /= 10) count += 1;
    return count;
}

fn displayPath(file_path: []const u8) []const u8 {
    if (file_path.len == 0) return "<input>";
    return file_path;
}

pub fn formatParseErrors(alloc: std.mem.Allocator, ast: *const Ast, file_path: []const u8) []const u8 {
    var buf: Writer = .{};
    const path = displayPath(file_path);

    for (ast.errors) |err| {
        const tok = ast.tokens[err.token];
        const loc = Chunk.locationFromOffset(ast.source, tok.start);
        const token_len: u32 = tok.end - tok.start;

        writeFmt(&buf, alloc, "\nParse error: {s} in {s} on line {d}\n\n", .{ errorTagMessage(err.tag), path, loc.line });
        writeSourceSnippet(&buf, alloc, ast.source, loc, token_len);
    }

    return buf.items;
}

// the first syntax error in php's wording: syntax error, unexpected token
// "}", expecting ";". the caller owns the returned bytes
pub fn parseErrorSummary(alloc: std.mem.Allocator, ast: *const Ast) ![]u8 {
    if (ast.errors.len == 0) return alloc.dupe(u8, "syntax error");
    const err = ast.errors[0];
    const tok = ast.tokens[err.token];
    const lexeme = ast.source[tok.start..tok.end];
    const expecting: ?[]const u8 = switch (err.tag) {
        .expected_expression => "expression",
        .expected_semicolon => "\";\"",
        .expected_r_paren => "\")\"",
        .expected_r_brace => "\"}\"",
        .expected_r_bracket => "\"]\"",
        .expected_identifier => "identifier",
        .expected_variable => "variable",
        .expected_colon => "\":\"",
        .unexpected_token => null,
    };
    if (lexeme.len == 0) {
        if (expecting) |e| return std.fmt.allocPrint(alloc, "syntax error, unexpected end of file, expecting {s}", .{e});
        return alloc.dupe(u8, "syntax error, unexpected end of file");
    }
    if (expecting) |e| return std.fmt.allocPrint(alloc, "syntax error, unexpected token \"{s}\", expecting {s}", .{ lexeme, e });
    return std.fmt.allocPrint(alloc, "syntax error, unexpected token \"{s}\"", .{lexeme});
}

// a compile-time fatal in the script being run, reported like a parse error
pub fn formatCompileError(alloc: std.mem.Allocator, ast: *const Ast, file_path: []const u8, diag: Diagnostic) []const u8 {
    var buf: Writer = .{};
    const tok = ast.tokens[diag.token];
    const loc = Chunk.locationFromOffset(ast.source, tok.start);
    writeFmt(&buf, alloc, "\nFatal error: {s} in {s} on line {d}\n\n", .{ diag.message, displayPath(file_path), loc.line });
    writeSourceSnippet(&buf, alloc, ast.source, loc, tok.end - tok.start);
    return buf.items;
}

pub fn compileErrorLine(ast: *const Ast, diag: Diagnostic) i64 {
    return @intCast(Chunk.locationFromOffset(ast.source, ast.tokens[diag.token].start).line);
}

pub fn parseErrorLine(ast: *const Ast) i64 {
    if (ast.errors.len == 0) return 0;
    return @intCast(Chunk.locationFromOffset(ast.source, ast.tokens[ast.errors[0].token].start).line);
}

fn errorTagMessage(tag: Ast.Error.Tag) []const u8 {
    return switch (tag) {
        .expected_expression => "expected expression",
        .expected_semicolon => "expected ';'",
        .expected_r_paren => "expected ')'",
        .expected_r_brace => "expected '}'",
        .expected_r_bracket => "expected ']'",
        .expected_identifier => "expected identifier",
        .expected_variable => "expected variable",
        .expected_colon => "expected ':'",
        .unexpected_token => "unexpected token",
    };
}

pub fn formatRuntimeError(alloc: std.mem.Allocator, vm: *const VM) []const u8 {
    var buf: Writer = .{};

    if (vm.pending_exception) |exc| {
        formatUncaughtException(&buf, alloc, vm, exc);
    } else if (vm.error_msg) |msg| {
        // some error_msg values already have a leading 'Fatal error:' or
        // similar prefix (set via setErrorMsg("Fatal error: ..."). detect
        // and pass through; otherwise treat as a bare message and add PHP's
        // 'PHP Fatal error:' prefix + 'in {path} on line N' suffix
        if (std.mem.startsWith(u8, msg, "PHP ") or std.mem.startsWith(u8, msg, "Fatal error:") or std.mem.startsWith(u8, msg, "\nFatal error:")) {
            write(&buf, alloc, msg);
            appendLocationContext(&buf, alloc, vm);
        } else {
            writeFmt(&buf, alloc, "PHP Fatal error:  {s}", .{msg});
            appendPhpLocationLine(&buf, alloc, vm);
            write(&buf, alloc, "Stack trace:\n");
            writeStackTrace(&buf, alloc, vm);
        }
    } else {
        write(&buf, alloc, "Fatal error: unknown runtime error");
        appendLocationContext(&buf, alloc, vm);
    }

    return buf.items;
}

// minimal PHP-format trailing 'in {path} on line N' line for bare fatals
// (no source snippet, no stack trace - matches 'PHP Fatal error:' output)
fn appendPhpLocationLine(buf: *Writer, alloc: std.mem.Allocator, vm: *const VM) void {
    if (vm.frame_count == 0) {
        write(buf, alloc, "\n");
        return;
    }
    const frame = &vm.frames[vm.frame_count - 1];
    const ip = if (frame.ip > 0) frame.ip - 1 else 0;
    const path = displayPath(vm.file_path);
    if (vm.sourceLocation(frame.chunk, ip)) |loc| {
        writeFmt(buf, alloc, " in {s} on line {d}\n", .{ path, loc.line });
    } else {
        writeFmt(buf, alloc, " in {s}\n", .{path});
    }
}

fn formatUncaughtException(buf: *Writer, alloc: std.mem.Allocator, vm: *const VM, exc: Value) void {
    var class_name: []const u8 = "Exception";
    var message: []const u8 = "";

    if (exc == .object) {
        class_name = exc.object.class_name;
        const msg = exc.object.get("message");
        if (msg == .string) message = msg.string.bytes();
    } else if (exc == .string) {
        message = exc.string.bytes();
    }

    const frame_idx = if (vm.frame_count > 0) vm.frame_count - 1 else 0;
    const frame = &vm.frames[frame_idx];
    const ip = if (frame.ip > 0) frame.ip - 1 else 0;
    // prefer the frame's own file - functions defined in required files
    // should report their own file path, not the top-level script's
    const frame_path: []const u8 = if (frame.func) |fn_| fn_.file_path else "";
    const path_raw: []const u8 = if (frame_path.len > 0) frame_path else vm.file_path;
    const path = displayPath(path_raw);

    // an uncaught ParseError prints the way php reports a syntax error: at
    // the exception's own file and line, with no stack trace
    if (exc == .object and (std.mem.eql(u8, class_name, "ParseError") or std.mem.eql(u8, class_name, "CompileError"))) {
        const file_v = exc.object.get("file");
        const line_v = exc.object.get("line");
        const where = displayPath(if (file_v == .string) file_v.string.bytes() else path_raw);
        const line: i64 = if (line_v == .int) line_v.int else 0;
        const label: []const u8 = if (std.mem.eql(u8, class_name, "ParseError")) "Parse error" else "Fatal error";
        writeFmt(buf, alloc, "PHP {s}:  {s} in {s} on line {d}\n", .{ label, message, where, line });
        if (vm.displayErrorsEnabled()) writeFmt(buf, alloc, "\n{s}: {s} in {s} on line {d}\n", .{ label, message, where, line });
        return;
    }

    // uncatchable fatals (e.g. execution-time exceeded) are formatted as
    // bare fatals without the "Uncaught Class:" prefix or stack trace - this
    // matches how PHP prints `Maximum execution time of N seconds exceeded`
    if (vm.uncatchable_fatal) {
        if (vm.sourceLocation(frame.chunk, ip)) |loc| {
            writeFmt(buf, alloc, "\nFatal error: {s} in {s} on line {d}\n", .{ message, path, loc.line });
        } else {
            writeFmt(buf, alloc, "\nFatal error: {s} in {s}\n", .{ message, path });
        }
        return;
    }

    // PHP emits the log_errors copy with the 'PHP ' prefix always; the bare
    // 'Fatal error:' display copy is emitted only when display_errors is on.
    // header uses 'in {path}:{line}' (the exception format, not the 'on line N'
    // fatal format). no source-line snippet - the stack trace names the site
    const maybe_loc = vm.sourceLocation(frame.chunk, ip);
    const display_on = vm.displayErrorsEnabled();
    var blocks: u8 = 0;
    while (blocks < 2) : (blocks += 1) {
        if (blocks == 1 and !display_on) break;
        const prefix: []const u8 = if (blocks == 0) "PHP Fatal error:  Uncaught" else "Fatal error: Uncaught";
        if (blocks == 1) write(buf, alloc, "\n");
        if (maybe_loc) |loc| {
            writeFmt(buf, alloc, "{s} {s}: {s} in {s}:{d}\n", .{ prefix, class_name, message, path, loc.line });
        } else {
            writeFmt(buf, alloc, "{s} {s}: {s} in {s}\n", .{ prefix, class_name, message, path });
        }
        write(buf, alloc, "Stack trace:\n");
        writeStackTrace(buf, alloc, vm);
        if (maybe_loc) |loc| {
            writeFmt(buf, alloc, "  thrown in {s} on line {d}\n", .{ path, loc.line });
        } else {
            writeFmt(buf, alloc, "  thrown in {s}\n", .{path});
        }
    }
}

fn appendLocationContext(buf: *Writer, alloc: std.mem.Allocator, vm: *const VM) void {
    if (vm.frame_count == 0) {
        write(buf, alloc, "\n");
        return;
    }
    const frame = &vm.frames[vm.frame_count - 1];
    const ip = if (frame.ip > 0) frame.ip - 1 else 0;
    const source = vm.chunkSource(frame.chunk);
    const path = displayPath(framePath(frame, vm));

    if (vm.sourceLocation(frame.chunk, ip)) |loc| {
        writeFmt(buf, alloc, " in {s} on line {d}\n\n", .{ path, loc.line });
        const token_len: u32 = estimateTokenLength(source, loc);
        writeSourceSnippet(buf, alloc, source, loc, token_len);
        if (vm.frame_count > 1) {
            write(buf, alloc, "\nStack trace:\n");
            writeStackTrace(buf, alloc, vm);
        }
    } else {
        writeFmt(buf, alloc, " in {s}\n", .{path});
    }
}

fn writeStackTrace(buf: *Writer, alloc: std.mem.Allocator, vm: *const VM) void {
    if (vm.frame_count == 0) return;


    var depth: u32 = 0;
    // synthetic depth-0 frame for the throwing native (e.g. random_bytes(-1))
    // when an uncaught exception originated from a native call. matches PHP
    // which always includes the native in the stack trace at #0
    if (vm.pending_native_name) |nname| {
        const top = &vm.frames[vm.frame_count - 1];
        const top_ip = if (top.ip > 0) top.ip - 1 else 0;
        const top_path = framePath(top, vm);
        const top_display = displayPath(top_path);
        write(buf, alloc, "#");
        writeFmt(buf, alloc, "{d} ", .{depth});
        if (vm.sourceLocation(top.chunk, top_ip)) |loc| {
            writeFmt(buf, alloc, "{s}({d}): ", .{ top_display, loc.line });
        } else {
            writeFmt(buf, alloc, "{s}: ", .{top_display});
        }
        // instance-method natives render 'Class->method'; static / plain
        // functions keep the stored 'Class::method' / 'func' form
        if (vm.pending_native_is_instance) {
            if (std.mem.indexOf(u8, nname, "::")) |sep| {
                writeFmt(buf, alloc, "{s}->{s}(", .{ nname[0..sep], nname[sep + 2 ..] });
            } else {
                writeFmt(buf, alloc, "{s}(", .{nname});
            }
        } else {
            writeFmt(buf, alloc, "{s}(", .{nname});
        }
        for (vm.pending_native_args, 0..) |a, ai| {
            if (ai > 0) write(buf, alloc, ", ");
            writeArgValue(buf, alloc, a);
        }
        write(buf, alloc, ")\n");
        depth += 1;
    }

    var i: usize = vm.frame_count - 1;
    while (i >= 1) : ({
        i -= 1;
        depth += 1;
    }) {
        const frame = &vm.frames[i];
        const caller = &vm.frames[i - 1];
        const caller_ip = if (caller.ip > 0) caller.ip - 1 else 0;

        const caller_path = framePath(caller, vm);
        const display = displayPath(caller_path);

        write(buf, alloc, "#");
        writeFmt(buf, alloc, "{d} ", .{depth});
        if (vm.sourceLocation(caller.chunk, caller_ip)) |loc| {
            writeFmt(buf, alloc, "{s}({d}): ", .{ display, loc.line });
        } else {
            writeFmt(buf, alloc, "{s}: ", .{display});
        }
        writeFrameCallee(buf, alloc, vm, frame, i);
        write(buf, alloc, "\n");
    }

    writeFmt(buf, alloc, "#{d} {{main}}\n", .{depth});
}

fn framePath(frame: anytype, vm: *const VM) []const u8 {
    if (frame.script_path.len > 0) return frame.script_path;
    if (frame.func) |f| if (f.file_path.len > 0) return f.file_path;
    return vm.file_path;
}

fn writeFrameCallee(buf: *Writer, alloc: std.mem.Allocator, vm: *const VM, frame: anytype, frame_idx: usize) void {
    const func = frame.func orelse {
        write(buf, alloc, "{main}()");
        return;
    };
    // method names are stored as "Class::method"; split on the separator and
    // pick the call-type from the function's static flag. instance dispatch
    // through fnames stored without the prefix falls back to called_class
    if (std.mem.indexOf(u8, func.name, "::")) |sep| {
        const class = func.name[0..sep];
        const method = func.name[sep + 2 ..];
        const type_str: []const u8 = if (func.is_static) "::" else "->";
        writeFmt(buf, alloc, "{s}{s}{s}(", .{ class, type_str, method });
    } else if (frame.called_class) |cls| {
        const type_str: []const u8 = if (func.is_static) "::" else "->";
        writeFmt(buf, alloc, "{s}{s}{s}(", .{ cls, type_str, func.name });
    } else {
        writeFmt(buf, alloc, "{s}(", .{func.name});
    }
    writeFrameArgs(buf, alloc, vm, frame_idx);
    write(buf, alloc, ")");
}

fn writeFrameArgs(buf: *Writer, alloc: std.mem.Allocator, vm: *const VM, frame_idx: usize) void {
    const ic = vm.ic orelse return;
    if (frame_idx >= ic.arg_counts.len) return;
    const ac = ic.arg_counts[frame_idx];
    if (ac == 0xFF) return;
    const offset: usize = ic.fga_offsets[frame_idx];
    const arg_count: usize = ac;
    if (offset + arg_count > ic.fga_buf.len) return;
    for (0..arg_count) |a| {
        if (a > 0) write(buf, alloc, ", ");
        writeArgValue(buf, alloc, ic.fga_buf[offset + a]);
    }
}

fn writeArgValue(buf: *Writer, alloc: std.mem.Allocator, v: Value) void {
    switch (v) {
        .null => write(buf, alloc, "NULL"),
        .bool => |b| write(buf, alloc, if (b) "true" else "false"),
        .int => |n| writeFmt(buf, alloc, "{d}", .{n}),
        .float => |f| writeFmt(buf, alloc, "{d}", .{f}),
        .string => |s| {
            // PHP truncates long strings to 15 chars + '...'
            if (s.len <= 15) {
                writeFmt(buf, alloc, "'{s}'", .{s.bytes()});
            } else {
                writeFmt(buf, alloc, "'{s}...'", .{s.bytes()[0..15]});
            }
        },
        .array => write(buf, alloc, "Array"),
        .object => |o| writeFmt(buf, alloc, "Object({s})", .{o.class_name}),
        else => write(buf, alloc, "?"),
    }
}

fn estimateTokenLength(source: []const u8, loc: SourceLocation) u32 {
    // bytecode mode has empty source and column == 0, which would underflow below
    if (source.len == 0 or loc.column == 0) return 1;
    const start = loc.line_start + loc.column - 1;
    if (start >= source.len) return 1;

    const c = source[start];
    if (c == '$' or isIdentStart(c)) {
        var end = start + 1;
        while (end < source.len and isIdentChar(source[end])) end += 1;
        return @intCast(end - start);
    }
    if (c == '"' or c == '\'') return 1;
    return 1;
}

fn isIdentStart(c: u8) bool {
    return (c >= 'a' and c <= 'z') or (c >= 'A' and c <= 'Z') or c == '_';
}

fn isIdentChar(c: u8) bool {
    return isIdentStart(c) or (c >= '0' and c <= '9');
}
