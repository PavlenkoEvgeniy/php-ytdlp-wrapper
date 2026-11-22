<?php

declare(strict_types=1);

namespace P3s\YtDlp\Tests;

use P3s\YtDlp\Exception\BinaryNotFoundException;
use P3s\YtDlp\Exception\ProcessFailedException;
use P3s\YtDlp\Exception\YtDlpException;
use P3s\YtDlp\YtDlpClient;
use P3s\YtDlp\YtDlpRequest;
use PHPUnit\Framework\TestCase;

final class YtDlpClientTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = \sprintf(
            '%s/php-ytdlp-wrapper-tests-%s',
            \sys_get_temp_dir(),
            \bin2hex(\random_bytes(8)),
        );
        \mkdir($this->temporaryDirectory, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->temporaryDirectory);

        parent::tearDown();
    }

    /**
     * Checks that all request parts keep their order and JSON lines are decoded.
     */
    public function testRunBuildsCommandAndParsesJsonLines(): void
    {
        $binary        = $this->createFakeBinary();
        $argumentsFile = \sprintf('%s/args.txt', $this->temporaryDirectory);

        $client = new YtDlpClient(
            binaryPath: $binary,
            defaultArguments: ['--default-flag', 'value'],
            workingDirectory: $this->temporaryDirectory,
            timeout: 30,
            environment: [
                'FAKE_YTDLP_ARGS_FILE' => $argumentsFile,
                'FAKE_YTDLP_EXIT_CODE' => '0',
                'FAKE_YTDLP_MODE'      => 'json',
            ],
        );

        $request = YtDlpRequest::create(
            urls: ['https://test.local/one', 'https://test.local/two'],
            options: [
                'format'   => 'bv*+ba/b',
                '--output' => '%(title)s.%(ext)s',
                'print'    => ['id', 'title'],
                'newline'  => true,
                'quiet'    => false,
                'cookies'  => null,
            ],
            flags: ['no-warnings', '--skip-download'],
            extraArguments: ['--'],
        );

        $result = $client->run($request);

        self::assertTrue($result->isSuccessful());
        self::assertSame(0, $result->exitCode);
        self::assertStringContainsString('stderr-line', $result->stderr);
        self::assertCount(2, $result->jsonLines);
        self::assertSame('video-1', $result->jsonLines[0]['id']);
        self::assertSame('video-2', $result->jsonLines[1]['id']);

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
            'https://test.local/one',
            'https://test.local/two',
        ], $this->savedArguments($argumentsFile));
    }

    /**
     * Checks that the metadata operation adds the expected flags and separator.
     */
    public function testRawJsonAddsExpectedFlags(): void
    {
        $binary        = $this->createFakeBinary();
        $argumentsFile = \sprintf('%s/raw-json-args.txt', $this->temporaryDirectory);

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_ARGS_FILE' => $argumentsFile,
                'FAKE_YTDLP_EXIT_CODE' => '0',
                'FAKE_YTDLP_MODE'      => 'json',
            ],
        );

        $result = $client->rawJson('https://test.local/watch?v=1', ['format' => 'best']);

        self::assertTrue($result->isSuccessful());

        $savedArguments = $this->savedArguments($argumentsFile);

        self::assertContains('--dump-json', $savedArguments);
        self::assertContains('--skip-download', $savedArguments);
        self::assertContains('--no-warnings', $savedArguments);
        self::assertContains('--format', $savedArguments);
        self::assertContains('best', $savedArguments);

        $separatorPosition = \array_search('--', $savedArguments, true);

        self::assertIsInt($separatorPosition);
        self::assertSame('https://test.local/watch?v=1', $savedArguments[$separatorPosition + 1]);
    }

    /**
     * Checks that a URL that looks like an option is placed after the separator.
     */
    public function testUrlStartingWithOptionMarkerIsPositional(): void
    {
        $binary        = $this->createFakeBinary();
        $argumentsFile = \sprintf('%s/dash-url-args.txt', $this->temporaryDirectory);

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_ARGS_FILE' => $argumentsFile,
                'FAKE_YTDLP_EXIT_CODE' => '0',
                'FAKE_YTDLP_MODE'      => 'json',
            ],
        );

        $client->run(YtDlpRequest::create('--exec=echo'));

        self::assertSame(['--', '--exec=echo'], \array_slice($this->savedArguments($argumentsFile), -2));
    }

    /**
     * Checks that an existing trailing separator is not duplicated.
     */
    public function testExistingSeparatorIsNotDuplicated(): void
    {
        $binary        = $this->createFakeBinary();
        $argumentsFile = \sprintf('%s/existing-separator-args.txt', $this->temporaryDirectory);

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_ARGS_FILE' => $argumentsFile,
                'FAKE_YTDLP_EXIT_CODE' => '0',
                'FAKE_YTDLP_MODE'      => 'json',
            ],
        );

        $client->run(YtDlpRequest::create(
            urls: 'https://test.local/video',
            extraArguments: ['--'],
        ));

        $savedArguments = $this->savedArguments($argumentsFile);

        self::assertSame(1, \count(\array_keys($savedArguments, '--', true)));
        self::assertSame(['--', 'https://test.local/video'], \array_slice($savedArguments, -2));
    }

    /**
     * Checks that an option value of `--` does not suppress the URL separator.
     */
    public function testOptionValueThatLooksLikeSeparatorDoesNotSuppressTheMarker(): void
    {
        $binary          = $this->createFakeBinary();
        $argumentsFile   = \sprintf('%s/option-separator-args.txt', $this->temporaryDirectory);

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_ARGS_FILE' => $argumentsFile,
                'FAKE_YTDLP_EXIT_CODE' => '0',
                'FAKE_YTDLP_MODE'      => 'json',
            ],
        );

        $client->run(YtDlpRequest::create(
            urls: '-dash-url',
            options: ['output' => '--'],
        ));

        self::assertSame(
            ['--output', '--', '--', '-dash-url'],
            $this->savedArguments($argumentsFile),
        );
    }

    /**
     * Checks that several URLs share a single separator.
     */
    public function testMultipleUrlsShareOneSeparator(): void
    {
        $binary        = $this->createFakeBinary();
        $argumentsFile = \sprintf('%s/multiple-urls-args.txt', $this->temporaryDirectory);

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_ARGS_FILE' => $argumentsFile,
                'FAKE_YTDLP_EXIT_CODE' => '0',
                'FAKE_YTDLP_MODE'      => 'json',
            ],
        );

        $client->run(YtDlpRequest::create([
            'https://test.local/one',
            'https://test.local/two',
        ]));

        $savedArguments = $this->savedArguments($argumentsFile);

        self::assertSame(1, \count(\array_keys($savedArguments, '--', true)));
        self::assertSame(
            ['--', 'https://test.local/one', 'https://test.local/two'],
            \array_slice($savedArguments, -3),
        );
    }

    /**
     * Checks that option and flag names are normalized before execution.
     */
    public function testOptionNameNormalization(): void
    {
        $binary        = $this->createFakeBinary();
        $argumentsFile = \sprintf('%s/normalized-args.txt', $this->temporaryDirectory);

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_ARGS_FILE' => $argumentsFile,
                'FAKE_YTDLP_EXIT_CODE' => '0',
                'FAKE_YTDLP_MODE'      => 'json',
            ],
        );

        $client->run(YtDlpRequest::create(
            urls: 'https://test.local/video',
            options: [
                ' format ' => 'best',
                '-o'       => 'file',
                '--print'  => ['id'],
            ],
            flags: [' no-progress ', '-k', '--flat-playlist'],
            extraArguments: ['--no-cache-dir'],
        ));

        self::assertSame([
            '--no-progress',
            '-k',
            '--flat-playlist',
            '--format',
            'best',
            '-o',
            'file',
            '--print',
            'id',
            '--no-cache-dir',
            '--',
            'https://test.local/video',
        ], $this->savedArguments($argumentsFile));
    }

    /**
     * Checks that true emits a flag while false and null omit it.
     */
    public function testBooleanAndNullOptionValues(): void
    {
        $binary        = $this->createFakeBinary();
        $argumentsFile = \sprintf('%s/boolean-args.txt', $this->temporaryDirectory);

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_ARGS_FILE' => $argumentsFile,
                'FAKE_YTDLP_EXIT_CODE' => '0',
                'FAKE_YTDLP_MODE'      => 'json',
            ],
        );

        $client->run(YtDlpRequest::create(
            urls: 'https://test.local/video',
            options: [
                'newline' => true,
                'quiet'   => false,
                'cookies' => null,
            ],
        ));

        self::assertSame(
            ['--newline', '--', 'https://test.local/video'],
            $this->savedArguments($argumentsFile),
        );
    }

    /**
     * Checks that a download returns a successful process result.
     */
    public function testDownloadReturnsResult(): void
    {
        $binary        = $this->createFakeBinary();
        $argumentsFile = \sprintf('%s/download-args.txt', $this->temporaryDirectory);

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_ARGS_FILE' => $argumentsFile,
                'FAKE_YTDLP_EXIT_CODE' => '0',
                'FAKE_YTDLP_MODE'      => 'json',
            ],
        );

        $result = $client->download('https://test.local/video', ['format' => 'best']);

        self::assertTrue($result->isSuccessful());
        self::assertSame(
            ['--format', 'best', '--', 'https://test.local/video'],
            \array_slice($this->savedArguments($argumentsFile), -4),
        );
    }

    /**
     * Checks that a successful metadata extraction returns decoded objects.
     */
    public function testExtractInfoReturnsDecodedJson(): void
    {
        $binary = $this->createFakeBinary();

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_EXIT_CODE' => '0',
                'FAKE_YTDLP_MODE'      => 'json',
            ],
        );

        $items = $client->extractInfo('https://test.local/video');

        self::assertCount(2, $items);
        self::assertSame('video-1', $items[0]['id']);
        self::assertSame('video-2', $items[1]['id']);
    }

    /**
     * Checks that scalar option values other than strings are cast to strings.
     */
    public function testScalarOptionValuesAreCast(): void
    {
        $binary        = $this->createFakeBinary();
        $argumentsFile = \sprintf('%s/scalar-options-args.txt', $this->temporaryDirectory);

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_ARGS_FILE' => $argumentsFile,
                'FAKE_YTDLP_EXIT_CODE' => '0',
                'FAKE_YTDLP_MODE'      => 'json',
            ],
        );

        $client->run(YtDlpRequest::create(
            urls: 'https://test.local/video',
            options: [
                'playlist-end'   => 2,
                'socket-timeout' => 15.5,
            ],
        ));

        self::assertSame(
            ['--playlist-end', '2', '--socket-timeout', '15.5', '--', 'https://test.local/video'],
            $this->savedArguments($argumentsFile),
        );
    }

    /**
     * Checks that a failed download returns a failed result instead of throwing.
     */
    public function testDownloadReturnsFailedResult(): void
    {
        $binary = $this->createFakeBinary();

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_EXIT_CODE' => '4',
                'FAKE_YTDLP_MODE'      => 'json',
            ],
        );

        $result = $client->download('https://test.local/video');

        self::assertFalse($result->isSuccessful());
        self::assertSame(4, $result->exitCode);
        self::assertStringContainsString('stderr-line', $result->stderr);
        self::assertCount(2, $result->jsonLines);
    }

    /**
     * Checks that a failed raw JSON request returns a failed result instead of throwing.
     */
    public function testRawJsonReturnsFailedResult(): void
    {
        $binary = $this->createFakeBinary();

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_EXIT_CODE' => '4',
                'FAKE_YTDLP_MODE'      => 'json',
            ],
        );

        $result = $client->rawJson('https://test.local/video');

        self::assertFalse($result->isSuccessful());
        self::assertSame(4, $result->exitCode);
    }

    /**
     * Checks that a top-level JSON array line is decoded like an object line.
     */
    public function testJsonArrayLineIsDecoded(): void
    {
        $binary = $this->createFakeBinary();

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_EXIT_CODE' => '0',
                'FAKE_YTDLP_MODE'      => 'array',
            ],
        );

        $result = $client->run(YtDlpRequest::create('https://test.local/video'));

        self::assertSame([[1, 2]], $result->jsonLines);
    }

    /**
     * Checks that a failed metadata extraction raises a process failure.
     */
    public function testExtractInfoThrowsOnProcessFailure(): void
    {
        $binary = $this->createFakeBinary();

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_EXIT_CODE' => '3',
                'FAKE_YTDLP_MODE'      => 'json',
            ],
        );

        $this->expectException(ProcessFailedException::class);

        $client->extractInfo('https://test.local/watch?v=1');
    }

    /**
     * Checks that an empty URL list raises a wrapper error before execution.
     */
    public function testEmptyUrlListThrows(): void
    {
        $binary        = $this->createFakeBinary();
        $argumentsFile = \sprintf('%s/empty-url-args.txt', $this->temporaryDirectory);

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: ['FAKE_YTDLP_ARGS_FILE' => $argumentsFile],
        );

        try {
            $client->run(YtDlpRequest::create([]));
            self::fail('Expected YtDlpException to be thrown.');
        } catch (YtDlpException $exception) {
            self::assertStringContainsString('At least one URL is required.', $exception->getMessage());
        }

        self::assertFileDoesNotExist($argumentsFile);
    }

    /**
     * Checks that a missing explicit binary path raises a binary error.
     */
    public function testRunThrowsForMissingBinaryPath(): void
    {
        $client = new YtDlpClient('/this/path/does/not/exist/yt-dlp');

        $this->expectException(BinaryNotFoundException::class);

        $client->run(YtDlpRequest::create('https://test.local/video'));
    }

    /**
     * Checks that a non-executable explicit binary path raises a binary error.
     */
    public function testRunThrowsForNonExecutableBinary(): void
    {
        $binary = \sprintf('%s/not-executable-yt-dlp', $this->temporaryDirectory);

        \file_put_contents($binary, '#!/usr/bin/env bash');
        \chmod($binary, 0644);

        $client = new YtDlpClient(binaryPath: $binary);

        $this->expectException(BinaryNotFoundException::class);

        $client->run(YtDlpRequest::create('https://test.local/video'));
    }

    /**
     * Checks that the default binary is found in the configured search path.
     */
    public function testRunUsesDefaultBinaryFromConfiguredPath(): void
    {
        $binary        = $this->createNamedFakeBinary('yt-dlp');
        $argumentsFile = \sprintf('%s/default-path-args.txt', $this->temporaryDirectory);

        $client = new YtDlpClient(
            environment: [
                'PATH'                 => \sprintf(
                    '%s%s%s',
                    \dirname($binary),
                    PATH_SEPARATOR,
                    \getenv('PATH') ?: '',
                ),
                'FAKE_YTDLP_ARGS_FILE' => $argumentsFile,
                'FAKE_YTDLP_EXIT_CODE' => '0',
                'FAKE_YTDLP_MODE'      => 'json',
            ],
        );

        $result = $client->run(YtDlpRequest::create('https://test.local/default-binary'));

        self::assertTrue($result->isSuccessful());
        self::assertContains('https://test.local/default-binary', $this->savedArguments($argumentsFile));
    }

    /**
     * Checks that a missing default binary in the search path raises a binary error.
     */
    public function testRunThrowsForMissingDefaultBinaryOnHost(): void
    {
        $client = new YtDlpClient(environment: ['PATH' => $this->temporaryDirectory]);

        $this->expectException(BinaryNotFoundException::class);

        $client->run(YtDlpRequest::create('https://test.local/video'));
    }

    /**
     * Checks that an empty configured search path raises a binary error.
     */
    public function testRunThrowsForEmptyConfiguredPath(): void
    {
        $client = new YtDlpClient(environment: ['PATH' => '']);

        $this->expectException(BinaryNotFoundException::class);

        $client->run(YtDlpRequest::create('https://test.local/video'));
    }

    /**
     * Checks that empty segments in the configured search path are skipped.
     */
    public function testRunSkipsEmptyPathSegments(): void
    {
        $client = new YtDlpClient(environment: [
            'PATH' => \sprintf('%s%s', $this->temporaryDirectory, PATH_SEPARATOR),
        ]);

        $this->expectException(BinaryNotFoundException::class);

        $client->run(YtDlpRequest::create('https://test.local/video'));
    }

    /**
     * Checks that scalar default arguments and environment values become strings.
     */
    public function testScalarArgumentsAndEnvironmentAreCast(): void
    {
        $binary           = $this->createFakeBinary();
        $argumentsFile    = \sprintf('%s/scalar-args.txt', $this->temporaryDirectory);
        $environmentFile  = \sprintf('%s/scalar-env.txt', $this->temporaryDirectory);

        $client = new YtDlpClient(
            binaryPath: $binary,
            defaultArguments: [123, true],
            environment: [
                'FAKE_YTDLP_ARGS_FILE'  => $argumentsFile,
                'FAKE_YTDLP_ENV_FILE'   => $environmentFile,
                'FAKE_YTDLP_INT_ENV'    => 456,
                'FAKE_YTDLP_EXIT_CODE'  => 0,
                'FAKE_YTDLP_MODE'       => 'json',
            ],
        );

        $result = $client->run(YtDlpRequest::create('https://test.local/video'));

        self::assertTrue($result->isSuccessful());
        self::assertSame(['123', '1'], \array_slice($this->savedArguments($argumentsFile), 0, 2));
        self::assertSame("456\n", \file_get_contents($environmentFile));
    }

    /**
     * Checks that environment values keep Symfony Process EnvArray semantics.
     */
    public function testEnvironmentValuesKeepProcessSemantics(): void
    {
        $binary          = $this->createFakeBinary();
        $environmentFile = \sprintf('%s/passthrough-env.txt', $this->temporaryDirectory);

        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable-value';
            }
        };

        $client = new YtDlpClient(
            binaryPath: $binary,
            environment: [
                'FAKE_YTDLP_BC_ENV_FILE'    => $environmentFile,
                'FAKE_YTDLP_STRINGABLE_ENV' => $stringable,
                'FAKE_YTDLP_UNSET_ENV'      => false,
                'FAKE_YTDLP_NULL_ENV'       => null,
                'FAKE_YTDLP_EXIT_CODE'      => '0',
            ],
        );

        $client->run(YtDlpRequest::create('https://test.local/video'));

        self::assertSame(
            "STRINGABLE=stringable-value\nUNSET=UNSET\nNULL=\n",
            \file_get_contents($environmentFile),
        );
    }

    private function createFakeBinary(): string
    {
        return $this->createNamedFakeBinary('fake-yt-dlp.sh');
    }

    private function createNamedFakeBinary(string $name): string
    {
        $binary = \sprintf('%s/%s', $this->temporaryDirectory, $name);

        $script = <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

if [[ -n "${FAKE_YTDLP_ARGS_FILE:-}" ]]; then
  for arg in "$@"; do
    printf '%s\n' "$arg" >> "$FAKE_YTDLP_ARGS_FILE"
  done
fi

if [[ -n "${FAKE_YTDLP_ENV_FILE:-}" ]]; then
  printf '%s\n' "${FAKE_YTDLP_INT_ENV:-}" >> "$FAKE_YTDLP_ENV_FILE"
fi

if [[ -n "${FAKE_YTDLP_BC_ENV_FILE:-}" ]]; then
  printf 'STRINGABLE=%s\n' "${FAKE_YTDLP_STRINGABLE_ENV-UNSET}" >> "$FAKE_YTDLP_BC_ENV_FILE"
  printf 'UNSET=%s\n' "${FAKE_YTDLP_UNSET_ENV-UNSET}" >> "$FAKE_YTDLP_BC_ENV_FILE"
  printf 'NULL=%s\n' "${FAKE_YTDLP_NULL_ENV-UNSET}" >> "$FAKE_YTDLP_BC_ENV_FILE"
fi

mode="${FAKE_YTDLP_MODE:-json}"
if [[ "$mode" == "json" ]]; then
  printf '%s\n' '{"id":"video-1","title":"One"}'
  printf '\n'
  printf '%s\n' 'not-json-line'
  printf '\n'
  printf '%s\n' '{"id":"video-2","title":"Two"}'
elif [[ "$mode" == "array" ]]; then
  printf '%s\n' '[1,2]'
fi

printf '%s\n' 'stderr-line' >&2
exit "${FAKE_YTDLP_EXIT_CODE:-0}"
BASH;

        \file_put_contents($binary, $script);
        \chmod($binary, 0755);

        return $binary;
    }

    /**
     * Reads the arguments captured by the fake binary.
     *
     * @return string[]
     */
    private function savedArguments(string $path): array
    {
        return \file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
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

            $full = \sprintf('%s/%s', $path, $item);
            if (\is_dir($full)) {
                $this->removeDir($full);
            } else {
                @\unlink($full);
            }
        }

        @\rmdir($path);
    }
}
