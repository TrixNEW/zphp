# Your First Script

## Running a script

Create a file called `app.php`:

```php
<?php

$name = "world";
echo "Hello, $name!\n";

$numbers = [1, 2, 3, 4, 5];
$doubled = [];
foreach ($numbers as $n) {
    $doubled[] = $n * 2;
}
echo implode(", ", $doubled) . "\n";
```

Run it:

```
$ zphp run app.php
Hello, world!
2, 4, 6, 8, 10
```

## Serving an application

Create a file called `server.php`:

```php
<?php

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

echo json_encode([
    'method' => $method,
    'path' => $path,
    'message' => 'Hello from zphp',
]);
```

Serve it:

```
$ zphp serve server.php --port 3000
```

```
$ curl http://localhost:3000/api/hello
{"method":"GET","path":"\/api\/hello","message":"Hello from zphp"}
```

The command starts zphp's built-in HTTP server. See [Serving an Application](../server/serving.md) for the full details.
