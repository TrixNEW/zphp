<?php
$_SERVER = ['v' => 1];
function sg_read() { return $_SERVER['v'] ?? null; }
function sg_dynamic() { $n = '_SERVER'; $$n = ['v' => 9]; echo $$n['v'], ':', sg_read(), "\n"; }
sg_dynamic();
$arrow = fn() => $_SERVER['v'];
$copy = $_SERVER;
function sg_write() { $_SERVER['v'] = 2; }
sg_write();
echo $copy['v'], ':', sg_read(), ':', $arrow(), "\n";
$old =& $_SERVER;
unset($_SERVER);
echo isset($_SERVER) ? 'bad' : 'absent', ':', array_key_exists('_SERVER', $GLOBALS) ? 'bad' : 'absent', "\n";
$_SERVER = ['v' => 3];
$old['v'] = 4;
echo $old['v'], ':', sg_read(), "\n";
function sg_gen() { yield $_SERVER['v']; yield $_SERVER['v']; }
$g = sg_gen(); echo $g->current(), "\n";
$_SERVER['v'] = 5; $g->next(); echo $g->current(), "\n";
$f = new Fiber(function() { Fiber::suspend($_SERVER['v']); return $_SERVER['v']; });
echo $f->start(), "\n"; $_SERVER['v'] = 6; $f->resume(); echo $f->getReturn(), "\n";
function sg_eval() { eval('$_SERVER["v"] = 7;'); }
sg_eval(); echo sg_read(), "\n";
$globalCopy = $GLOBALS;
$_SERVER['v'] = 8;
echo $globalCopy['_SERVER']['v'], ':', $_SERVER['v'], "\n";
$GLOBALS['_SERVER'] = ['v' => 9]; echo sg_read(), "\n";
unset($GLOBALS['_SERVER']); echo isset($_SERVER) ? 'bad' : 'absent', "\n";
$GLOBALS['_SERVER'] = ['v' => 10]; echo sg_read(), "\n";
function sg_ref(&$x) { $x = ['v' => 11]; }
sg_ref($_SERVER); echo sg_read(), "\n";
$nativeCopy = $_SERVER; array_pop($_SERVER); echo count($_SERVER), ':', count($nativeCopy), "\n";
parse_str('v=12', $_SERVER); echo sg_read(), "\n";
function sg_explicit() { global $_SERVER; $n = '_SERVER'; $$n = ['v' => 13]; }
sg_explicit(); echo sg_read(), "\n";
$_SERVER = 1; ++$_SERVER; $_SERVER++; $_SERVER += 2; echo $_SERVER, "\n";
$_SERVER = 'a'; $_SERVER .= 'b'; echo $_SERVER, "\n";
$_SERVER = ['v' => 14];
function sg_include() { include __DIR__ . '/fixtures/superglobal_include.inc'; }
sg_include(); echo sg_read(), "\n";
