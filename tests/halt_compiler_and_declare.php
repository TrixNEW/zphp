<?php
namespace App;

use \ArrayObject;
use \Countable, \Traversable as Walkable;
use function \strlen;

declare(ticks=1): echo "alt declare\n"; enddeclare;
declare(ticks=1) {
    echo "block declare\n";
}

echo get_class(new ArrayObject([])), ' ', strlen('abc'), ' ', Walkable::class, ' ', Countable::class, "\n";

$handle = fopen(__FILE__, 'r');
fseek($handle, __COMPILER_HALT_OFFSET__);
echo json_encode(stream_get_contents($handle)), "\n";
echo __COMPILER_HALT_OFFSET__ === filesize(__FILE__) - strlen("\nstored payload\n"), "\n";

__halt_compiler();
stored payload
