<?php
function attempt(callable $f): void {
    try { $f(); echo "no error\n"; } catch (Throwable $e) { echo get_class($e), ": ", $e->getMessage(), "\n"; }
}

// fixed length, zero filled, bytes in and out
$b = new Zphp\Buffer(8);
var_dump($b->length(), bin2hex($b->toString()), $b->isDetached());
$b->write(2, "abc");
var_dump(bin2hex($b->toString()));
$s = Zphp\Buffer::fromString("hello world");
var_dump($s->length(), $s->toString());
$empty = new Zphp\Buffer(0);
var_dump($empty->length(), $empty->toString(), $empty->slice(0)->length());

// slices are windows onto the same bytes
$hello = $s->slice(0, 5);
$world = $s->slice(6);
$hello->write(0, "J");
$world->write(0, $hello->slice(1, 2));
var_dump($hello->toString(), $world->toString(), $s->toString(), $world->slice(1, 2)->toString());

// overlapping writes move bytes like memmove
$m = Zphp\Buffer::fromString("abcdefgh");
$m->write(2, $m->slice(0, 5));
var_dump($m->toString());
$m = Zphp\Buffer::fromString("abcdefgh");
$m->write(0, $m->slice(2, 5));
var_dump($m->toString());

// numbers at fixed widths and byte orders
$n = new Zphp\Buffer(16);
$n->writeUInt32LE(0, 0xdeadbeef);
$n->writeUInt32BE(4, 0xdeadbeef);
$n->writeInt16LE(8, -2);
$n->writeInt8(10, -128);
$n->writeUInt8(11, 255);
$n->writeUInt16BE(12, 0x1234);
$n->writeUInt16LE(14, 0x1234);
var_dump(bin2hex($n->toString()));
var_dump(dechex($n->readUInt32LE(0)), dechex($n->readUInt32BE(4)), $n->readInt32LE(0), $n->readInt16LE(8), $n->readUInt16LE(8));
var_dump($n->readInt8(10), $n->readUInt8(10), $n->readInt8(11), $n->readUInt16BE(12), $n->readUInt16LE(12), $n->readInt16BE(14));
$n->writeInt64BE(0, PHP_INT_MIN);
$n->writeInt64LE(8, -1);
var_dump($n->readInt64BE(0) === PHP_INT_MIN, $n->readInt64LE(8), bin2hex($n->slice(0, 8)->toString()));
$n->writeFloat64LE(0, 1.5);
$n->writeFloat64BE(8, -0.1);
$n->writeFloat32LE(0, 3);
var_dump($n->readFloat32LE(0), $n->readFloat64BE(8), $n->readFloat32BE(8) < 0);
var_dump(unpack("E", $n->slice(8, 8)->toString())[1]);

// bounds, ranges, and types
attempt(fn() => $n->readUInt32LE(13));
attempt(fn() => $n->readUInt8(-1));
attempt(fn() => $n->readUInt8(16));
attempt(fn() => $n->slice(17));
attempt(fn() => $n->slice(4, 13));
attempt(fn() => $n->slice(0, -1));
attempt(fn() => $n->write(15, "ab"));
attempt(fn() => $n->writeUInt8(0, 256));
attempt(fn() => $n->writeInt8(0, -129));
attempt(fn() => $n->writeUInt32LE(0, -1));
attempt(fn() => $n->writeUInt8(0, "1"));
attempt(fn() => $n->writeFloat64LE(0, "x"));
attempt(fn() => $n->readUInt8("0"));
attempt(fn() => $n->write(0, 5));
attempt(fn() => new Zphp\Buffer(-1));
attempt(fn() => new Zphp\Buffer("4"));
attempt(fn() => Zphp\Buffer::fromString(5));
attempt(fn() => $n->readUInt8(PHP_INT_MAX));
attempt(fn() => $n->slice(PHP_INT_MAX, PHP_INT_MAX));
attempt(fn() => (string) $n);

// a clone owns a copy of its window
$orig = Zphp\Buffer::fromString("0123456789");
$part = clone $orig->slice(3, 4);
$part->write(0, "X");
var_dump($part->toString(), $orig->toString());

