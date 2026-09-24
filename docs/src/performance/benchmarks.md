# Benchmarks

Build with `make release` when measuring performance. Debug builds include allocation diagnostics and are not representative of execution speed.

## Runtime

The runtime suite measures six compute-heavy scripts, taking the best of five runs for each runtime. Times include process startup and the timing harness overhead; startup is not subtracted.

Results on Apple M4 with PHP 8.5.4, without PHP's just-in-time compiler:

| Benchmark | PHP | zphp | zphp/PHP |
|---|---|---|---|
| string_ops | 99 ms | 37 ms | 0.37x |
| array_ops | 81 ms | 43 ms | 0.53x |
| objects | 103 ms | 76 ms | 0.74x |
| closures | 103 ms | 99 ms | 0.96x |
| fibonacci | 171 ms | 260 ms | 1.52x |
| loops | 132 ms | 209 ms | 1.58x |

A ratio below 1 means zphp took less time. These scripts help detect runtime regressions; they do not predict the performance of a framework application.

```sh
make bench
```

Measure your application's actual workload before choosing a deployment configuration.

## Parallel work

A [worker pool](../parallelism/pools.md) runs PHP on several cores at once. Splitting 32 CPU-bound tasks took 240 ms on one worker and 32 ms on eight, on an Apple M4 Pro.

Values passed to and from workers are copied, so large strings cost time in proportion to their size: a 16 MB string takes about 3.9 ms to reach a worker and come back. A [buffer](../parallelism/buffers.md) of any size takes about 0.012 ms, because its bytes move instead of being copied. Pass large binary data to workers as buffers.

`tests/workers/run` measures both.

## HTTP throughput

The HTTP harness uses [wrk](https://github.com/wg/wrk) against a trivial response. Its defaults are four client threads, 100 connections, and ten seconds.

```sh
make release
./benchmarks/serve/wrk_bench
```

It requires wrk and Docker for the nginx and PHP-FPM comparison. Historical measurements used native zphp against linux/amd64 containers under emulation on Apple Silicon. That difference prevents a fair runtime comparison, so those results should not be used as a production speedup claim.

## Formatter

The formatter harness compares the best of ten runs on `benchmarks/sample.php`:

```sh
./benchmarks/fmt
```

It uses GNU-style nanosecond timestamps from `date`, so run it in a compatible environment, such as Linux. It may install comparison tools. Formatters apply different rules, and this benchmark does not establish equivalent output.
