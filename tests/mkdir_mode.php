<?php
// mkdir passes $mode to mkdir(2) and the umask narrows it
$base = sys_get_temp_dir() . "/zphp_mkdir_mode_" . getmypid();
umask(022);

function perms($p) {
    clearstatcache();
    return decoct(fileperms($p) & 07777);
}

var_dump(mkdir($base));
echo "default: ", perms($base), "\n";

var_dump(mkdir("$base/private", 0700));
echo "0700: ", perms("$base/private"), "\n";

var_dump(mkdir("$base/a/b/c/", 0750, true));
foreach (["a", "a/b", "a/b/c"] as $p) echo "$p: ", perms("$base/$p"), "\n";

// existing parents are fine, an existing target is not
var_dump(mkdir("$base/a/b/d", 0755, true));
var_dump(@mkdir("$base/a/b/c", 0777, true));
var_dump(@mkdir("$base/a/b/c"));
var_dump(@mkdir("$base/missing/x"));
var_dump(@mkdir("/", 0777, true));

umask(0);
var_dump(mkdir("$base/sticky", 01777));
echo "01777 umask 0: ", perms("$base/sticky"), "\n";
var_dump(mkdir("$base/open"));
echo "default umask 0: ", perms("$base/open"), "\n";
umask(022);

foreach (["sticky", "open", "private", "a/b/c", "a/b/d", "a/b", "a", ""] as $p) rmdir("$base/$p");
var_dump(file_exists($base));
