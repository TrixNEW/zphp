<?php
// an escaped quote keeps its backslash outside its own kind of string
$v = 1;
echo <<<E
\"a\" \`b\` \$v $v
E;
echo "\n";
echo <<<"E"
\"c\" {$v}
E;
echo "\n";
echo "\"d\" \`e\`", "\n";
