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

This worker builds an 8 MB binary file, one 64-bit number per entry, and hands it back without a copy:

```php
<?php
$pool = new Zphp\Pool(workers: 1);

$export = $pool->submit(function (int $count) {
    $data = new Zphp\Buffer($count * 8);
    for ($i = 0; $i < $count; $i++) {
        $data->writeInt64LE($i * 8, $i * $i);
    }
    return $data;
}, [1_000_000]);

$data = $export->await();
echo $data->length(), " bytes\n";

$file = fopen('squares.bin', 'wb');
$data->writeTo($file);
fclose($file);
```

```
8000000 bytes
```

Passing a buffer to a worker, returning one from a task, or sending one on a [channel](./channels.md) moves its bytes. The thread that sent it keeps the `Zphp\Buffer` object, but it is detached: `isDetached()` returns `true`, and any other method throws `Zphp\TransferException`.

```php
<?php
$data = Zphp\Buffer::fromString('some bytes');
$pool->submit(fn(Zphp\Buffer $data) => $data->length(), [$data])->await();

var_dump($data->isDetached()); // bool(true)
$data->toString();             // throws Zphp\TransferException
```

Only one thread can use the bytes at a time, so there are no data races and no locks.

## Slices

`slice($offset, $length)` returns a view of part of a buffer without copying. A slice shares its parent's bytes, so a write through either one is visible in both. Omitting the length extends the slice to the end of the buffer.

```php
<?php
$text = Zphp\Buffer::fromString('hello world');
$first = $text->slice(0, 5);
$first->write(0, 'J');
echo $text->toString(), "\n";
```

```
Jello world
```

A slice always refers to the whole block it was cut from. Moving a slice to another thread moves the entire block and detaches the parent and every other slice of it. To send only part of a large buffer, clone the slice first: `clone $buffer->slice(0, 1024)` copies those 1024 bytes into a new, independent buffer.

## Reading and writing

A PNG file stores the image's width and height as 32-bit big-endian numbers at bytes 16 and 20. This reads them without loading the rest of the file:

```php
<?php
$file = fopen('photo.png', 'rb');
$header = new Zphp\Buffer(24);
$header->readFrom($file);
fclose($file);

echo $header->readUInt32BE(16), ' x ', $header->readUInt32BE(20), "\n";
```

```
640 x 480
```

Writing works the same way. This builds a message with a 4-byte length in front of it, a common format for network protocols:

```php
<?php
$message = '{"user":42}';

$packet = new Zphp\Buffer(4 + strlen($message));
$packet->writeUInt32BE(0, strlen($message));
$packet->write(4, $message);

$length = $packet->readUInt32BE(0);
echo $packet->slice(4, $length)->toString(), "\n";
```

```
{"user":42}
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

`readFrom` and `writeTo` accept anything `fread` and `fwrite` accept, including files, sockets, `php://memory`, and user stream wrappers. Like `fread`, one `readFrom` call can return fewer bytes than the buffer holds, for example from a socket; read into `$buffer->slice($read)` to continue where the last call stopped.

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
