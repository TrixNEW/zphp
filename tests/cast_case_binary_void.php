<?php
// cast names ignore case and spacing, (binary) is (string), and (void)
// discards a value where php 8.5 allows it
var_dump((INT) "5", ( string ) 5, (Bool) 1, (BINARY) "x", (Array) 1, (DOUBLE) "1.5", (Object) []);

function noisy(string $tag): int {
    echo "called $tag\n";
    return 1;
}

(void) noisy("statement");
(VOID) noisy("upper case");
( void ) noisy("spaced");
for ((void) noisy("for init"), $i = 0; $i < 2; (void) $i++) {
    echo "loop $i\n";
}

class Box {
    public function put(int $v): static {
        echo "put $v\n";
        return $this;
    }
}
(void) (new Box)->put(1)->put(2);
