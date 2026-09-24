<?php
trait Smart {
    public function __get($name) { throw new LogicException("undeclared $name"); }
}
class Def { use Smart; public function setName($n) { if ($this->missing) {} return $this; } }
class Builder { public function add() { $d = new Def(); $d->setName('x'); } }
class Compiler { public function process(array $exts) { foreach ($exts as $e) { $e(); } } }
class App {
    public function run() {
        try {
            $exitCode = $this->doRun();
        } catch (\Throwable $e) {
            echo "caught: ", $e->getMessage(), "\n";
            $exitCode = 1;
        } finally {
            echo "finally\n";
        }
        echo "after finally, exit $exitCode\n";
        return $exitCode;
    }
    private function doRun() {
        $c = new Compiler();
        $c->process([fn() => (new Builder)->add()]);
        return 0;
    }
}
(function () { (new App)->run(); })();
echo "returned from run\n";
