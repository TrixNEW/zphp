<?php
// a generator owns its yield-from array until delegation ends
function delegated()
{
    yield from [1, 2, 3];
}

$generator = delegated();
echo $generator->current(), "\n";
$generator->next();
echo $generator->current(), "\n";
$generator->next();
echo $generator->current(), "\n";
$generator->next();
echo $generator->valid() ? "valid\n" : "done\n";
