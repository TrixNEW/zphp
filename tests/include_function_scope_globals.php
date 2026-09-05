<?php
// a file included from inside a function gets that function's scope: its
// variables are not globals and must not appear in $GLOBALS, and they are
// released when the function returns
class ScopeProbe {
    public $n;
    function __construct($n) { $this->n = $n; }
    function __destruct() { echo "destruct {$this->n}\n"; }
}
function loadScoped() {
    require __DIR__ . '/include/scoped_vars.php';
    echo "in function: ", $scopedName, " ", $scopedObj->n, "\n";
    var_dump(isset($GLOBALS['scopedObj']), isset($GLOBALS['scopedName']));
}
loadScoped();
echo "after function\n";
var_dump(isset($GLOBALS['scopedObj']), isset($GLOBALS['scopedName']), isset($scopedObj));
require __DIR__ . '/include/scoped_vars.php';
var_dump(isset($GLOBALS['scopedObj']), $GLOBALS['scopedName'], $scopedObj->n);
unset($scopedObj);
var_dump(isset($GLOBALS['scopedObj']));
echo "end\n";