// serialize() copies bytes; a region reference only means something to a receiving thread
$copy = unserialize(serialize($orig->slice(5)));
$copy->write(0, "Y");
var_dump($copy->toString(), $orig->toString());
attempt(fn() => unserialize('O:11:"Zphp\Buffer":3:{s:6:"region";i:0;s:6:"offset";i:0;s:6:"length";i:1;}'));
attempt(fn() => unserialize('O:11:"Zphp\Buffer":1:{s:4:"what";i:0;}'));

// streams read straight into a window and write straight out of one
$mem = fopen("php://memory", "w+");
fwrite($mem, "stream bytes");
rewind($mem);
$r = new Zphp\Buffer(20);
var_dump($r->slice(2, 6)->readFrom($mem), $r->slice(8)->readFrom($mem), $r->slice(0, 1)->readFrom($mem));
var_dump(bin2hex($r->toString()));
$path = tempnam(sys_get_temp_dir(), "zbuf");
$f = fopen($path, "w");
var_dump(Zphp\Buffer::fromString("to the file")->slice(3)->writeTo($f));
fclose($f);
var_dump(file_get_contents($path));
$f = fopen($path, "r");
$into = new Zphp\Buffer(64);
var_dump($into->readFrom($f), $into->slice(0, 8)->toString(), $into->readFrom($f));
fclose($f);
unlink($path);
attempt(fn() => $into->readFrom($f));
attempt(fn() => $into->writeTo("nope"));

// across threads: the region moves, every window here detaches
$pool = new Zphp\Pool(workers: 2, bootstrap: __DIR__ . "/worker.php");
$big = new Zphp\Buffer(1000);
for ($i = 0; $i < 1000; $i++) $big->writeUInt8($i, $i % 256);
$view = $big->slice(10, 2);
$other = $big->slice(500);
$f1 = $pool->submit('buffer_stamp', [$big, $view, 0xbeef]);
var_dump($big->isDetached(), $view->isDetached(), $other->isDetached());
attempt(fn() => $big->length());
attempt(fn() => $other->toString());
attempt(fn() => $pool->submit('buffer_sum', [$view]));
attempt(fn() => serialize($big));
$back = $f1->await();
var_dump($back->isDetached(), $back->length(), dechex($back->readUInt16BE(10)), $back->readUInt8(999));
var_dump($pool->submit('buffer_sum', [$back->slice(0, 256)])->await(), $back->isDetached());

// a window names the whole region: sending a slice moves every byte of it
$made = $pool->submit('buffer_make', [6])->await();
$made['tail']->write(0, "!?");
var_dump($made['buf']->toString(), $made['tail']->toString());

// a pack that fails leaves the buffer where it was
$stay = Zphp\Buffer::fromString("still here");
attempt(fn() => $pool->submit('buffer_sum', [$stay, fn() => 1]));
var_dump($stay->isDetached(), $stay->toString());

// buffers ride channels the same way
$in = new Zphp\Channel(4);
$out = new Zphp\Channel(4);
$echo = $pool->submit('buffer_echo', [$in, $out]);
$ring = [];
for ($i = 0; $i < 3; $i++) { $ring[] = $p = new Zphp\Buffer(4); $p->writeUInt8(0, $i * 10); $in->send($p); }
$in->close();
foreach ($out as $got) echo $got->readUInt8(0), " ";
echo "\n";
var_dump($echo->await(), $ring[0]->isDetached());

// a payload nobody receives frees what it carried
$slow = $pool->submit('slow', [50]);
$slow2 = $pool->submit('slow', [50]);
$dropped = $pool->submit('buffer_sum', [new Zphp\Buffer(1 << 20)]);
$dropped->cancel();
attempt(fn() => $dropped->await());
$slow->await(); $slow2->await();

// large transfers do not copy
$huge = new Zphp\Buffer(64 << 20);
$huge->writeUInt8((64 << 20) - 1, 7);
$t = hrtime(true);
for ($i = 0; $i < 20; $i++) $huge = $pool->submit('buffer_stamp', [$huge, $huge->slice(0, 2), $i])->await();
var_dump($huge->readUInt16BE(0), $huge->readUInt8((64 << 20) - 1), (hrtime(true) - $t) / 1e6 < 1000);
$pool->shutdown();
