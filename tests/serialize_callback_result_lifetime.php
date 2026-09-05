<?php
class SleepChild { public $value = 7; public function __sleep() { return ['value']; } }
class SleepParent {
    public $first;
    public $second = 'kept';
    public function __construct() { $this->first = new SleepChild; }
    public function __sleep() { return ['first', 'second']; }
}
class SerializeParent {
    public function __serialize(): array { return ['first' => new SleepChild, 'second' => 'kept']; }
}
for ($i = 0; $i < 3; $i++) {
    echo serialize(new SleepParent), "\n";
    echo serialize(new SerializeParent), "\n";
}
