<?php
// php's stream opens fold `.` and `..` by name before the os sees the path, so
// a missing directory before a `..` does not stop them; stat, listing and the
// other calls that hand the path to the os answer the way the os does, which
// on windows also folds by name

$base = sys_get_temp_dir() . '/zphp_dot_segments_' . getmypid();
@mkdir("$base/real", 0777, true);
file_put_contents("$base/real/f.txt", "hi\n");
file_put_contents("$base/real/i.php", '<?php return 5;');
file_put_contents("$base/real/c.ini", "a = 1\n");
chdir($base);

echo "streams\n";
var_dump(file_get_contents("nope/../real/f.txt"));
var_dump((bool) fopen("nope/../real/f.txt", "r"));
var_dump(file_put_contents("nope/../real/g.txt", "x"));
var_dump(file("nope/../real/f.txt"));
var_dump(md5_file("nope/../real/f.txt"));
var_dump(parse_ini_file("nope/../real/c.ini"));
var_dump(copy("nope/../real/f.txt", "nope/../real/copied.txt"));
var_dump(include "nope/../real/i.php");
var_dump(file_get_contents("real/./sub/../f.txt"));

echo "os\n";
var_dump(file_exists("nope/../real/f.txt"), is_file("nope/../real/f.txt"), is_dir("nope/.."));
var_dump(is_array(@scandir("nope/../real")), is_resource(@opendir("nope/../real")), realpath("nope/../real") !== false);
var_dump(@filesize("nope/../real/f.txt"), @touch("nope/../real/t.txt"), @mkdir("nope/../real/d"));
var_dump(@rename("real/g.txt", "nope/../real/r.txt"), @unlink("nope/../real/g.txt"));
var_dump(file_exists("real/../real/f.txt"), basename(realpath("real/./../real/f.txt")));

echo "written\n";
$names = scandir("$base/real");
echo implode(',', $names), "\n";

foreach (['f.txt', 'g.txt', 'i.php', 'c.ini', 'copied.txt', 'r.txt', 't.txt'] as $name) @unlink("$base/real/$name");
@rmdir("$base/real/d");
rmdir("$base/real");
chdir(sys_get_temp_dir());
rmdir($base);
