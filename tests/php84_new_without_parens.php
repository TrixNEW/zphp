<?php

class Greeter {
    public function __construct(private string $name = "world") {}
    public function hello(): string { return "hello " . $this->name; }
}
echo new Greeter()->hello(), "\n";
echo new Greeter("PHP")->hello(), "\n";

// property access
class Point {
    public function __construct(public int $x = 5, public int $y = 10) {}
}
echo new Point()->x, "\n";
echo new Point(3, 4)->y, "\n";

// method chaining
class Builder {
    private string $s = "";
    public function add(string $x): static { $this->s .= $x; return $this; }
    public function build(): string { return $this->s; }
}
echo new Builder()->add("a")->add("b")->add("c")->build(), "\n";

// nullsafe method call
class MaybeNull {
    public function value(): string { return "ok"; }
}
echo new MaybeNull()?->value(), "\n";

// static property access
class Tagged {
    public static string $tag = "mytag";
}
echo new Tagged()::$tag, "\n";

// namespaced class
echo new \Greeter("ns")->hello(), "\n";

// array access
class Config {
    public function get(): array { return ["key" => "val"]; }
}
echo new Config()->get()["key"], "\n";

class Wrapper {
    public function wrap(string $s): string { return "[" . $s . "]"; }
}
var_dump(new Wrapper()->wrap("test"));

