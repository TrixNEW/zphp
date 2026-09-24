# Channels

A `Zphp\Channel` is a bounded queue that threads use to pass values to each other. Unlike other values, a channel is shared when it crosses to a worker: the main thread and every worker that receives it operate on the same queue. The values sent through it are copied, following the same [rules as task arguments](./pools.md#what-can-cross-between-threads).

Channels let a task keep receiving values while it runs, instead of getting one set of arguments up front. Here a worker counts error lines while the main thread is still sending them:

```php
<?php
$pool = new Zphp\Pool(workers: 1);
$lines = new Zphp\Channel(capacity: 100);

$counter = $pool->submit(function (Zphp\Channel $lines) {
    $errors = 0;
    foreach ($lines as $line) {
        if (str_contains($line, 'ERROR')) {
            $errors++;
        }
    }
    return $errors;
}, [$lines]);

$log = [
    '10:00:01 INFO server started',
    '10:00:02 ERROR disk full',
    '10:00:03 INFO retrying',
    '10:00:04 ERROR disk still full',
];
foreach ($log as $line) {
    $lines->send($line);
}
$lines->close();

echo $counter->await(), " errors\n";
```

```
2 errors
```

The `foreach` in the worker ends when the main thread closes the channel.

## Sending and receiving

| Method | Behavior |
|---|---|
| `new Zphp\Channel(capacity: 1)` | Creates a channel that holds up to `capacity` values |
| `send($value, $seconds = null)` | Waits while the channel is full; throws `Zphp\TimeoutException` if the timeout passes first |
| `trySend($value)` | Returns `false` instead of waiting when the channel is full |
| `recv($seconds = null)` | Waits for a value; throws `Zphp\TimeoutException` if the timeout passes first |
| `close()` | Stops new sends; values already queued can still be received |
| `isClosed()`, `count()`, `capacity()` | Report the channel's state |

`recv(0)` returns a value only if one is already queued. There is no `tryRecv`, because `null` is a value a channel can carry.

Sending to a closed channel, or receiving from one that is closed and empty, throws `Zphp\ChannelException`. A thread blocked in `send` or `recv` wakes up when another thread closes the channel.

## Iterating

`foreach` over a channel receives values until the channel is closed and drained. Several threads can iterate the same channel, and each value goes to exactly one of them, so submitting the counter above twice would split the lines between two workers. Keys count up from zero for each consumer.

The capacity bounds how far a producer can run ahead of its consumers. A producer that fills the channel waits until a consumer makes room, so memory stays bounded even when one side is faster.

## Waiting on several sources

`Zphp\select($sources, $seconds = null)` waits until any of several channels or futures is ready. It returns a two-element array of the ready source's key and its value, or `null` if the timeout passes first. For a channel, the value is the item it received; the receive happens inside `select`, so when several threads select on the same channel, each item still goes to exactly one of them. For a future, the value is the future itself, finished, so `await()` returns its result or throws its exception without waiting.

This downloads the same file from two mirrors and uses whichever answers first:

```php
<?php
$pool = new Zphp\Pool(workers: 2);
$download = fn(string $url) => file_get_contents($url);

$mirrors = [
    'europe' => $pool->submit($download, ['https://eu.example.com/app.zip']),
    'america' => $pool->submit($download, ['https://us.example.com/app.zip']),
];

$first = Zphp\select($mirrors, 10);
if ($first === null) {
    echo "No mirror answered within 10 seconds\n";
} else {
    [$mirror, $future] = $first;
    echo "Fastest mirror: $mirror\n";
    $zip = $future->await();
}
```

The slower download keeps running in its worker, and `$pool->shutdown()` waits for it.

Channels and futures can be mixed in the same call, such as `Zphp\select(['progress' => $channel, 'done' => $future])` to print progress messages from a task until it finishes.

With futures from several pools, `select` returns them in the order they finish. A finished future stays ready, so remove it from the array once it has been handled, or the next `select` returns it again.

When several sources are ready, `select` rotates which one it checks first, so a busy channel cannot starve the others. A channel that is closed and has nothing left is skipped. If every source is such a channel, `select` throws `Zphp\ChannelException`, the same as `recv` would. A timeout of `0` checks each source once without waiting.

## Channels in values

A channel can travel inside any value that crosses threads: in task arguments, in a task's result, or inside a message sent on another channel. A worker can create a channel and return it, and the caller then shares that channel with the worker. The channel stays alive as long as any thread holds it.
