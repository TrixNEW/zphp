<?php
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
// line padding so offsets diverge from the including script
function lines_warn(): void
{
    $empty = [];
    echo $empty['missing'] ?? 'default', "\n";
    $x = $empty['k'];
}

function lines_throw(): void
{
    throw new RuntimeException('from include');
}

function lines_typed(int $n): int
{
    return $n;
}

function lines_trace(): array
{
    return array_map(fn($f) => $f['line'] ?? null, debug_backtrace());
}

class LinesThrower
{
    public function __construct()
    {
        lines_throw();
    }
}
