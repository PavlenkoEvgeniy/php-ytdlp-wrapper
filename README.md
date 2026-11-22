# 🎬 php-ytdlp-wrapper

Robust `yt-dlp` wrapper for **PHP 8.4–8.6** with a ready-to-use **Symfony 7.4/8 bundle**.

## ✨ Features

- Safe process execution through `symfony/process`
- Structured request object (`YtDlpRequest`)
- Raw execution + JSON extraction helpers
- Strong error model (`BinaryNotFoundException`, `ProcessFailedException`)
- Symfony 7.4/8 service wiring and configurable bundle options

## 📋 Requirements

- PHP 8.4, 8.5, or 8.6 at runtime; PHP 8.4.3+ for development (Psalm 7.16)
- Symfony 7.4 or 8.x
- `yt-dlp` installed on the host machine (binary in `PATH` or custom path)

## 📦 Install

```bash
composer require p3sdev/php-ytdlp-wrapper
```

## 🧩 Symfony 7.4/8 Bundle Setup

### 1️⃣ Register bundle

If Symfony Flex does not auto-register it, add to `config/bundles.php`:

```php
return [
    // ...
    P3s\YtDlp\YtDlpBundle::class => ['all' => true],
];
```

### 2️⃣ Configure bundle

Create `config/packages/yt_dlp.yaml`:

```yaml
yt_dlp:
  binary_path: 'yt-dlp'
  default_arguments: ['--no-warnings']
  working_directory: ~
  timeout: 300
  environment: {}
```

`timeout: 0` disables the process timeout (Symfony Process semantics); negative values are rejected.

## 🍱 Symfony Flex Recipe

A ready-to-submit Symfony Flex recipe scaffold is available in:

- `flex-recipe/1.0`

To publish it for auto-install support, submit that recipe to `symfony/recipes-contrib`.

## 🚀 Usage in Symfony

Inject `P3s\YtDlp\YtDlpClientInterface` into your service:

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

## 🛠️ Core API

### ⬇️ `download(string|array $urls, array $options = []): ProcessResult`

Runs `yt-dlp` for download/processing workflows.

### 🧠 `rawJson(string|array $urls, array $options = []): ProcessResult`

Runs metadata mode (`--dump-json --skip-download --no-warnings`) and returns full process output.
With the default bundle configuration, `--no-warnings` is also present in `default_arguments`, so it
can appear twice; yt-dlp accepts duplicate flags.

### 🔎 `extractInfo(string|array $urls, array $options = []): array`

Convenience method that returns parsed JSON lines.

### ⚙️ `run(YtDlpRequest $request): ProcessResult`

Low-level execution with full control over options, flags, and extra arguments.

## 🧾 `YtDlpRequest`

```php
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
);
```

The wrapper inserts the `--` end-of-options marker before URLs. If the last extra argument is already
`--`, the marker is not duplicated.

## 🔒 URL safety

The wrapper always places the `--` end-of-options marker before URLs, so a URL that starts with `-`
is treated as positional input, not as a `yt-dlp` option. Option keys and `extraArguments` are trusted
input: do not pass untrusted user data through them.

## ⚠️ Errors

- `BinaryNotFoundException`: the configured binary path does not exist or is not executable, or the
  default `yt-dlp` binary is not found in the search path
- `ProcessFailedException`: raised by `extractInfo()` or by `requireSuccessful()` when the result is
  unsuccessful; `run()`, `download()`, and `rawJson()` return a failed result instead of throwing
- `YtDlpException`: base runtime exception

## 📝 Notes

- The wrapper maps options directly to `yt-dlp` CLI flags (`format` -> `--format`).
- Arrays in option values are emitted as repeated flags.
- Boolean `true` emits a flag, `false` and `null` are ignored.
- URLs are separated from options by `--`.
- Environment values follow Symfony Process semantics: `false` omits a variable, `null` makes it
  empty, and `Stringable` values are stringified.

## 📄 License

MIT. See `LICENSE`.

## ✅ CI

GitHub Actions runs the test suite on push and pull requests for PHP `8.4`, `8.5`, and `8.6`, each
with the lowest and highest allowed dependencies (Symfony 7.4 and 8.x). Separate jobs run static
analysis on PHP 8.6, code style plus spelling checks on PHP 8.5, and a coverage gate that requires
100% line coverage.

Workflow file: `.github/workflows/tests.yml`
