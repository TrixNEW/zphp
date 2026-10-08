<?php
echo ini_get('memory_limit'), "|";
echo ini_set('memory_limit', '300M'), "|", ini_get('memory_limit'), "|";
echo ini_set('memory_limit', '-1'), "|", ini_get('memory_limit'), "\n";
