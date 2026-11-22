<?php

declare(strict_types=1);

namespace P3s\YtDlp;

use P3s\YtDlp\Exception\BinaryNotFoundException;
use P3s\YtDlp\Exception\YtDlpException;
use Symfony\Component\Process\Process;

final readonly class YtDlpClient implements YtDlpClientInterface
{
    public const string DEFAULT_BINARY = 'yt-dlp';
    public const float DEFAULT_TIMEOUT = 300.0;

    /**
     * @param scalar[]                                $defaultArguments
     * @param array<string, string|\Stringable|false> $environment
     */
    public function __construct(
        private string $binaryPath = self::DEFAULT_BINARY,
        private array $defaultArguments = [],
        private ?string $workingDirectory = null,
        private float $timeout = self::DEFAULT_TIMEOUT,
        private array $environment = [],
    ) {
    }

    #[\Override]
    public function run(YtDlpRequest $request): ProcessResult
    {
        $resolvedBinaryPath = $this->resolveBinaryPath();
        $command            = $this->buildCommand($request, $resolvedBinaryPath);

        $process = new Process(
            $command,
            $this->workingDirectory,
            $this->environment,
            null,
            $this->timeout,
        );

        $process->run();

        $stdout = $process->getOutput();

        return new ProcessResult(
            command: $command,
            exitCode: $process->getExitCode() ?? 1,
            stdout: $stdout,
            stderr: $process->getErrorOutput(),
            jsonLines: $this->extractJsonObjects($stdout),
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
            flags: ['dump-json', 'skip-download', 'no-warnings'],
        );

        return $this->run($request);
    }

    private function resolveBinaryPath(): string
    {
        if (self::DEFAULT_BINARY === $this->binaryPath) {
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
        $path     = (string) ($this->environment['PATH'] ?? (false === $hostPath ? '' : $hostPath));

        if ('' === $path) {
            return null;
        }

        foreach (\explode(PATH_SEPARATOR, $path) as $directory) {
            if ('' === $directory) {
                continue;
            }

            $candidate = \sprintf('%s%s%s', \rtrim($directory, '/\\'), DIRECTORY_SEPARATOR, $binary);

            if (\is_file($candidate) && \is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function buildCommand(YtDlpRequest $request, string $binaryPath): array
    {
        if ([] === $request->urls) {
            throw new YtDlpException('At least one URL is required.');
        }

        $command = [$binaryPath];

        $this->appendDefaultArguments($command, $this->defaultArguments);
        $this->appendFlags($command, $request->flags);
        $this->appendOptions($command, $request->options);
        $this->appendExtraArguments($command, $request->extraArguments);

        $extraArguments    = $request->extraArguments;
        $lastExtraArgument = \end($extraArguments);

        if ('--' !== $lastExtraArgument) {
            $command[] = '--';
        }

        foreach ($request->urls as $url) {
            $command[] = $url;
        }

        return $command;
    }

    /**
     * @param string[] $command
     * @param scalar[] $defaultArguments
     */
    private function appendDefaultArguments(array &$command, array $defaultArguments): void
    {
        foreach ($defaultArguments as $argument) {
            $command[] = (string) $argument;
        }
    }

    /**
     * @param string[] $command
     * @param string[] $flags
     */
    private function appendFlags(array &$command, array $flags): void
    {
        foreach ($flags as $flag) {
            $command[] = $this->normalizeOptionName($flag);
        }
    }

    /**
     * @param string[]                            $command
     * @param array<string, scalar|scalar[]|null> $options
     */
    private function appendOptions(array &$command, array $options): void
    {
        foreach ($options as $option => $value) {
            $this->appendOptionValue($command, $this->normalizeOptionName($option), $value);
        }
    }

    /**
     * @param string[]                             $command
     * @param array<array-key, scalar>|scalar|null $value
     */
    private function appendOptionValue(
        array &$command,
        string $optionName,
        int|float|string|bool|array|null $value,
    ): void {
        if (null === $value) {
            return;
        }

        if (\is_array($value)) {
            foreach ($value as $item) {
                $command[] = $optionName;
                $command[] = (string) $item;
            }

            return;
        }

        if (\is_bool($value)) {
            if ($value) {
                $command[] = $optionName;
            }

            return;
        }

        $command[] = $optionName;
        $command[] = (string) $value;
    }

    /**
     * @param string[] $command
     * @param string[] $extraArguments
     */
    private function appendExtraArguments(array &$command, array $extraArguments): void
    {
        foreach ($extraArguments as $argument) {
            $command[] = $argument;
        }
    }

    private function normalizeOptionName(string $option): string
    {
        $trimmed = \trim($option);

        if (\str_starts_with($trimmed, '-')) {
            return $trimmed;
        }

        return \sprintf('--%s', $trimmed);
    }

    /**
     * @return array<array-key, mixed>[]
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

            if (\is_array($decoded)) {
                $json[] = $decoded;
            }
        }

        return $json;
    }
}
