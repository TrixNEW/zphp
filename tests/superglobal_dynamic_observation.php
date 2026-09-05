<?php
$_SERVER = ['v' => 'canonical'];
function observedDynamic() {
    global $_SERVER;
    $name = '_SERVER';
    echo $_SERVER['v'], "\n";
    $$name = ['v' => 'local'];
    echo $$name['v'], ':', $_SERVER['v'], "\n";
    include __DIR__ . '/fixtures/superglobal_dynamic_observation.inc';
    echo $$name['v'], ':', $_SERVER['v'], "\n";
}
observedDynamic();
echo $_SERVER['v'], "\n";
function includedReference() {
    $name = '_SERVER';
    $$name = ['v' => 'dynamic'];
    $local = ['v' => 'reference'];
    include __DIR__ . '/fixtures/superglobal_reference_binding.inc';
    $local['v'] = 'changed';
    echo $$name['v'], ':', $_SERVER['v'], "\n";
}
includedReference();
echo $_SERVER['v'], "\n";
