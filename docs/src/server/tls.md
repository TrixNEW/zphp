# TLS and HTTP/2

zphp uses OpenSSL for TLS and nghttp2 for HTTP/2.

## Enabling TLS

Provide a certificate and private key together:

```sh
zphp serve app.php --tls-cert cert.pem --tls-key key.pem --port 8443
```

TLS connections negotiate HTTP/2 through ALPN when the client supports it, with HTTP/1.1 as the fallback. Plain HTTP does not enable HTTP/2.

The HTTP/2 implementation handles streams and HPACK header compression, but its response path does not yet forward custom PHP headers or cookies. Gzip and static-file caching headers are only implemented on the HTTP/1.1 path. Applications that depend on these features should use an HTTP/1.1 backend, for example behind a TLS-terminating reverse proxy.

## Local development

Generate a self-signed certificate:

```sh
openssl req -x509 -newkey rsa:2048 -keyout key.pem -out cert.pem \
    -days 365 -nodes -subj '/CN=localhost'
zphp serve app.php --tls-cert cert.pem --tls-key key.pem --port 8443
```

Test it with `curl -k https://localhost:8443/`. The `-k` flag disables certificate verification; use it only for local testing.

## Deployment

Use a certificate from your certificate authority, with `--tls-cert` pointing to the full chain and `--tls-key` to the private key. Alternatively, terminate TLS at a reverse proxy and forward HTTP/1.1 requests to zphp.
