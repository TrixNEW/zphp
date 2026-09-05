<?php
function readServer() { return $_SERVER['REQUEST_URI'] ?? 'missing'; }
function writeServer() { $_SERVER['REQUEST_URI'] = '/function'; }
function replaceServer() { $_SERVER = ['REQUEST_URI' => '/replacement']; }
$_SERVER['REQUEST_URI'] = '/script';
echo readServer(), "\n";
$copy = $_SERVER;
writeServer();
echo readServer(), ':', $_SERVER['REQUEST_URI'], ':', $copy['REQUEST_URI'], "\n";
replaceServer();
echo readServer(), ':', $_SERVER['REQUEST_URI'], "\n";
function removeKey() { unset($_SERVER['REQUEST_URI']); }
removeKey();
echo readServer(), "\n";
$alias =& $_SERVER;
$alias['REQUEST_URI'] = '/alias';
echo readServer(), "\n";
function byref(&$a) { $a = ['REQUEST_URI' => '/byref']; }
byref($_SERVER);
echo readServer(), ':', $alias['REQUEST_URI'], "\n";
function removeServer() { unset($_SERVER); }
removeServer();
echo readServer(), ':', $alias['REQUEST_URI'], "\n";
writeServer();
echo readServer(), ':', $alias['REQUEST_URI'], "\n";
$_GET = ['x' => 'script'];
function getWrite() { $_GET['x'] = 'function'; echo $_GET['x'], "\n"; }
getWrite();
echo $_GET['x'], "\n";
require __DIR__ . '/superglobal_scope_include.inc';
echo readServer(), "\n";
function includeScope() {
    require __DIR__ . '/superglobal_scope_include.inc';
    echo $_SERVER['REQUEST_URI'], "\n";
}
includeScope();
echo readServer(), "\n";
function rebindServer() {
    $local = ['REQUEST_URI' => '/rebound'];
    $_SERVER =& $local;
    $local['REQUEST_URI'] = '/rebound-write';
    echo readServer(), "\n";
}
rebindServer();
echo readServer(), "\n";
$GLOBALS['_SERVER'] = ['REQUEST_URI' => '/GLOBALS'];
echo readServer(), "\n";
function dynamicScope() {
    $name = '_SERVER';
    $$name = ['REQUEST_URI' => '/dynamic'];
    echo readServer(), "\n";
}
dynamicScope();
echo readServer(), "\n";
function explicitGlobal() {
    global $_SERVER;
    $_SERVER['REQUEST_URI'] = '/explicit-global';
}
explicitGlobal();
echo readServer(), "\n";
$closure = function () { $_SERVER['REQUEST_URI'] = '/closure'; };
$closure();
echo readServer(), "\n";
function &serverReference() { return $_SERVER; }
$returned =& serverReference();
$returned['REQUEST_URI'] = '/returned-reference';
echo readServer(), "\n";
$_SESSION = ['value' => 'session'];
function sessionWrite() { $_SESSION['value'] = 'session-function'; }
sessionWrite();
echo $_SESSION['value'], "\n";
function nestedWrite() { $_SERVER['nested']['value'] = 'nested'; }
$_SERVER['nested'] = ['value' => 'original'];
$snapshot = $_SERVER;
nestedWrite();
echo $_SERVER['nested']['value'], ':', $snapshot['nested']['value'], "\n";
writeServer();
echo $GLOBALS['_SERVER']['REQUEST_URI'], "\n";
