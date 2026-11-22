# 🎬 php-ytdlp-wrapper

A small, typed wrapper around the [`yt-dlp`](https://github.com/yt-dlp/yt-dlp) CLI for **PHP
8.4–8.6**, with a ready-to-use **Symfony 7.4/8 bundle**.

The wrapper runs `yt-dlp` through `symfony/process` without a shell, maps PHP arrays to command-line
options, parses JSON-lines metadata, and gives you a predictable result and error model.

## ✨ Features

- Safe array-mode process execution through `symfony/process`
- Immutable request and result objects
- Download, metadata, raw JSON, and low-level execution helpers
- Options, flags, and extra arguments mapped to `yt-dlp` CLI arguments
- Automatic `--` end-of-options marker before URLs
- Typed error model: `YtDlpException`, `BinaryNotFoundException`, `ProcessFailedException`
- Symfony 7.4/8 configuration, service wiring, and interface alias
- PHP 8.4, 8.5, and 8.6 support

## 📋 Requirements

- PHP 8.4, 8.5, or 8.6 at runtime; PHP 8.4.3+ for development (Psalm 7.16)
- Symfony 7.4 or 8.x
- `yt-dlp` installed on the host machine (in `PATH` or at a custom path)

Check the binary:

```bash
yt-dlp --version
```

## 📦 Installation

```bash
composer require p3sdev/php-ytdlp-wrapper
```

## 🚀 Quick start (standalone PHP)

```php
<?php

declare(strict_types=1);

use P3s\YtDlp\YtDlpClient;

$client = new YtDlpClient();

$result = $client->download('https://www.youtube.com/watch?v=BaW_jenozKc', [
    'format' => 'bv*+ba/b',
    'output' => '%(title)s.%(ext)s',
    'paths' => '/tmp/videos',
]);

$result->requireSuccessful();

echo $result->stdout;
```

Read metadata without downloading:

```php
<?php

declare(strict_types=1);

use P3s\YtDlp\YtDlpClient;

$client = new YtDlpClient();

$items = $client->extractInfo('https://www.youtube.com/watch?v=BaW_jenozKc', [
    'no-playlist' => true,
]);

$first = $items[0];

echo $first['title'];
```

## 🧩 Symfony 7.4/8 bundle

### Register the bundle

Add it to `config/bundles.php`:

```php
return [
    // ...
    P3s\YtDlp\YtDlpBundle::class => ['all' => true],
];
```

If the Flex recipe is published, `composer require` can add this entry automatically.

### Configure the bundle

Create `config/packages/yt_dlp.yaml`:

```yaml
yt_dlp:
  binary_path: 'yt-dlp'
  default_arguments: ['--no-warnings']
  working_directory: ~
  timeout: 300
  environment: {}
```

| Key | Default | Description |
|---|---|---|
| `binary_path` | `yt-dlp` | Binary name from `PATH`, or an explicit file path |
| `default_arguments` | `['--no-warnings']` | Arguments added to every command |
| `working_directory` | `null` | Working directory for the process |
| `timeout` | `300.0` | Timeout in seconds; `0` disables it |
| `environment` | `[]` | Extra environment variables for the child process |

### Inject the client

```php
<?php

declare(strict_types=1);

namespace App\Service;

use P3s\YtDlp\YtDlpClientInterface;

final class VideoService
{
    public function __construct(private readonly YtDlpClientInterface $ytDlpClient)
    {
    }

    public function download(string $url): void
    {
        $result = $this->ytDlpClient->download($url, [
            'format' => 'bv*+ba/b',
            'output' => '%(title)s.%(ext)s',
            'paths' => '/tmp/videos',
        ]);

        $result->requireSuccessful();
    }
}
```

The service is available under `YtDlpClientInterface`; you do not need to wire it manually.

## 🛠️ API at a glance

| Method | What it does | Throws |
|---|---|---|
| `run(YtDlpRequest $request)` | Executes a fully described request | Binary-not-found; empty URL list |
| `download(string\|array $urls, array $options = [])` | Runs a download command | Binary-not-found; empty URL list |
| `rawJson(string\|array $urls, array $options = [])` | Runs metadata mode and returns the full result | Binary-not-found; empty URL list |
| `extractInfo(string\|array $urls, array $options = [])` | Runs metadata mode and returns decoded JSON values | Binary-not-found; empty URL list; process-failure |

`run()`, `download()`, and `rawJson()` return a `ProcessResult` even when `yt-dlp` exits with a
non-zero code. Call `requireSuccessful()` when failure should become an exception.

Process timeouts and other Symfony Process errors (for example an invalid working directory) are not
wrapped yet; they surface as Symfony exceptions. Wrapping them is planned for 2.0.

## ⬇️ Downloading

The examples below assume a configured `$client` from the quick start.

### Single video with a format and output template

```php
$result = $client->download('https://www.youtube.com/watch?v=BaW_jenozKc', [
    'format' => 'bv*+ba/b',
    'output' => '%(title)s.%(ext)s',
    'paths' => '/tmp/videos',
]);
```

### Multiple URLs

```php
$result = $client->download([
    'https://www.youtube.com/watch?v=BaW_jenozKc',
    'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
]);
```

### Playlist limits

```php
$result = $client->download('https://www.youtube.com/playlist?list=PL...', [
    'yes-playlist' => true,
    'playlist-items' => '1:5',
    'ignore-errors' => true,
]);
```

### Cookies and a proxy

```php
$url = 'https://www.youtube.com/watch?v=BaW_jenozKc';

$result = $client->download($url, [
    'cookies' => '/secure/cookies.txt',
    'proxy' => 'socks5://127.0.0.1:1080',
    'socket-timeout' => 15,
]);
```

### Check the result without throwing

```php
$url = 'https://www.youtube.com/watch?v=BaW_jenozKc';

$result = $client->download($url);

if (!$result->isSuccessful()) {
    \fwrite(\STDERR, \sprintf("yt-dlp exited with %d\n%s", $result->exitCode, $result->stderr));
}
```

## 🔎 Reading metadata

### `extractInfo()` — decoded metadata

```php
$url = 'https://www.youtube.com/watch?v=BaW_jenozKc';

$items = $client->extractInfo($url, [
    'no-playlist' => true,
]);

foreach ($items as $item) {
    echo $item['title'] ?? 'Untitled';
}
```

For a playlist, return the first few entries:

```php
$playlistUrl = 'https://www.youtube.com/playlist?list=PL...';

$items = $client->extractInfo($playlistUrl, [
    'flat-playlist' => true,
    'playlist-end' => 2,
]);
```

### `rawJson()` — full process result

```php
$url = 'https://www.youtube.com/watch?v=BaW_jenozKc';

$result = $client->rawJson($url);
$result->requireSuccessful();

foreach ($result->jsonLines as $line) {
    // Each entry is a decoded JSON object or array.
    \var_dump($line);
}

// Full output and exit information are still available.
echo $result->exitCode;
echo $result->stderr;
```

`rawJson()` adds `--dump-json --skip-download --no-warnings`. With the default bundle configuration,
`--no-warnings` is also present in `default_arguments`, so it can appear twice; `yt-dlp` accepts
duplicate flags.

## ⚙️ Low-level requests

`YtDlpRequest` gives you control over every part of the command:

```php
<?php

declare(strict_types=1);

use P3s\YtDlp\YtDlpRequest;

$request = YtDlpRequest::create(
    urls: ['https://www.youtube.com/watch?v=BaW_jenozKc'],
    options: [
        'format' => 'bestvideo+bestaudio/best',
        'cookies' => '/secure/cookies.txt',
        'proxy' => 'socks5://127.0.0.1:1080',
        'playlist-items' => '1:3',
        'print' => ['id', 'title'],
    ],
    flags: ['no-progress'],
    extraArguments: ['--no-cache-dir'],
);

$result = $client->run($request);
```

The generated command has this order:

```text
yt-dlp [default arguments] [flags] [options] [extra arguments] -- [URLs]
```

### Options, flags, and extra arguments

- **Options** — named values, for example `'format' => 'best'`.
- **Flags** — switches without a value, for example `flags: ['no-progress']`.
- **Extra arguments** — raw trusted tokens appended before the `--` marker.
- **URLs** — positional values placed after `--`.

Option names are normalized: `format` becomes `--format`, while `-o` and `--output` are used as
written. Values are mapped like this:

| PHP input | Generated CLI |
|---|---|
| `'format' => 'best'` | `--format best` |
| `'print' => ['id', 'title']` | `--print id --print title` |
| `'no-progress' => true` | `--no-progress` |
| `'quiet' => false` | omitted |
| `'cookies' => null` | omitted |
| `'--output' => '%(title)s.%(ext)s'` | `--output %(title)s.%(ext)s` |
| `'-o' => 'video.%(ext)s'` | `-o video.%(ext)s` |

## 🧾 `ProcessResult`

| Property / method | Type | Meaning |
|---|---|---|
| `command` | `string[]` | The generated command, including `--` |
| `exitCode` | `int` | Process exit code |
| `stdout` | `string` | Standard output |
| `stderr` | `string` | Standard error |
| `jsonLines` | `array` | JSON values decoded from `stdout`, one per line |
| `isSuccessful()` | `bool` | `true` when the exit code is `0` |
| `requireSuccessful()` | `self` | Returns the result or throws `ProcessFailedException` |

Non-JSON lines in `stdout` are ignored when building `jsonLines`.

## 🔒 URL safety

The wrapper places the `--` end-of-options marker before URLs, so a URL that starts with `-` is
treated as positional input, not as a `yt-dlp` option. If the last extra argument is already `--`,
the marker is not duplicated.

Option keys and `extraArguments` are trusted input: do not pass untrusted user data through them.
URLs are the place for user-provided values.

## ⚠️ Error handling

| Exception | When it is raised |
|---|---|
| `YtDlpException` | Base class for all wrapper errors |
| `BinaryNotFoundException` | The configured binary is missing, not executable, or absent from the search path |
| `ProcessFailedException` | `extractInfo()` failed, or `requireSuccessful()` was called on a failed result |

Catch the specific errors:

```php
<?php

declare(strict_types=1);

use P3s\YtDlp\Exception\BinaryNotFoundException;
use P3s\YtDlp\Exception\ProcessFailedException;

$url = 'https://www.youtube.com/watch?v=BaW_jenozKc';

try {
    $items = $client->extractInfo($url);
} catch (BinaryNotFoundException $exception) {
    // Install yt-dlp or fix binary_path.
} catch (ProcessFailedException $exception) {
    $result = $exception->getResult();

    \fwrite(\STDERR, $result->stderr);
    \fwrite(\STDERR, \sprintf("exit code: %d\n", $result->exitCode));
}
```

`ProcessFailedException::getResult()` returns the original `ProcessResult`, so the command, output,
error output, and parsed JSON lines are still available.

## 🔧 Custom client configuration

Standalone, without Symfony:

```php
<?php

declare(strict_types=1);

use P3s\YtDlp\YtDlpClient;

$client = new YtDlpClient(
    binaryPath: '/usr/local/bin/yt-dlp',
    defaultArguments: ['--no-warnings', '--no-progress'],
    workingDirectory: '/tmp/videos',
    timeout: 600.0,
    environment: [
        'HTTP_PROXY' => 'http://127.0.0.1:8080',
    ],
);
```

Environment values follow Symfony Process semantics:

- `false` omits the variable from the child process;
- `null` sets it to an empty value;
- objects with `__toString()` are converted to strings.

## 🍱 Symfony Flex recipe

A ready-to-submit recipe scaffold is available in `flex-recipe/1.0`. To publish it for auto-install
support, submit that directory to `symfony/recipes-contrib`.

## 🧪 Tests and CI

```bash
make test   # PHPUnit
make lint   # php-cs-fixer, PHPStan, Psalm, peck
```

GitHub Actions runs the test suite on PHP 8.4, 8.5, and 8.6 with the lowest and highest allowed
dependencies (Symfony 7.4 and 8.x). Separate jobs run static analysis on PHP 8.6, code style plus
spelling checks on PHP 8.5, and a coverage gate that requires 100% line coverage.

Workflow file: `.github/workflows/tests.yml`.

## 📝 Behavior notes

- The wrapper maps options directly to `yt-dlp` CLI flags (`format` -> `--format`).
- Arrays in option values are emitted as repeated flags.
- Boolean `true` emits a flag; `false` and `null` are ignored.
- URLs are separated from options by `--`.
- The command is executed without a shell.
- The default binary name is resolved through the configured search path; an explicit path must exist
  and be executable.
- `timeout: 0` disables the process timeout; negative values are rejected.

## 📄 License

MIT. See `LICENSE`.
