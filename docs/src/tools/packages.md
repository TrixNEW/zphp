# Package Manager

zphp includes a package manager that uses [Packagist](https://packagist.org/), the same registry that Composer uses. It is not a complete replacement for Composer. Test installed packages with your application.

## Quick start

```
$ zphp add slim/slim
```

This creates a `composer.json`, resolves dependencies, downloads packages, and generates a `vendor/autoload.php` that works with zphp's autoloader.

## Commands

| Command | Description |
|---|---|
| `zphp install` | Install all packages from `composer.json` |
| `zphp add <package>` | Add a package and install it |
| `zphp remove <package>` | Remove a package |
| `zphp packages` | List installed packages |

## composer.json

zphp reads `require`, `require-dev`, and project PSR-4 mappings from `composer.json`:

```json
{
    "require": {
        "slim/slim": "^4.0",
        "slim/psr7": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

## Version constraints

| Constraint | Meaning |
|---|---|
| `^1.2.3` | >=1.2.3, <2.0.0 |
| `~1.2.3` | >=1.2.3, <1.3.0 |
| `>=1.0` | 1.0 or higher |
| `*` | Any version |
| `1.2.3` | Exact version |

## Lock file

`zphp install` writes resolved versions to `zphp.lock`, but resolves dependencies again on each install rather than installing from that lock file. It does not provide Composer-style reproducible installs.

The resolver skips `php` and `ext-*` requirements. Composer scripts, plugins, and the full Composer dependency-resolution behavior are not implemented. Keep using Composer when your project depends on those features.

## Autoloading

The generated `vendor/autoload.php` supports project and package PSR-4 mappings and package `autoload.files` entries. It does not implement every Composer autoload mode.

```php
<?php
require __DIR__ . '/vendor/autoload.php';
```
