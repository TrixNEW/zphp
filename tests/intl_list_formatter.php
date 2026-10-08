<?php
// php 8.5's IntlListFormatter, over ICU's list patterns
$r = new ReflectionClass("IntlListFormatter");
var_dump($r->getConstants(), $r->isFinal());
foreach ([["en_US", 0, 0], ["en_US", 1, 0], ["en_US", 2, 1], ["fr", 0, 0], ["de", 1, 2], ["ja", 0, 0]] as [$l, $t, $w]) {
    $f = new IntlListFormatter($l, $t, $w);
    echo "$l $t $w: ", $f->format(["a", "b", "c"]), " | ", $f->format(["x"]), " | ", var_export($f->format([]), true), " | ", $f->format(["1", "2"]), "\n";
}
$f = new IntlListFormatter("en");
var_dump($f->format([1, 2.5, true, "é"]), $f->getErrorCode(), $f->getErrorMessage());
var_dump($f->format(["k" => "keyed", 7 => "values"]));
foreach ([["en", 9, 0], ["en", 0, 9], ["en", -1, 0]] as [$l, $t, $w]) {
    try {
        new IntlListFormatter($l, $t, $w);
    } catch (ValueError $e) {
        echo $e->getMessage(), "\n";
    }
}
var_dump(@$f->format([[1]]));

// no visible properties, and not cloneable
$plain = new IntlListFormatter("en");
var_dump(get_object_vars($plain), (array) $plain);
try {
    clone $plain;
} catch (Error $e) {
    echo $e->getMessage(), "\n";
}
