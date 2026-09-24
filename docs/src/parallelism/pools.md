# Worker Pools

PHP runs your code on one thread. `Zphp\Pool` runs PHP on several threads at once, so CPU-bound work spreads across cores inside a single process. PHP needs a thread-safe build and the parallel extension for this; zphp has it built in.

Each worker thread owns its own VM for the life of the pool. Workers never share PHP variables. A task's arguments are copied into the worker, and its result is copied back. Code that never creates a pool pays nothing for this.

This computes checksums for three files at the same time:

```php
<?php
$pool = new Zphp\Pool(workers: 4);

$files = ['january.csv', 'february.csv', 'march.csv'];
$checksums = [];
foreach ($files as $file) {
    $checksums[$file] = $pool->submit('md5_file', [$file]);
}

foreach ($checksums as $file => $future) {
    echo $file, ': ', $future->await(), "\n";
}
```

```
january.csv: 56153e6036e3e33e8524168b0803204d
february.csv: 214ec5aa5320ad8d9cde0dd99db917dd
march.csv: e6e5eeeb53c8b7311acfdccbfe60cefd
```

`submit` hands the task to a free worker and returns a `Zphp\Future` right away. `await()` waits for that task and returns what it returned.

Splitting 32 CPU-bound tasks over more workers scales with the number of cores. On an Apple M4 Pro, with a release build, the same work took 240 ms on one worker, 123 ms on two, 63 ms on four, and 32 ms on eight.

## Creating a pool

```php
new Zphp\Pool(workers: 4, bootstrap: __DIR__ . '/worker.php', queue: 1024);
```

| Argument | Default | Meaning |
|---|---|---|
| `workers` | CPU count | Number of worker threads |
| `bootstrap` | none | A script each worker runs once at startup |
| `queue` | 1024 | Tasks that can wait for a free worker |

The bootstrap script is where workers load an autoloader, define functions, or open connections they reuse between tasks. Each worker keeps its globals, static variables, and loaded classes from one task to the next. If the bootstrap throws, the constructor throws `Zphp\PoolException` with its message.

## Submitting tasks

`submit($callable, $args)` queues a task and returns a `Zphp\Future`. The callable is a closure, a function name, `'Class::method'`, or `[ClassName::class, 'method']`. Named functions and classes must exist in the worker, which usually means the bootstrap defines or autoloads them.

```php
<?php
// worker.php
function slugify(string $title): string
{
    return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-');
}
```

```php
<?php
$pool = new Zphp\Pool(workers: 2, bootstrap: __DIR__ . '/worker.php');
echo $pool->submit('slugify', ['Hello, World!'])->await(), "\n";
```

```
hello-world
```

A closure travels with its compiled code and its captured variables. `use` variables, `$this` for a bound closure, and the variables an arrow function reads from the enclosing scope are copied at submit time. Capturing by reference (`use (&$x)`) is refused, because the worker cannot write back into the caller's variable.

When the queue is full, `submit` waits for room. `trySubmit` returns `null` instead, so a producer can shed load rather than block.

## Waiting for results

`$future->await()` blocks until the task finishes and returns its result. An exception thrown in the task is thrown again from `await()`, with the same class, message, and code. If the class only exists in the worker, it arrives as `Zphp\TaskException` with the original class name in the message.

`await($seconds)` gives up after the timeout with `Zphp\TimeoutException`. The task keeps running, and a later `await()` still gets its result. `$future->isDone()` checks without waiting.

```php
<?php
$pool = new Zphp\Pool(workers: 2);
$average = fn(array $values) => intdiv(array_sum($values), count($values));

try {
    $pool->submit($average, [[]])->await();
} catch (DivisionByZeroError $e) {
    echo 'Could not average: ', $e->getMessage(), "\n";
}
```

```
Could not average: Division by zero
```

```php
<?php
$report = $pool->submit(function () {
    sleep(1);
    return 'report ready';
});

try {
    $report->await(0.1);
} catch (Zphp\TimeoutException) {
    echo "still working\n";
}
echo $report->await(), "\n";
```

```
still working
report ready
```

To handle results in completion order instead of submission order, call `$pool->collect($seconds)`. It returns the next finished future, or `null` when nothing finishes within the timeout.

```php
<?php
$pool = new Zphp\Pool(workers: 3);
$build = function (string $name, int $ms) {
    usleep($ms * 1000);
    return $name;
};

$pool->submit($build, ['yearly report', 300]);
$pool->submit($build, ['daily report', 100]);
$pool->submit($build, ['monthly report', 200]);

while ($future = $pool->collect(timeout: 1)) {
    echo $future->await(), " finished\n";
}
```

```
daily report finished
monthly report finished
yearly report finished
```

To wait on futures together with [channels](./channels.md#waiting-on-several-sources), or on futures from more than one pool, use `Zphp\select`.

An event loop can watch `$pool->readiness()` instead of polling. It returns a stream that becomes readable when a completed task is waiting, so it can go into `stream_select()` next to sockets.

## Cancellation and shutdown

`$future->cancel()` removes a task that has not started and returns `true`. A running task is never interrupted. Instead, `Zphp\Task::cancelled()` starts returning `true` inside it, and the task decides when to stop. Awaiting a task that was removed from the queue throws `Zphp\CancelledException`.

```php
<?php
$job = $pool->submit(function () {
    while (!Zphp\Task::cancelled()) usleep(1000);
    return 'stopped early';
});
usleep(20_000);
$job->cancel();
echo $job->await(), "\n";
```

```
stopped early
```

`Zphp\Task::id()` and `Zphp\Task::worker()` identify the current task and worker from inside a task.

`$pool->shutdown()` stops accepting tasks, cancels the queued ones, waits for running tasks, and joins the threads. `shutdown($seconds)` returns `false` if tasks are still running when the timeout passes, and a later `shutdown()` waits for them. Futures stay valid after shutdown.

## What can cross between threads

Arguments and results are copied between VMs. These values can cross:

- `null`, booleans, integers, floats, and strings
- arrays of values that can cross
- objects whose class exists on both sides, with their properties
- [channels](./channels.md), which are shared rather than copied
- [buffers](./buffers.md), which are moved rather than copied

Closures inside arguments or results, generators, fibers, pools, futures, streams and other resources, and objects that wrap a native handle (a PDO connection, a cURL handle) cannot cross. Passing one throws `Zphp\TransferException` naming where it was found, such as `args[0]->connection`. Open files and connections in the worker instead, usually in the bootstrap script.

Copying is proportional to the size of the value. For large binary data, use a [buffer](./buffers.md): its bytes move to the worker without a copy.
