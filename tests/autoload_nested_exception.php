<?php
foreach ([false, true] as $objectCallback) {
    $suffix = $objectCallback ? 'Object' : 'Closure';
    $load = function ($name) use ($suffix) {
        if ($name === 'NestedClass' . $suffix) {
            eval('class ' . $name . ' { use NestedTrait' . $suffix . '; }');
        } elseif ($name === 'NestedTrait' . $suffix) {
            eval('trait ' . $name . ' {}');
            throw new RuntimeException('autoload failed ' . $suffix);
        }
    };
    $callback = $objectCallback ? new class($load) {
        public function __construct(private $load) {}
        public function __invoke($name) { ($this->load)($name); }
    } : $load;
    spl_autoload_register($callback);
    try { class_exists('NestedClass' . $suffix); }
    catch (Throwable $e) { echo get_class($e), ': ', $e->getMessage(), "\n"; }
    spl_autoload_unregister($callback);
    echo "alive\n";
}
