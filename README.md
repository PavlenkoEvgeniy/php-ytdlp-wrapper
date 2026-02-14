# php-ytdlp-wrapper

Robust `yt-dlp` wrapper for **PHP 8.2** with a ready-to-use **Symfony 7 bundle**.

## Features

- Safe process execution through `symfony/process`
- Structured request object (`YtDlpRequest`)
- Raw execution + JSON extraction helpers
- Strong error model (`BinaryNotFoundException`, `ProcessFailedException`)
- Symfony 7 service wiring and configurable bundle options

## Requirements

- PHP 8.2+
- `yt-dlp` installed on the host machine (binary in `PATH` or custom path)

## Install

```bash
composer require p3s/php-ytdlp-wrapper
```

## Symfony 7 Bundle Setup

### 1) Register bundle

If Symfony Flex does not auto-register it, add to `config/bundles.php`:

```php
return [
    // ...
    P3s\YtDlp\YtDlpBundle::class => ['all' => true],
];
```

### 2) Configure bundle

Create `config/packages/yt_dlp.yaml`:

```yaml
yt_dlp:
  binary_path: 'yt-dlp'
  default_arguments: ['--no-warnings']
  working_directory: ~
  timeout: 300
  environment: {}
```

## Usage in Symfony

Inject `P3s\YtDlp\YtDlpClientInterface` into your service:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use P3s\YtDlp\YtDlpClientInterface;

final class VideoService
{
    public function __construct(private readonly YtDlpClientInterface $ytDlp)
    {
    }

    public function download(string $url): void
    {
        $result = $this->ytDlp->download($url, [
            'format' => 'bv*+ba/b',
            'output' => '%(title)s.%(ext)s',
            'paths' => '/tmp/videos',
        ]);

        $result->requireSuccessful();
    }
}
```

## Core API

### `download(string|array $urls, array $options = []): ProcessResult`

Runs `yt-dlp` for download/processing workflows.

### `rawJson(string|array $urls, array $options = []): ProcessResult`

Runs metadata mode (`--dump-json --skip-download --no-warnings`) and returns full process output.

### `extractInfo(string|array $urls, array $options = []): array`

Convenience method that returns parsed JSON lines.

### `run(YtDlpRequest $request): ProcessResult`

Low-level execution with full control over options, flags, and extra arguments.

## `YtDlpRequest`

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
    extraArguments: ['--'],
);
```

## Errors

- `BinaryNotFoundException`: custom binary path does not exist or is not executable
- `ProcessFailedException`: command returned non-zero exit code
- `YtDlpException`: base runtime exception

## Notes

- The wrapper maps options directly to `yt-dlp` CLI flags (`format` -> `--format`).
- Arrays in option values are emitted as repeated flags.
- Boolean `true` emits a flag, `false` is ignored.

## License

MIT. See `LICENSE`.

## CI

GitHub Actions runs tests on push and pull requests for PHP `8.2`, `8.3`, and `8.4`.

Workflow file: `.github/workflows/tests.yml`
