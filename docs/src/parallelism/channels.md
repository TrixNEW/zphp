# Channels

A `Zphp\Channel` is a bounded queue that threads use to pass values to each other. Unlike other values, a channel is shared when it crosses to a worker: the main thread and every worker that receives it operate on the same queue. The values sent through it are copied, following the same [rules as task arguments](./pools.md#what-can-cross-between-threads).

Channels let a task keep producing or consuming values while it runs, instead of taking one set of arguments and returning one result. This example streams log lines to two parser tasks and collects the errors they find:

```php
<?php
$lines = new Zphp\Channel(capacity: 100);
$errors = new Zphp\Channel(capacity: 100);

$pool = new Zphp\Pool(workers: 2);
$parsers = [];
for ($i = 0; $i < 2; $i++) {
    $parsers[] = $pool->submit(function (Zphp\Channel $in, Zphp\Channel $out) {
        $seen = 0;
        foreach ($in as $line) {
            $seen++;
            if (preg_match('/^(\S+) ERROR (.*)$/', $line, $m)) {
                $out->send(['time' => $m[1], 'message' => $m[2]]);
            }
        }
        return $seen;
    }, [$lines, $errors]);
}

$log = [
    '10:00:01 INFO started',
    '10:00:02 ERROR disk full',
    '10:00:03 INFO retrying',
    '10:00:04 ERROR disk still full',
];
foreach ($log as $line) {
    $lines->send($line);
}
$lines->close();

$total = array_sum(array_map(fn($f) => $f->await(), $parsers));
$errors->close();

$found = iterator_to_array($errors, false);
usort($found, fn($a, $b) => $a['time'] <=> $b['time']);
foreach ($found as $error) {
    echo "{$error['time']} {$error['message']}\n";
}
echo "$total lines parsed\n";
```

```
10:00:02 disk full
10:00:04 disk still full
4 lines parsed
```

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

`foreach` over a channel receives values until the channel is closed and drained. Several threads can iterate the same channel, and each value goes to exactly one of them, which is how the two parsers above split the log lines between them. Keys count up from zero for each consumer.

The capacity bounds how far a producer can run ahead of its consumers. A producer that fills the channel waits until a consumer makes room, so memory stays bounded even when one side is faster.

## Channels in values

A channel can travel inside any value that crosses threads: in task arguments, in a task's result, or inside a message sent on another channel. A worker can create a channel and return it, and the caller then shares that channel with the worker. The channel stays alive as long as any thread holds it.
