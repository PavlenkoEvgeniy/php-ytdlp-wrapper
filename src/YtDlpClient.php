<?php

declare(strict_types=1);

namespace P3s\YtDlp;

use P3s\YtDlp\Exception\BinaryNotFoundException;
use P3s\YtDlp\Exception\YtDlpException;
use Symfony\Component\Process\Process;

final class YtDlpClient implements YtDlpClientInterface
{
    /**
     * @param list<string>          $defaultArguments
     * @param array<string, string> $environment
     */
    public function __construct(
        private readonly string $binaryPath = 'yt-dlp',
        private readonly array $defaultArguments = [],
        private readonly ?string $workingDirectory = null,
        private readonly float $timeout = 300.0,
        private readonly array $environment = [],
    ) {
    }

    #[\Override]
    public function run(YtDlpRequest $request): ProcessResult
    {
        $resolvedBinaryPath = $this->resolveBinaryPath();

        $command = $this->buildCommand($request, $resolvedBinaryPath);
        $process = new Process(
            $command,
            $this->workingDirectory,
            $this->environment,
            null,
            $this->timeout
        );

        $process->run();

        return new ProcessResult(
            command: $command,
            exitCode: $process->getExitCode() ?? 1,
            stdout: $process->getOutput(),
            stderr: $process->getErrorOutput(),
            jsonLines: $this->extractJsonObjects($process->getOutput())
        );
    }

    #[\Override]
    public function download(string|array $urls, array $options = []): ProcessResult
    {
        $request = YtDlpRequest::create($urls, options: $options);

        return $this->run($request);
    }

    #[\Override]
    public function extractInfo(string|array $urls, array $options = []): array
    {
        $result = $this->rawJson($urls, $options)->requireSuccessful();

        return $result->jsonLines;
    }

    #[\Override]
    public function rawJson(string|array $urls, array $options = []): ProcessResult
    {
        $request = YtDlpRequest::create(
            urls: $urls,
            options: $options,
            flags: ['dump-json', 'skip-download', 'no-warnings']
        );

        return $this->run($request);
    }

    private function resolveBinaryPath(): string
    {
        if ('yt-dlp' === $this->binaryPath) {
            $resolvedPath = $this->findBinaryInPath($this->binaryPath);
            if (null === $resolvedPath) {
                throw BinaryNotFoundException::forPath($this->binaryPath);
            }

            return $resolvedPath;
        }

        if (!\is_file($this->binaryPath) || !\is_executable($this->binaryPath)) {
            throw BinaryNotFoundException::forPath($this->binaryPath);
        }

        return $this->binaryPath;
    }

    private function findBinaryInPath(string $binary): ?string
    {
        $hostPath = \getenv('PATH');
        $path     = $this->environment['PATH'] ?? (false === $hostPath ? '' : $hostPath);

        if ('' === $path) {
            return null;
        }

        foreach (\explode(PATH_SEPARATOR, $path) as $directory) {
            if ('' === $directory) {
                continue;
            }

            $candidate = \rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $binary;

            if (\is_file($candidate) && \is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function buildCommand(YtDlpRequest $request, string $binaryPath): array
    {
        if ([] === $request->urls) {
            throw new YtDlpException('At least one URL is required.');
        }

        $command = [$binaryPath];

        foreach ($this->defaultArguments as $argument) {
            $command[] = $argument;
        }

        foreach ($request->flags as $flag) {
            $command[] = $this->normalizeOptionName($flag);
        }

        foreach ($request->options as $option => $value) {
            $optionName = $this->normalizeOptionName($option);

            if (null === $value) {
                continue;
            }

            if (\is_array($value)) {
                foreach ($value as $item) {
                    $command[] = $optionName;
                    $command[] = (string) $item;
                }

                continue;
            }

            if (\is_bool($value)) {
                if ($value) {
                    $command[] = $optionName;
                }

                continue;
            }

            $command[] = $optionName;
            $command[] = (string) $value;
        }

        foreach ($request->extraArguments as $argument) {
            $command[] = $argument;
        }

        foreach ($request->urls as $url) {
            $command[] = $url;
        }

        return $command;
    }

    private function normalizeOptionName(string $option): string
    {
        $trimmed = \ltrim($option);

        if (\str_starts_with($trimmed, '--') || \str_starts_with($trimmed, '-')) {
            return $trimmed;
        }

        return '--' . \ltrim($trimmed, '-');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractJsonObjects(string $stdout): array
    {
        $lines = \preg_split('/\r\n|\r|\n/', \trim($stdout)) ?: [];
        $json  = [];

        foreach ($lines as $line) {
            if ('' === $line) {
                continue;
            }

            $decoded = \json_decode($line, true);

            if (JSON_ERROR_NONE === \json_last_error() && \is_array($decoded)) {
                $json[] = $decoded;
            }
        }

        return $json;
    }
}
