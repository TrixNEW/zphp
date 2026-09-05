# benchmarks

## runtime

Six benchmarks comparing zphp vs PHP on compute-heavy tasks. Each runs both runtimes and reports best of 5.

```
zig build -Doptimize=ReleaseFast
./benchmarks/runtime/run
```

Requires PHP installed locally. zphp must be built with ReleaseFast - debug builds are 30-50x slower due to safety checks.

- **fibonacci** - recursive fib(32), tests function call overhead
- **loops** - tight integer arithmetic, nested loops with conditionals
- **closures** - closure creation, captures, higher-order composition
- **objects** - class instantiation, method calls, property access
- **array_ops** - array building, filtering, mapping via loops
- **string_ops** - string concatenation in loop, substr_count, str_replace, explode/implode

### Results (Apple M4, PHP 8.5.4 no JIT, zphp ReleaseFast)

| benchmark | php | zphp | ratio |
|---|---|---|---|
| string_ops | 99 ms | 37 ms | 0.37x |
| array_ops | 81 ms | 43 ms | 0.53x |
| objects | 103 ms | 76 ms | 0.74x |
| closures | 103 ms | 99 ms | 0.96x |
| fibonacci | 171 ms | 260 ms | 1.52x |
| loops | 132 ms | 209 ms | 1.58x |

zphp remains faster on the array, string, and object benchmarks. Sequential-array key lookup, fastLoop concat handling, the growable concat-assignment buffer, property slot indices, and inline-cached property access are the main advantages in those workloads.

Closures are approximately even with PHP; Fibonacci and loops are slower in the current ownership-correct runtime. Object, array, generator, and fiber lifetime tracking adds tag checks to general operand-stack operations. A conservative compile-time proof for scalar-only function stacks was implemented and fully tested, but it made Fibonacci and loops slower through VM code-generation perturbation, so it was not shipped. The table reports the measured implementation rather than retaining earlier results from before the ownership model changed.

These six microbenchmarks are code-generation canaries, not the primary performance target. Real WordPress and Laravel harnesses are measured separately with `benchmarks/macro/run`; request and application throughput take priority over an isolated recursive call or arithmetic loop.

## serve

HTTP throughput benchmark comparing `zphp serve` against nginx + php-fpm (the standard production PHP deployment). Uses [wrk](https://github.com/wg/wrk) for load generation.

```
zig build -Doptimize=ReleaseFast
./benchmarks/serve/wrk_bench [duration] [threads] [connections]
```

Defaults: 10s duration, 4 threads, 100 connections. Requires wrk. Requires Docker for the nginx + php-fpm comparison. PHP's built-in server (`php -S`) is included as a baseline but is single-threaded and not a production server.

All servers run the same file: `echo "hello"`.

### Results (Apple M4, 14 cores, wrk -t4 -c100 -d10s)

| server | req/s | avg latency |
|---|---|---|
| zphp serve | 92,343 | 1.12 ms |
| nginx + php-fpm (128 workers) | 42,088 | 50.37 ms |
| php -S (dev only) | 3,652 | 2.91 ms |

zphp is 2.2x higher throughput and 45x lower latency than nginx + php-fpm on the same trivial endpoint.

### Caveats

- nginx + php-fpm runs in Docker with linux/amd64 emulation on Apple Silicon. native Linux performance would be significantly better for php-fpm. on a real Linux x86_64 server, expect the gap to narrow
- zphp runs natively. this is representative of real deployment - zphp is a single binary with a built-in production server
- `php -S` is PHP's built-in development server. single-threaded, not intended for production. included only as a baseline
- this benchmarks I/O and dispatch overhead on a trivial endpoint. real-world PHP with database queries, template rendering, etc. would shift the bottleneck from the server to the application layer

## fmt

Formats `sample.php` (416 lines) with each tool, reports best of 10 runs.

```
./benchmarks/fmt
```

Requires `zig build` first. Installs prettier locally if node/npm is available. Skips php-cs-fixer if php is not installed (use `./php` docker wrapper to run it manually).

### Results (Apple M4)

| tool | best of 10 |
|---|---|
| zphp fmt | 5 ms |
| php-cs-fixer (PSR-12) | 92 ms |
| prettier @prettier/plugin-php | 95 ms |
