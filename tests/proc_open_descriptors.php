<?php

// proc_open honors its descriptor spec: ['pipe',...] makes a $pipes entry,
// ['file',...] redirects to/from a file with NO $pipes entry, an absent fd is
// inherited from the parent. these match php's per-fd handling.

// 1. stdout redirected to a FILE: no $pipes[1], the file receives the output
$tmp = sys_get_temp_dir() . '/zphp_pd1_' . getmypid() . '.txt';
$desc = [0 => ['pipe', 'r'], 1 => ['file', $tmp, 'w'], 2 => ['pipe', 'w']];
$p = proc_open('echo hello-to-file', $desc, $pipes);
fclose($pipes[0]);
echo "1 keys: ", implode(',', array_keys($pipes)), "\n";       // 0,2
echo "1 has_pipe1: ", isset($pipes[1]) ? 'y' : 'n', "\n";       // n
fclose($pipes[2]);
proc_close($p);
echo "1 file: ", trim(@file_get_contents($tmp)), "\n";         // hello-to-file
@unlink($tmp);

// 2. partial spec: only a stdout pipe is requested
$p2 = proc_open('echo just-stdout', [1 => ['pipe', 'w']], $pipes2);
echo "2 keys: ", implode(',', array_keys($pipes2)), "\n";      // 1
echo "2 out: ", trim(stream_get_contents($pipes2[1])), "\n";   // just-stdout
fclose($pipes2[1]);
proc_close($p2);

// 3. stdin FROM a file (this used to deadlock under the always-pipe model)
$in = sys_get_temp_dir() . '/zphp_pd3_' . getmypid() . '.txt';
file_put_contents($in, "fed-from-file");
$p3 = proc_open('cat', [0 => ['file', $in, 'r'], 1 => ['pipe', 'w']], $pipes3);
echo "3 keys: ", implode(',', array_keys($pipes3)), "\n";      // 1
echo "3 out: ", trim(stream_get_contents($pipes3[1])), "\n";   // fed-from-file
fclose($pipes3[1]);
proc_close($p3);
@unlink($in);

// 4. inherited stdout: the child writes straight to our stdout, interleaved
//    correctly with our own echo (buffer flushed before spawn)
echo "before-child ";
$p4 = proc_open('echo from-child', [], $pipes4);
proc_close($p4);
echo "after-child\n";

// 5. descriptors above fd 2 are wired and returned by their original key
$p5 = proc_open('echo extra-fd >&3', [3 => ['pipe', 'w']], $pipes5);
echo "5 keys: ", implode(',', array_keys($pipes5)), "\n";
echo "5 out: ", trim(stream_get_contents($pipes5[3])), "\n";
fclose($pipes5[3]);
proc_close($p5);

// 6. socket descriptors are bidirectional
$p6 = proc_open('read line <&3; echo "socket:$line" >&3', [3 => ['socket']], $pipes6);
fwrite($pipes6[3], "roundtrip\n");
echo "6 out: ", trim(fgets($pipes6[3])), "\n";
fclose($pipes6[3]);
proc_close($p6);

// 7. append-mode file redirects preserve existing contents
$append = sys_get_temp_dir() . '/zphp_pd7_' . getmypid() . '.txt';
file_put_contents($append, "first\n");
$p7 = proc_open('echo second', [1 => ['file', $append, 'a']], $pipes7);
proc_close($p7);
echo "7 file: ", str_replace("\n", ',', trim(file_get_contents($append))), "\n";
@unlink($append);

// 8. pty descriptors expose a terminal to the child
$p8 = proc_open('printf tty; test -t 1 && printf yes', [1 => ['pty']], $pipes8);
echo "8 out: ", trim(stream_get_contents($pipes8[1])), "\n";
fclose($pipes8[1]);
proc_close($p8);

echo "done\n";
