# Worker Pools

PHP runs your code on one thread. `Zphp\Pool` runs PHP on several threads at once, so CPU-bound work spreads across cores inside a single process. PHP needs a thread-safe build and the parallel extension for this; zphp has it built in.

Each worker thread owns its own VM for the life of the pool. Workers never share PHP variables. A task's arguments are copied into the worker, and its result is copied back. Code that never creates a pool pays nothing for this.

```php
<?php
$files = glob(__DIR__ . '/*.bin');

$pool = new Zphp\Pool(workers: 4);

$futures = [];
foreach ($files as $file) {
    $futures[$file] = $pool->submit(fn(string $path) => hash_file('sha256', $path), [$file]);
}

foreach ($futures as $file => $future) {
    echo basename($file), ' ', substr($future->await(), 0, 16), "\n";
}

$pool->shutdown();
```

```
file1.bin 62dfc330b8caee85
file2.bin 53a09fe50e4102c7
file3.bin bc34581d1ae4554e
```

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
// bootstrap.php
function resize(string $path, int $width): string
{
    return "$path resized to {$width}px on worker " . Zphp\Task::worker();
}
```

```php
<?php
$pool = new Zphp\Pool(workers: 2, bootstrap: __DIR__ . '/bootstrap.php');
echo $pool->submit('resize', ['photo.jpg', 800])->await(), "\n";
```

```
photo.jpg resized to 800px on worker 0
```

A closure travels with its compiled code and its captured variables. `use` variables, `$this` for a bound closure, and the variables an arrow function reads from the enclosing scope are copied at submit time. Capturing by reference (`use (&$x)`) is refused, because the worker cannot write back into the caller's variable.

When the queue is full, `submit` waits for room. `trySubmit` returns `null` instead, so a producer can shed load rather than block.

## Waiting for results

`$future->await()` blocks until the task finishes and returns its result. An exception thrown in the task is thrown again from `await()`, with the same class, message, and code. If the class only exists in the worker, it arrives as `Zphp\TaskException` with the original class name in the message.

`await($seconds)` gives up after the timeout with `Zphp\TimeoutException`. The task keeps running, and a later `await()` still gets its result. `$future->isDone()` checks without waiting.

```php
<?php
$pool = new Zphp\Pool(workers: 2);

try {
    $pool->submit(function () { throw new InvalidArgumentException('bad input', 42); })->await();
} catch (InvalidArgumentException $e) {
    echo get_class($e), ': ', $e->getMessage(), ' (', $e->getCode(), ")\n";
}

$slow = $pool->submit(function () { usleep(200_000); return 'done'; });
try {
    $slow->await(0.05);
} catch (Zphp\TimeoutException $e) {
    echo "not yet\n";
}
echo $slow->await(), "\n";
```

```
InvalidArgumentException: bad input (42)
not yet
done
```

To handle results in completion order instead of submission order, call `$pool->collect($seconds)`. It returns the next finished future, or `null` when nothing finishes within the timeout.

```php
<?php
$pool = new Zphp\Pool(workers: 3);
foreach ([300, 100, 200] as $ms) {
    $pool->submit(function (int $ms) { usleep($ms * 1000); return $ms; }, [$ms]);
}
while ($future = $pool->collect(timeout: 1.0)) {
    echo $future->await(), " ms task finished\n";
}
```

```
100 ms task finished
200 ms task finished
300 ms task finished
```

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
