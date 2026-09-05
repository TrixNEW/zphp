<?php
$_SERVER['REQUEST_URI'] = '/include';
echo readServer(), "\n";
replaceServer();
echo $_SERVER['REQUEST_URI'], "\n";
