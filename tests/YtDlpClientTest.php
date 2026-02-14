<?php

declare(strict_types=1);

namespace P3s\YtDlp\Tests;

use P3s\YtDlp\Exception\BinaryNotFoundException;
use P3s\YtDlp\Exception\ProcessFailedException;
use P3s\YtDlp\YtDlpClient;
use P3s\YtDlp\YtDlpRequest;
use PHPUnit\Framework\TestCase;

final class YtDlpClientTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = \sys_get_temp_dir() . '/php-ytdlp-wrapper-tests-' . \bin2hex(\random_bytes(8));
        \mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);

        parent::tearDown();
    }

    public function testRunBuildsCommandAndParsesJsonLines(): void
    {
        $binary   = $this->createFakeBinary();
        $argsFile = $this->tmpDir . '/args.txt';

        $client = new YtDlpClient(
            binaryPath: $binary,
            defaultArguments: ['--default-flag', 'value'],
            workingDirectory: $this->tmpDir,
            timeout: 30,
            environment: [
                'FAKE_YTDLP_ARGS_FILE' => $argsFile,
                'FAKE_YTDLP_EXIT_CODE' => '0',
                'FAKE_YTDLP_MODE'      => 'json',
            ]
        );

        $request = YtDlpRequest::create(
            urls: ['https://example.com/one', 'https://example.com/two'],
            options: [
                'format'   => 'bv*+ba/b',
                '--output' => '%(title)s.%(ext)s',
                'print'    => ['id', 'title'],
                'newline'  => true,
                'quiet'    => false,
                'cookies'  => null,
            ],
            flags: ['no-warnings', '--skip-download'],
            extraArguments: ['--']
        );

        $result = $client->run($request);

        self::assertTrue($result->isSuccessful());
        self::assertSame(0, $result->exitCode);
        self::assertStringContainsString('stderr-line', $result->stderr);
        self::assertCount(2, $result->jsonLines);
        self::assertSame('video-1', $result->jsonLines[0]['id']);
        self::assertSame('video-2', $result->jsonLines[1]['id']);

        $savedArgs = \file($argsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        self::assertSame([
            '--default-flag',
            'value',
            '--no-warnings',
            '--skip-download',
            '--format',
            'bv*+ba/b',
            '--output',
            '%(title)s.%(ext)s',
            '--print',
            'id',
            '--print',
            'title',
            '--newline',
            '--',
            'https://example.com/one',
            'https://example.com/two',
        ], $savedArgs);
    }

    public function testRawJsonAddsExpectedFlags(): void
    {
        $binary   = $this->createFakeBinary();
        $argsFile = $this->tmpDir . '/raw-json-args.txt';

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_ARGS_FILE' => $argsFile,
                'FAKE_YTDLP_EXIT_CODE' => '0',
                'FAKE_YTDLP_MODE'      => 'json',
            ]
        );

        $result = $client->rawJson('https://example.com/watch?v=1', ['format' => 'best']);

        self::assertTrue($result->isSuccessful());

        $savedArgs = \file($argsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        self::assertContains('--dump-json', $savedArgs);
        self::assertContains('--skip-download', $savedArgs);
        self::assertContains('--no-warnings', $savedArgs);
        self::assertContains('--format', $savedArgs);
        self::assertContains('best', $savedArgs);
    }

    public function testExtractInfoThrowsOnProcessFailure(): void
    {
        $binary = $this->createFakeBinary();

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_EXIT_CODE' => '3',
                'FAKE_YTDLP_MODE'      => 'json',
            ]
        );

        $this->expectException(ProcessFailedException::class);

        $client->extractInfo('https://example.com/watch?v=1');
    }

    public function testRunThrowsForMissingBinaryPath(): void
    {
        $client = new YtDlpClient('/this/path/does/not/exist/yt-dlp');

        $this->expectException(BinaryNotFoundException::class);

        $client->run(YtDlpRequest::create('https://example.com/video'));
    }

    private function createFakeBinary(): string
    {
        $binary = $this->tmpDir . '/fake-yt-dlp.sh';

        $script = <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

if [[ -n "${FAKE_YTDLP_ARGS_FILE:-}" ]]; then
  for arg in "$@"; do
    printf '%s\n' "$arg" >> "$FAKE_YTDLP_ARGS_FILE"
  done
fi

mode="${FAKE_YTDLP_MODE:-json}"
if [[ "$mode" == "json" ]]; then
  printf '%s\n' '{"id":"video-1","title":"One"}'
  printf '%s\n' 'not-json-line'
  printf '%s\n' '{"id":"video-2","title":"Two"}'
fi

printf '%s\n' 'stderr-line' >&2
exit "${FAKE_YTDLP_EXIT_CODE:-0}"
BASH;

        \file_put_contents($binary, $script);
        \chmod($binary, 0755);

        return $binary;
    }

    private function removeDir(string $path): void
    {
        if (!\is_dir($path)) {
            return;
        }

        $items = \scandir($path);
        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $full = $path . '/' . $item;
            if (\is_dir($full)) {
                $this->removeDir($full);
            } else {
                @\unlink($full);
            }
        }

        @\rmdir($path);
    }
}
