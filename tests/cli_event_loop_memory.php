<?php
// mixed transient heaps stay bounded while persistent CLI state survives
class EventPacket
{
    public int $tick;
    public array $payload;
}

function eventValues(int $tick): Generator
{
    yield $tick;
    yield $tick + 1;
}

$state = ['ticks' => 0, 'checksum' => 0];
$before = memory_get_usage();
for ($phase = 0; $phase < 5; $phase++) {
    for ($i = 0; $i < 4000; $i++) {
        $tick = $phase * 4000 + $i;
        $packet = new EventPacket();
        $packet->tick = $tick;
        $packet->payload = [$tick, ['next' => $tick + 1]];
        $handler = static fn(int $value): int => $value + 3;
        foreach (eventValues($tick) as $value) {
            $state['checksum'] += $handler($value);
        }
        if (($tick % 20) === 0) {
            $cycle = [];
            $cycle['self'] = &$cycle;
            unset($cycle);
        }
        $state['ticks']++;
        unset($handler, $packet, $value);
    }
    gc_collect_cycles();
}
$growth = memory_get_usage() - $before;
echo $state['ticks'], "\n";
echo $state['checksum'], "\n";
echo ($growth < 20 * 1024 * 1024) ? "bounded\n" : "unbounded\n";
