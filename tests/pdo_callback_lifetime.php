<?php
$db = new PDO('sqlite::memory:');
$db->sqliteCreateFunction('retained_callback', static fn($v) => strtoupper($v), 1);
gc_collect_cycles();
echo $db->query("SELECT retained_callback('alive')")->fetchColumn(), "\n";
$db->sqliteCreateFunction('retained_callback', static fn($v) => $v . '-replaced', 1);
echo $db->query("SELECT retained_callback('alive')")->fetchColumn(), "\n";
unset($db);
gc_collect_cycles();
echo "done\n";
