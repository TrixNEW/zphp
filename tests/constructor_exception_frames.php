<?php
// Native TypeError propagation must not resume an outer catch while a new
// opcode is still waiting to discard its constructor's return value.
class ThrowingConstructor {
    public function __construct() { strlen([]); }
}
class OuterConstructor {
    public function __construct() { new ThrowingConstructor(); }
}
class SuccessfulConstructor {
    public function __construct() { echo "constructed\n"; }
}
try {
    new OuterConstructor();
    echo "unreachable\n";
} catch (TypeError $e) {
    echo "caught static\n";
    new SuccessfulConstructor();
}
$name = 'OuterConstructor';
try {
    new $name();
    echo "unreachable\n";
} catch (TypeError $e) {
    echo "caught dynamic\n";
}
// Native callbacks add their own nested execution/handler floors.
spl_autoload_register(function ($name) {
    if ($name === 'LoadOuter') { new LoadInner(); }
    if ($name === 'LoadInner') { new OuterConstructor(); }
});
class AutoloadConstructor {
    public function __construct() { new LoadOuter(); }
}
try {
    new AutoloadConstructor();
    echo "unreachable\n";
} catch (TypeError $e) {
    echo "caught autoload\n";
}
class InternallyCaughtConstructor {
    public function __construct() {
        try { new ThrowingConstructor(); }
        catch (TypeError $e) { echo "caught inside\n"; }
    }
}
new InternallyCaughtConstructor();
new SuccessfulConstructor();
echo "alive\n";
