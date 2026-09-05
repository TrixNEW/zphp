# WebSockets

`zphp serve` supports WebSocket upgrades over HTTP/1.1. Declare `ws_onMessage` in the entry point so the server detects it when compiling the application. Defining it only in a runtime include does not enable WebSocket handling.

```php
<?php
function ws_onOpen($conn) {
    $conn->send('welcome');
}

function ws_onMessage($conn, $message) {
    $conn->send('echo: ' . $message);
}

function ws_onClose($conn) {}
```

```sh
zphp serve ws_app.php --port 8080
```

Connect to `ws://localhost:8080/`. With TLS configured, use `wss://localhost:8080/` instead. WebSocket upgrades are not implemented on the HTTP/2 path.

## Connection object

Handlers receive a `WebSocketConnection` object. `ws_onOpen` and `ws_onClose` are optional.

| Method | Description |
|---|---|
| `$conn->send($message)` | Send a text message |
| `$conn->close()` | Close the connection |

## State limitations

The entry point runs on the first WebSocket connection handled by each worker. PHP globals are worker-local, so an array of clients cannot broadcast across all workers.

Regular HTTP requests can use the same port, but they reset the same worker VM used by WebSocket callbacks. Do not rely on PHP globals or connection objects surviving mixed HTTP and WebSocket traffic. Use a separate WebSocket process rather than treating this as a shared-state chat server.
