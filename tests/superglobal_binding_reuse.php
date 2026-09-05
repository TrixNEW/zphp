<?php
function reuseSuperglobalBinding($value) {
    global $bindingReuseOther;
    $_GET['binding_reuse'] = $value;
    return $_GET['binding_reuse'];
}
for ($i = 0; $i < 200; ++$i) {
    $snapshot = $_GET;
    if (reuseSuperglobalBinding($i) !== $i) throw new Exception('binding value');
    if ($i && $snapshot['binding_reuse'] !== $i - 1) throw new Exception('snapshot changed');
}
echo "binding reuse ok\n";
