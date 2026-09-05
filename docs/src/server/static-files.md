# Static Files

`zphp serve` serves static files automatically from the same directory as your PHP file. Keep private files outside this directory. The static-file handler has no general dotfile or secret-file exclusion, and symlinks can expose files outside the document root.

## How it works

When a request comes in, zphp checks if the path maps to a file on disk in the document root (the directory containing your PHP entry point). If the file exists and isn't a `.php` file, it's served directly. Existing `.php` files execute directly; missing paths fall back to your PHP entry point.

```
project/
  app.php         <- entry point
  style.css       <- served as static file
  script.js       <- served as static file
  images/
    logo.png      <- served as static file
```

```
$ zphp serve project/app.php
```

- `GET /style.css` serves `project/style.css`
- `GET /images/logo.png` serves `project/images/logo.png`
- `GET /anything-else` executes `project/app.php`

## Supported content types

zphp selects `Content-Type` by extension. Common mappings are listed below; HTML, CSS, JavaScript, and JSON also include `charset=utf-8`. Unknown extensions use `application/octet-stream`:

| Extensions | Content-Type |
|---|---|
| `.html`, `.htm` | text/html |
| `.css` | text/css |
| `.js`, `.mjs` | application/javascript |
| `.json` | application/json |
| `.png` | image/png |
| `.jpg`, `.jpeg` | image/jpeg |
| `.gif` | image/gif |
| `.svg` | image/svg+xml |
| `.ico` | image/x-icon |
| `.webp` | image/webp |
| `.woff`, `.woff2` | font/woff, font/woff2 |
| `.pdf` | application/pdf |
| `.wasm` | application/wasm |

## Caching

Over HTTP/1.1, static files are served with:
- **ETag** headers based on file size and modification time in seconds
- **Cache-Control: public, max-age=3600** (1 hour)
- Automatic **304 Not Modified** responses when the client sends a matching `If-None-Match` header

## Compression

Over HTTP/1.1, compressible static files up to 1 MiB are gzip-compressed when the client advertises gzip support.

The HTTP/2 static-file path does not send ETags or Cache-Control, handle conditional requests, or apply gzip compression. Files larger than 10 MiB fall through to PHP handling on that path.
