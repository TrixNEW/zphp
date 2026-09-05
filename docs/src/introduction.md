# zphp

zphp is a PHP runtime written in Zig. It can run PHP scripts, serve HTTP, manage packages, run tests, and format code.

```
$ zphp serve app.php --port 8080
listening on http://0.0.0.0:8080 (14 workers)
```

## What's in the box

| Command | What it does |
|---|---|
| `zphp run <file>` | Execute a PHP script |
| `zphp serve <file>` | HTTP server with TLS, HTTP/2, WebSocket, gzip |
| `zphp test [file]` | Test runner with built-in assertions |
| `zphp fmt <file>...` | Code formatter |
| `zphp build <file>` | Compile to bytecode |
| `zphp build --compile <file>` | Compile to a standalone executable |
| `zphp install` | Install packages from composer.json |
| `zphp add <pkg>` | Add a package |

## How it relates to PHP

zphp implements PHP syntax and standard library behavior, with compatibility tests against PHP and application harnesses for projects including Laravel and WordPress. This does not guarantee that every PHP application or extension works unchanged. See [compatibility](compatibility/same.md) and [differences](compatibility/different.md).

Its built-in server and development tools reduce the number of separate programs needed for supported workflows. They are not drop-in implementations of Composer, PHPUnit, or PHP-CS-Fixer.

Unused values are reclaimed during execution, supporting long-running command-line workloads as well as request-based applications. Like traditional PHP, zphp uses reference counting and cycle collection. See the [memory model](internals/memory-model.md).
