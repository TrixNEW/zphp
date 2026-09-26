<?php
// a clone holds its own reference to every string and object in its
// dynamic properties, so either copy can go first

class Tracked { public function __destruct() { echo "tracked gone\n"; } }
$original = new stdClass;
$original->object = new Tracked;
$original->text = str_repeat('x', 10);
$original->list = [str_repeat('y', 3)];
$copy = clone $original;
unset($original);
echo "original gone\n";
echo $copy->text, ' ', $copy->list[0], "\n";
unset($copy);
echo "copy gone\n";

$iterator = IntlBreakIterator::createWordInstance('en_US');
$iterator->setText('hello big world');
$twin = clone $iterator;
$iterator->setText('other text');
unset($iterator);
var_dump($twin->getText());
