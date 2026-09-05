# Standalone Executables

`zphp build --compile` copies the current zphp executable and appends the entry point's compiled bytecode:

```sh
zphp build --compile app.php
./app
```

The output is named after the input file's stem and written in the current working directory. For example, building `src/app.php` produces `./app`.

The executable runs the embedded program in CLI mode, without a separate PHP or zphp installation. Arguments are passed to that program. It does not dispatch `serve` or turn the application into a standalone HTTP server.

## Deployment requirements

This command does not cross-compile. Deploy to a compatible operating system and architecture. Files loaded with `include` or `require` and application assets are not bundled.

The output inherits the installed zphp binary's library dependencies. The build prefers static linking for libraries such as OpenSSL and SQLite, but links other libraries dynamically, including database clients and curl. Dynamic dependencies are required even when the PHP program does not call those extensions.

Inspect the resulting executable with `ldd ./app` on Linux or `otool -L ./app` on macOS, and install its required libraries on the target machine. Do not assume that the target OS supplies them.
