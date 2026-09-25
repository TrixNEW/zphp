<?php
// statements that bail out of the fast loop, followed by statements that run
// in it again, in frames with and without named variables
class Probe {
    const LIMIT = 3;
    public static $table = ['a' => 1, 'b' => 2];
    public $str = "`ab` 'cd'";
    public $last = 0;

    public static function two() { return 2; }

    public function negations() {
        $out = [];
        foreach ([0, 2, -1, 0.0, 1.5, '', '0', 'a', [], [0], null, true, false] as $v) {
            $f = $v;
            $r = !$f;
            $out[] = $r ? 'T' : 'F';
            if (!$f) $out[] = 'n';
        }
        return implode('', $out);
    }

    public function afterStaticCall() {
        $f = static::two();
        if (!$f) return 'bad';
        $g = $f + static::LIMIT;
        $h = self::$table['b'];
        return "$f $g $h";
    }

    public function namedReads() {
        $a = static::two();
        $b = $a * 10;
        $name = 'b';
        $c = $$name;
        $vars = compact('a', 'b', 'c');
        ksort($vars);
        return json_encode($vars) . ' ' . count(get_defined_vars());
    }

    public function scan() {
        $tokens = [];
        $len = strlen($this->str);
        for ($this->last = 0; $this->last < $len; ++$this->last) {
            $ch = $this->str[$this->last];
            if ('`' === $ch || "'" === $ch) {
                $quote = $ch;
                $token = $ch;
                while (++$this->last < $len && $this->str[$this->last] !== $quote) {
                    $token .= $this->str[$this->last];
                }
                $token .= $quote;
                $tokens[] = $token;
            }
        }
        return implode('|', $tokens);
    }

    public function dispatch() {
        $out = '';
        foreach (['afterStaticCall', 'scan'] as $m) {
            $res = $this->$m();
            if (!$res) continue;
            $out .= "[$res]";
        }
        return $out;
    }
}

function plain() {
    $f = Probe::two();
    if (!$f) return 'bad';
    $arr = ['x' => $f, 'y' => [1, 2, 3]];
    $s = 0;
    foreach ($arr['y'] as $v) $s += $v;
    return $arr['x'] . ':' . $s . ':' . $arr['y'][2];
}

$p = new Probe;
for ($i = 0; $i < 3; $i++) {
    echo $p->negations(), "\n";
    echo $p->afterStaticCall(), "\n";
    echo $p->namedReads(), "\n";
    echo $p->scan(), "\n";
    echo $p->dispatch(), "\n";
    echo plain(), "\n";
}

// operations that must keep runLoop's semantics once the fast loop resumes
class Base2 {
    public static function make() { return static function () { return static::class; }; }
}
class Child2 extends Base2 {}
function args_seen($a, $b = 10) { return implode(',', func_get_args()); }
function unions($a, $b) { $t = 1; $u = $a + $b; return json_encode($u); }
function casts($csv) { [$x, $y] = explode(',', $csv); $n = (int)$x + (int)$y; return $n; }
function offsets($s) { $n = 0; $r = [strpos($s, 'a', -1), strpos($s, 'c', -2), strrpos($s, 'a', 1), substr($s, 1, -1)]; return json_encode($r); }
$counter = 0;
function bump() { global $counter; $counter++; }
for ($i = 0; $i < 3; $i++) {
    echo args_seen('x'), ' ', args_seen('y', 'z'), "\n";
    echo unions(['a' => 1], ['a' => 9, 'b' => 2]), "\n";
    echo casts('3,4'), "\n";
    echo offsets('abcabc'), "\n";
    $cb = Child2::make();
    echo $cb(), ' ', Base2::make()(), "\n";
    bump();
    $local = $counter * 2;
    echo $GLOBALS['local'], ' ', $GLOBALS['counter'], "\n";
    try { echo strpos('abc', 'a', 9); } catch (ValueError $e) { echo "ValueError\n"; }
}

// fused local arithmetic keeps set_local semantics: references, named
// variables, operand checks, and the global scope's other views
function fusedRefs() { $s = 0; $t = &$s; for ($j = 1; $j < 4; $j++) { $s += $j; } $x = 0; $y = &$x; for ($k = 0; $k < 3; $k++) { $x++; } return "$s $t $x $y"; }
function fusedMix() { $a = 10; $b = 0; for ($i = 0; $i < 5; $i++) { $a--; $b++; $b += $i; $a -= 1; $c = 2; $c *= $i; } return "$a $b $c"; }
function fusedArrays() { $a = ['x' => 1]; $b = ['y' => 2]; for ($i = 0; $i < 1; $i++) { $a += $b; } return json_encode($a); }
function fusedTypeError() { $a = [1]; $b = 2; for ($i = 0; $i < 1; $i++) { try { $a -= $b; } catch (TypeError $e) { return $e->getMessage(); } } return 'no'; }
function fusedNamed() { $n = 0; for ($i = 0; $i < 3; $i++) { $n += $i; } $v = get_defined_vars(); return $v['n'] . compact('i')['i']; }
echo fusedRefs(), '|', fusedMix(), '|', fusedArrays(), '|', fusedTypeError(), '|', fusedNamed(), "\n";
for ($gi = 0; $gi < 3; $gi++) { $gs = ($gs ?? 0) + $gi; }
$gsum = 0;
for ($gj = 0; $gj < 4; $gj++) { $gsum += $gj; }
$gw = 10;
for ($gr = 0; $gr < 3; $gr++) { $gw -= 1; }
function readGlobals() { return $GLOBALS['gi'] . $GLOBALS['gsum'] . $GLOBALS['gj'] . $GLOBALS['gw']; }
echo $GLOBALS['gi'], ' ', $GLOBALS['gsum'], ' ', $GLOBALS['gw'], ' ', readGlobals(), "\n";
// short-circuit and ternary operands are resume points, including inside call
// arguments, by-reference out parameters and write chains
function shortCircuitArgs($subjects)
{
    $out = [];
    $n = 0;
    foreach ($subjects as $i => $s) {
        $ok = preg_match($s !== '' && strlen($s) > 1 ? '/(\d+)/' : '/x/', $s, $m) && isset($m[1]);
        $out[$ok ? 'hit' : 'miss'][] = $ok ? (int) $m[1] : ($s ?: '-');
        $out[$i % 2 === 0 || $n > 2 ? 'even' : 'odd'][$s ?: 'empty'] = ++$n > 1 && $n < 4;
        $t = strlen($s) > 2 ?: str_pad($s, 3, $n % 2 ? 'a' : 'b');
        $out['pad'][] = $t;
    }
    return json_encode($out);
}
echo shortCircuitArgs(['a12', '', 'zz', '7', 'q99q']), "\n";
