<?php

class PathValue
{
    public function __construct(private string $path) {}
    public function __toString(): string { return $this->path; }
}

$source = sys_get_temp_dir() . '/zphp_path_source_' . getmypid();
$target = sys_get_temp_dir() . '/zphp_path_target_' . getmypid();
file_put_contents($source, 'copied');
$path = new PathValue($source);
var_dump(is_file($path), is_dir($path));
$handle = fopen($path, 'r');
echo stream_get_contents($handle), "\n";
fclose($handle);
var_dump(copy($path, $target));
echo file_get_contents($target), "\n";
unlink($source);
unlink($target);
