<?php
function populateGlobals() {
    $GLOBALS['chain_owned']['first'] = ['value' => 1];
    $GLOBALS['deep_owned']['first']['second']['value'] = 2;
    $GLOBALS['false_owned'] = false;
    $GLOBALS['false_owned']['first']['second'] = 3;
}
populateGlobals();
function includeGlobalWrite() {
    require __DIR__ . '/include/globals_direct.php';
    $local['callback'] = function () { return 'alive'; };
    $GLOBALS['local_owned'] = $local;
}
includeGlobalWrite();
gc_collect_cycles();
var_dump($include_global_owned, $local_owned['callback']());
var_dump($chain_owned, $deep_owned, $false_owned);
$copy = $chain_owned;
$GLOBALS['chain_owned']['second'] = 4;
var_dump($copy, $chain_owned);
$deepCopy = $deep_owned;
$GLOBALS['deep_owned']['first']['second']['value'] = 5;
var_dump($deepCopy, $deep_owned);
$GLOBALS['text_owned'] = 'abc';
$GLOBALS['text_owned'][1] = 'z';
var_dump($text_owned);
function replaceGlobalChain() {
    global $chain_owned;
    $GLOBALS['chain_owned']['third'] = 6;
    var_dump($chain_owned);
}
replaceGlobalChain();
gc_collect_cycles();
var_dump($chain_owned);
