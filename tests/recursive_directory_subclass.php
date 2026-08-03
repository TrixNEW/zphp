<?php

class NamedRecursiveDirectoryIterator extends RecursiveDirectoryIterator
{
    public function current(): SplFileInfo
    {
        return parent::current();
    }
}

$root = sys_get_temp_dir() . '/zphp_recursive_' . getmypid();
mkdir($root . '/src', 0777, true);
file_put_contents($root . '/composer.json', '{}');
file_put_contents($root . '/src/Greeter.php', '<?php');

$iterator = new RecursiveIteratorIterator(
    new NamedRecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
);
foreach ($iterator as $file) {
    echo substr($file->getPathname(), strlen($root) + 1), "\n";
}

unlink($root . '/src/Greeter.php');
unlink($root . '/composer.json');
rmdir($root . '/src');
rmdir($root);
