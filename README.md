# zphp

**A high-performance PHP runtime built in Zig.**

zphp is an experimental PHP 8.x-compatible runtime focused on **speed, low memory usage, and modern deployment**. It combines a custom runtime with a built-in HTTP server, WebSocket support, TLS, HTTP/2, database drivers, cURL bindings, package management, testing, formatting, and standalone compilation.

Built with **Zig**, zphp is designed to give PHP workloads lower-level control, reduced overhead, and a more performance-oriented runtime architecture.

```sh
zphp run app.php
zphp serve app.php --port 8080
zphp build --compile app.php
zphp test
zphp fmt src/*.php
zphp install
```

## Features

* PHP 8.x compatibility
* Runtime written in Zig
* Built-in HTTP server
* HTTP/2, TLS, and WebSockets
* SQLite, MySQL, and PostgreSQL
* cURL bindings
* Composer package support
* Standalone executable compilation
* Performance and low-memory focused architecture

## Comparison

| Task              | PHP                | zphp                   |
| ----------------- | ------------------ | ---------------------- |
| Run script        | `php app.php`      | `zphp run app.php`     |
| HTTP server       | PHP-FPM + nginx    | `zphp serve app.php`   |
| Dependencies      | `composer install` | `zphp install`         |
| Tests             | PHPUnit            | `zphp test`            |
| Formatting        | PHP-CS-Fixer       | `zphp fmt`             |
| Standalone binary | External tooling   | `zphp build --compile` |

## Installation

Download builds from [GitHub Releases](https://github.com/nvms/zphp/releases).

See the [documentation](https://nvms.github.io/zphp/) for build instructions and usage guides.

## Project Status

zphp was originally developed and maintained heavily through AI-assisted development.

The project is now being actively reviewed and hardened with a stronger focus on **security, correctness, memory safety, testing, performance validation, and production readiness**.

AI may still be used as a development tool, but critical runtime code is expected to be reviewed, tested, and independently verified.

zphp is still experimental and should be thoroughly tested before production use.

---

**PHP on the surface. Zig at the core. Built for speed.**
