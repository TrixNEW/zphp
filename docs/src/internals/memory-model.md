# Memory Model

zphp reclaims unused PHP values during execution, rather than waiting for the script or request to finish. This supports long-running command-line programs whose temporary data is no longer needed after each iteration.

Like traditional PHP, zphp uses reference counting and a cycle collector. Traditional PHP also supports long-running workers; request boundaries are not its only mechanism for reclaiming memory.

## Reclaiming unused values

Reference counting tracks the holders of a value. Replacing a variable or removing an array entry releases that holder. When the last holder disappears, the runtime can reclaim the value. Cleanup is deferred to safe execution boundaries so temporary values remain valid while an expression is being evaluated.

Heap-backed strings and arrays participate in this ownership model, as do objects, generators, and fibers. Variables joined with `&` share a reference cell: storage that lets each alias see the same value. When its last holder disappears, the cell releases its value and becomes available for reuse.

Reference counting alone cannot reclaim a cycle, such as an array containing a reference to itself. zphp's cycle collector detects unreachable cycles. Collection runs automatically at allocation thresholds and can also be requested with `gc_collect_cycles()`.

## Long-running programs

A loop does not need to end its process to release temporary PHP values. For example, replacing `$batch` releases the previous batch when nothing else retains it:

```php
for ($i = 0; $i < 100000; $i++) {
    $batch = [$i, ['next' => $i + 1]];
    processBatch($batch);
    unset($batch);
}
```

This assumes `processBatch()` does not keep the batch in persistent storage. A growing cache or a list that retains every result will still consume increasing memory. Closures that capture values can keep them alive too.

Reclaimed memory can be reused without being returned immediately to the operating system. Internal buffers also retain capacity. A process's reported memory therefore need not fall after `unset()` or cycle collection, and reference counting does not guarantee a fixed memory ceiling for every workload.

The regression suite checks repeated reference creation and object replacement, including reference cycles and suspended execution. These checks test bounded memory for those workloads, not every possible application. See `tests/reference_cell_lifetime.php`, `tests/global_reference_cycle_memory.php`, and `tests/cli_event_loop_memory.php`.

## Request lifecycle

In serve mode, each worker thread owns a persistent VM instance:

1. Before a request, the VM resets and releases the previous request's PHP values.
2. Superglobals such as `$_SERVER` and `$_POST` are populated from the incoming request.
3. The entry file executes from the top and the response is sent.

Ordinary PHP variables do not carry application state between requests. Request reset remains a cleanup boundary in addition to reclamation during execution.

Compiled bytecode is cached across requests. Required files are compiled when first encountered and re-executed from the cache on subsequent requests. Internal buffers retain capacity for reuse.

## Copy-on-write

Like PHP, zphp uses copy-on-write for arrays. Assignment or argument passing can share the underlying data until a write requires separation.

```php
$a = [1, 2, 3];
$b = $a;
$b[0] = 9;

var_dump($a[0]); // int(1)
var_dump($b[0]); // int(9)
```

Ordinary array assignment preserves independent values without immediately copying the array. Explicit references made with `&` share storage instead.

## Environment variables

Workers capture environment variables at startup. `$_ENV` is initialized from that snapshot for each request. Restart workers to pick up changes to the process environment.
