<?php
// an uncaught throwable is reported from the object, php's way: its string
// form, previous ones first, and where it was thrown, even when the frames
// that threw it were inside an included file that is gone by then

echo "before\n";
require __DIR__ . '/include/uncaught/inner.php';
echo "not reached\n";
