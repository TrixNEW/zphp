# Benchmarks

Build a release binary first. Debug builds are 30 to 50 times slower.

```sh
make release
```

## Real applications

Each harness boots WordPress or Laravel and does real work: rendering, queries, validation, caching. Each is timed as a one-shot CLI run, startup included, taking the best of seven.

```sh
make bench-macro
```

Apple M4, PHP 8.5.4. Across all 25 harnesses zphp takes 0.43x PHP's time (geometric mean), and it is faster on every one:

| Harness | zphp/PHP |
|---|---|
| Laravel JSON API | 0.61x |
| Laravel Blade | 0.62x |
| Laravel Eloquent | 0.88x |
| WordPress transients | 0.95x |

## Runtime

Six compute-heavy scripts, best of five runs under each runtime. PHP runs without JIT.

```sh
make bench
```

Apple M4, PHP 8.5.4:

| Benchmark | PHP | zphp | zphp/PHP |
|---|---|---|---|
| string_ops | 99 ms | 37 ms | 0.37x |
| array_ops | 81 ms | 43 ms | 0.53x |
| objects | 103 ms | 76 ms | 0.74x |
| closures | 103 ms | 99 ms | 0.96x |
| fibonacci | 171 ms | 260 ms | 1.52x |
| loops | 132 ms | 209 ms | 1.58x |

These catch regressions in the interpreter. They don't predict how a framework application performs.

## Comparing two builds

```sh
make bench-compare              # this tree against its merge base with main
make bench-compare BASE=v0.10.0
```

Builds both, runs every benchmark interleaved on the same machine, and prints the ratios. Differences under 5% are noise.

## HTTP

`zphp serve` against nginx with PHP-FPM and `php -S`, all serving `echo "hello"`. Requires [wrk](https://github.com/wg/wrk) and Docker.

```sh
./benchmarks/serve/wrk_bench [duration] [threads] [connections]
```

Defaults are 10 seconds, 4 threads, and 100 connections.

Apple M4, 14 cores, `wrk -t4 -c100 -d10s`:

| Server | Requests/s | Avg latency |
|---|---|---|
| zphp serve | 92,343 | 1.12 ms |
| nginx + PHP-FPM (128 workers) | 42,088 | 50.37 ms |
| php -S | 3,652 | 2.91 ms |

nginx and PHP-FPM ran in Docker under x86 emulation, which slows them down; on native Linux the gap is smaller. `php -S` is PHP's single-threaded development server.

## Formatter

`zphp fmt`, php-cs-fixer, and prettier's PHP plugin on `sample.php` (416 lines), best of ten runs. The tools apply different rules, so this compares speed, not output.

```sh
./benchmarks/fmt
```

Apple M4:

| Tool | Time |
|---|---|
| zphp fmt | 5 ms |
| php-cs-fixer (PSR-12) | 92 ms |
| prettier @prettier/plugin-php | 95 ms |
