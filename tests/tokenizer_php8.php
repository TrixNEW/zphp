<?php
function dump(string $code, int $flags = 0): void
{
    echo '== ', json_encode($code), "\n";
    foreach (token_get_all($code, $flags) as $t) {
        echo is_array($t) ? token_name($t[0]) . ' ' . json_encode($t[1]) . ' ' . $t[2] : json_encode($t), "\n";
    }
}

dump("<?php namespace\\Foo; \\Bar\\Baz; A\\B; \\C; list\\x; namespace Foo\\Bar;");
dump('<?php yield  from $x; enum Foo {} enum extends; (int ) $x; ( string) $y; private(set) $z; (binary) $b;');
dump('<?php 0x1F 0b11 0o17 017 1_000 1e3 .5 9223372036854775808 1__0 0x7FFFFFFFFFFFFFFF 0x8000000000000000 1. 1.e2');
dump('<?php $a = &$b; function f(&...$x) {} $c & $d; $e && $f; #[Attr] # c' . "\n" . '/**/ /** d */ /* e');
dump("<?php \$\$a; \${'x'}; \"\\\$x {\$y->z[1]} {\$w}\"; `ls \$d`; b'x'; b\"y \$z\"; 'unterminated");
dump("<?php \$x = <<<EOT\n  a \$b {\$c[1]} \${d} \$e[k] \$f->g\n  EOT;\n\$y = <<<'N'\nraw \$b\nN;\n\$z = <<<E\nE;\n");
dump("<?php \"a \$b[0] \$b[-1] \$c[\$d] \$e->f \$g?->h\";");
dump("html <?php echo 1 ?>\nmore<?= \$x ?> end <?php\r\n\$y;");
dump("<?php\n/* multi\nline */\n\$a\n  ->b\n  ?->c();\nfunction  (\$a) use (&\$b) {};\n\$a->class; \$a::CLASS; <=> ** **= ??= ... ?->");
dump("<?php echo 1; __halt_compiler(); raw \x01 data");
dump('<?php Foo::class; Foo::list(); class A { function list() {} const CLASS = 1, Array = 2; public function &new() {} } foo(array: 1); enum E: string { case Default = "x"; }', TOKEN_PARSE);

try {
    token_get_all('<?php if (', TOKEN_PARSE);
} catch (ParseError $e) {
    echo get_class($e), ' ', $e->getLine(), "\n";
}

class MyToken extends PhpToken
{
    public function describe(): string
    {
        return $this->getTokenName() . '@' . $this->line . ':' . $this->pos;
    }
}

$tokens = MyToken::tokenize('<?php echo $x + 1; // done');
echo get_class($tokens[0]), ' ', count($tokens), "\n";
foreach ($tokens as $token) {
    echo $token->describe(), ' ', json_encode((string) $token), ' ', var_export($token->isIgnorable(), true), "\n";
}
$plus = $tokens[5];
var_dump($plus->is('+'), $plus->is(ord('+')), $plus->is([T_STRING, '+']), $plus->is(T_VARIABLE), $plus->id === ord('+'));
$made = new PhpToken(T_STRING, 'name');
var_dump($made->line, $made->pos, $made->getTokenName(), $made instanceof Stringable);
var_dump(token_name(T_DOUBLE_COLON), token_name(T_PAAMAYIM_NEKUDOTAYIM), token_name(-1));
