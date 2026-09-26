# Standalone Executables

`zphp build --compile` copies the current zphp executable and appends the application to it:

```sh
zphp build --compile public/index.php
./index
```

The executable runs the program in CLI mode, without a separate PHP or zphp installation. Arguments are passed to that program. It does not dispatch `serve` or turn the application into a standalone HTTP server.

## What gets packed

The build packs every file under the project root, not only the entry script. Scripts reached through `require`, `include`, Composer's autoloader, or a path built at runtime are all in the executable, and so are templates, configuration files, and other assets. PHP files are compiled to bytecode during the build, so the executable does not parse them again at startup. A file that fails to compile is packed as source and reports its parse error when it is loaded, as it would under PHP.

The project root is the nearest directory above the entry script that contains a `composer.json`, or the entry script's own directory when there is none. `.git` and `node_modules` directories are never packed.

| Option | Effect |
| --- | --- |
| `--root DIR` | Pack `DIR` instead of the detected project root. The entry script must be inside it. |
| `--exclude PATH` | Leave out a file or directory, given relative to the root. Repeat it for several paths. |
| `--out FILE`, `-o FILE` | Write the executable to `FILE`. By default it is named after the entry script's stem and written in the current directory. The name cannot match a file or directory at the top of the root, since the executable would hide it. |

Exclude files that should not ship inside the binary, such as secrets, local databases, and caches built on the development machine:

```sh
zphp build --compile artisan --exclude .env --exclude storage/framework/cache -o shop
```

## Paths at runtime

The packed files appear under the directory that holds the executable, at the same relative paths they had under the project root. If `./app` was built from a project containing `config/app.php`, then `__DIR__`, `realpath()`, `file_exists()`, and `include` inside the program see that file at `<executable directory>/config/app.php`.

The executable's directory is layered over the packed files. A file that exists on disk there takes precedence over the packed copy, and directory listings (`scandir`, `glob`, `opendir`, `DirectoryIterator`) show the names from both. Packed files are never modified. Writes go to the disk:

- Creating or replacing a file writes it next to the executable, creating the directories above it when they exist only in the pack.
- Appending to or editing a packed file, including opening a packed SQLite database, first copies the packed file to the disk and then changes the copy.
- Deleting or renaming a packed file hides the packed copy until the program exits.

A program that writes logs, compiled templates, or a database therefore works without preparing any directories, and whatever it writes persists next to the executable between runs.

## Deployment requirements

This command does not cross-compile. Deploy to a compatible operating system and architecture.

The output inherits the installed zphp binary's library dependencies. The build prefers static linking for libraries such as OpenSSL and SQLite, but links other libraries dynamically, including database clients and curl. Dynamic dependencies are required even when the PHP program does not call those extensions. The Linux musl release binaries are fully static, so executables built with them have no library dependencies.

Inspect the resulting executable with `ldd ./app` on Linux or `otool -L ./app` on macOS, and install its required libraries on the target machine. Do not assume that the target OS supplies them.
