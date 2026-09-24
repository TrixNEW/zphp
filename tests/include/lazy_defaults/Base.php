<?php
class LazyBase
{
    protected $mask = LazyValidator::NONE;
    public $flags = [LazyValidator::NONE => 'none', PHP_INT_SIZE => 'size'];
    private $own = LazyValidator::ALL | 2;

    public function mask()
    {
        return [$this->mask, $this->own];
    }
}
