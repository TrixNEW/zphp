<?php
preg_match_all('~(a+)(*MARK:1)|(b+)(*MARK:2)|c(*MARK:3)~', 'aabbc', $m, PREG_SET_ORDER);
var_dump($m);
var_dump(preg_match('/x(*MARK:hit)|y(*MARK:other)/', 'zy', $m2), $m2);
preg_match_all('/(?<w>\w)(*MARK:W)/', 'ab', $m3);
var_dump($m3);
echo preg_replace_callback("/a(*MARK:A)|b(*MARK:B)/", fn($m) => $m["MARK"], "ab"), "
";
