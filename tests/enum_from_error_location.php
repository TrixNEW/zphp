<?php
// errors from an enum's from() carry the calling file and line like any
// other builtin exception
enum Suit: string { case H = 'h'; }
enum Rank: int { case Ace = 1; }
foreach ([fn() => Suit::from('x'), fn() => Rank::from(9), fn() => Suit::from([])] as $call) {
    try {
        $call();
    } catch (Error $e) {
        echo get_class($e), ' | ', $e->getMessage(), ' | ', $e->getLine(), ' | ', basename($e->getFile()), "\n";
    }
}
