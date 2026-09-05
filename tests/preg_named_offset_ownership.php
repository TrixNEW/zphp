<?php
function namedMatches($flags) {
    preg_match('/(?<word>[a-z]+)(?<number>[0-9]+)?/', 'hello42', $matches, $flags);
    return $matches;
}
for ($i = 0; $i < 3; $i++) {
    $matches = namedMatches(PREG_OFFSET_CAPTURE);
    gc_collect_cycles();
    var_dump($matches);
}
preg_match_all('/(?<word>[a-z]+)/', 'one two', $all, PREG_OFFSET_CAPTURE);
gc_collect_cycles();
var_dump($all);
preg_match_all('/(?<word>[a-z]+)/', 'one two', $sets, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
gc_collect_cycles();
var_dump($sets);
