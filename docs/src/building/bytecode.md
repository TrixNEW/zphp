# Bytecode Compilation

`zphp build` compiles a PHP file to serialized bytecode without executing it:

```sh
zphp build app.php
zphp run app.zphpc
```

For a `.php` input, the output replaces that suffix with `.zphpc` in the same directory. For other filenames, `.zphpc` is appended.

Running the bytecode skips parsing and compiling the entry point. This does not bundle files loaded with `include` or `require`, or application assets. Keep those runtime dependencies available at their expected paths.

`zphp serve` accepts a PHP source entry point and retains compiled bytecode across requests; it does not require a separate build step. A `.zphpc` file is for `zphp run`, not `zphp serve`.

For an executable containing the runtime and entry-point bytecode, see [Standalone Executables](./standalone.md).
