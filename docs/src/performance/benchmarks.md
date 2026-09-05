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
