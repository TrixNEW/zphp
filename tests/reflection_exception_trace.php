<?php
// Built-in private slots must remain distinct from subclass trace properties.
class TraceException extends Exception { public $trace = 'child'; }
class TraceError extends Error { private $trace = 'child'; public function childTrace() { return $this->trace; } }
class TraceRuntime extends RuntimeException {}
foreach ([new Exception, new Error, new TraceException, new TraceError, new TraceRuntime, new ErrorException] as $e) {
    $root = $e instanceof Exception ? 'Exception' : 'Error';
    $r = new ReflectionProperty($root, 'trace');
    echo get_class($e), "\n";
    var_dump($r->isPrivate(), $r->getType()->getName(), $r->getDefaultValue(), $r->getDeclaringClass()->getName());
    var_dump($r->getValue($e) === $e->getTrace());
    $trace = [['file'=>'synthetic.php', 'line'=>12, 'function'=>'replacement', 'args'=>[]]];
    $r->setAccessible(true);
    $r->setValue($e, $trace);
    var_dump($r->getValue($e) === $e->getTrace());
    echo $e->getTraceAsString(), "\n";
    $copy = $r->getValue($e); $copy[] = [];
    var_dump(count($e->getTrace()));
    try { $r->setValue($e, 'bad'); } catch (TypeError $x) { echo $x->getMessage(), "\n"; }
    $r->setValue($e, []);
    echo $e->getTraceAsString(), "\n";
    if ($e instanceof TraceException) var_dump($e->trace);
    if ($e instanceof TraceError) {
        var_dump($e->childTrace());
        $child = new ReflectionProperty(TraceError::class, 'trace');
        $child->setValue($e, 'changed');
        var_dump($child->getValue($e), $e->childTrace(), $r->getValue($e));
    }
    var_dump(array_key_exists('trace', get_object_vars($e)));
}
foreach (['Exception', 'Error'] as $root) {
    $r = new ReflectionProperty($root, 'trace');
    try { $r->getValue(new stdClass); } catch (ReflectionException $e) { echo $e->getMessage(), "\n"; }
    try { $r->setValue(new stdClass, []); } catch (ReflectionException $e) { echo $e->getMessage(), "\n"; }
    $e = new $root;
    try { echo $e->trace; } catch (Error $x) { echo $x->getMessage(), "\n"; }
}
function nativeTraceError() { strlen([]); }
function nativeTraceException() { json_decode('{', true, 512, JSON_THROW_ON_ERROR); }
foreach (['nativeTraceError', 'nativeTraceException'] as $fn) {
    try { $fn(); } catch (Throwable $e) {
        $r = new ReflectionProperty($e instanceof Exception ? 'Exception' : 'Error', 'trace');
        var_dump($r->getValue($e) === $e->getTrace(), count($e->getTrace()) > 0);
        $r->setValue($e, []);
        var_dump($e->getTrace());
        echo $e->getTraceAsString(), "\n";
    }
}
