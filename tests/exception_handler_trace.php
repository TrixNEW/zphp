<?php
// the exception handler and shutdown functions run after the stack unwinds,
// called by the engine: their traces start at an internal call and {main}

class Custom extends Exception {
    public function __toString(): string { return "custom: " . $this->getMessage(); }
}

set_exception_handler(function (Throwable $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
    echo (new Exception())->getTraceAsString(), "\n";
    echo $e, "\n";
});
register_shutdown_function(function () {
    echo (new Exception())->getTraceAsString(), "\n";
});

function deep() { throw new Custom("handled"); }
deep();
