# Buffers

A `Zphp\Buffer` is a fixed-length block of bytes. It differs from a string in two ways: its bytes can be changed in place, and it moves to another thread instead of being copied.

When a string is passed to a worker, zphp copies it into the worker and copies the result back, so the cost grows with the size of the data. A buffer's bytes change owner without being copied, so passing one costs about the same at any size. Round trips to a worker and back on an Apple M4 Pro, with a release build:

| Size | String | Buffer |
|---|---|---|
| 64 KB | 0.029 ms | 0.012 ms |
| 1 MB | 0.20 ms | 0.011 ms |
| 16 MB | 3.9 ms | 0.012 ms |
| 256 MB | 73 ms | 0.016 ms |

The buffer times are the cost of submitting and awaiting a task, with no copying. Use a buffer whenever a worker needs large binary data: file contents, images, audio, compressed archives, or network frames.

## Moving to another thread

Passing a buffer to a worker, returning one from a task, or sending one on a [channel](./channels.md) moves its bytes. The sending thread keeps its `Zphp\Buffer` objects, but they are detached: `isDetached()` returns `true`, and any other method throws `Zphp\TransferException`. Use the buffer that arrives on the other side instead.

```php
<?php
$pool = new Zphp\Pool(workers: 1);

$frame = new Zphp\Buffer(64 << 20);
$header = $frame->slice(0, 16);

$future = $pool->submit(function (Zphp\Buffer $frame) {
    $frame->writeUInt32LE(0, 0xCAFE);
    return $frame;
}, [$frame]);

var_dump($frame->isDetached(), $header->isDetached());
try {
    $frame->length();
} catch (Zphp\TransferException $e) {
    echo $e->getMessage(), "\n";
}

$frame = $future->await();
printf("%x\n", $frame->readUInt32LE(0));
```

```
bool(true)
bool(true)
the buffer was transferred to another thread
cafe
```

Only one thread can use the bytes at a time, so there are no data races and no locks.

## Slices

`slice($offset, $length)` returns a view of part of a buffer without copying. A slice shares its parent's bytes, so a write through either one is visible in both. Omitting the length extends the slice to the end of the buffer.

A slice always refers to the whole block it was cut from. Moving a slice to another thread moves the entire block and detaches the parent and every other slice of it. To send only part of a large buffer, clone the slice first: `clone $buffer->slice(0, 1024)` copies those 1024 bytes into a new, independent buffer.

## Reading and writing

```php
<?php
// a length-prefixed message: 4-byte big-endian length, 2-byte type, payload
$body = '{"user":42}';
$packet = new Zphp\Buffer(6 + strlen($body));
$packet->writeUInt32BE(0, strlen($body));
$packet->writeUInt16BE(4, 7);
$packet->write(6, $body);

$header = $packet->slice(0, 6);
$payload = $packet->slice(6);
echo $header->readUInt32BE(0), ' bytes, type ', $header->readUInt16BE(4), ': ', $payload->toString(), "\n";
```

```
11 bytes, type 7: {"user":42}
```

| Method | Behavior |
|---|---|
| `new Zphp\Buffer($length)` | A buffer of `$length` zero bytes |
| `Zphp\Buffer::fromString($bytes)` | A buffer holding a copy of a string |
| `length()` | The buffer's size in bytes |
| `toString()` | A string copy of the bytes |
| `write($offset, $data)` | Copies a string or another buffer in at `$offset`; overlapping copies are handled |
| `slice($offset, $length = null)` | A view of part of the buffer |
| `readFrom($stream)` | One read from a stream straight into the buffer; returns the bytes read, `0` at end of file, or `false` if the stream cannot be read |
| `writeTo($stream)` | Writes the buffer to a stream; returns the bytes written, or `false` if the stream cannot be written |
| `isDetached()` | Whether the bytes moved to another thread |

`readFrom` and `writeTo` accept anything `fread` and `fwrite` accept, including files, sockets, `php://memory`, and user stream wrappers. Combined with `slice`, they fill or send a region of a buffer without building an intermediate string:

```php
<?php
$in = fopen($path, 'r');
$data = new Zphp\Buffer(filesize($path));
$read = 0;
while ($read < $data->length() && ($n = $data->slice($read)->readFrom($in)) > 0) {
    $read += $n;
}
fclose($in);
```

Fixed-width numbers have a read and a write method for each type:

| Type | Methods |
|---|---|
| 8-bit | `readInt8`, `readUInt8`, `writeInt8`, `writeUInt8` |
| 16-bit | `readInt16LE`, `readInt16BE`, `readUInt16LE`, `readUInt16BE`, and the matching `write` methods |
| 32-bit | `readInt32LE`, `readInt32BE`, `readUInt32LE`, `readUInt32BE`, and the matching `write` methods |
| 64-bit | `readInt64LE`, `readInt64BE`, `writeInt64LE`, `writeInt64BE` |
| Floats | `readFloat32LE`, `readFloat32BE`, `readFloat64LE`, `readFloat64BE`, and the matching `write` methods |

Each read takes an offset. Each write takes an offset and a value. A value outside the type's range, or an offset and width that do not fit inside the buffer, throws `ValueError`. There is no unsigned 64-bit type, because PHP integers are signed.

A buffer never grows. Allocate the size you need, or use `slice` to work with the part that is filled.

## Copies

`clone` gives an independent copy of a buffer's bytes. `serialize()` also copies, so a serialized buffer can be stored or sent over the network and unserialized into a new buffer. Only a transfer between threads moves the bytes.
