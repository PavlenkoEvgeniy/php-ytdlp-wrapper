<?php

declare(strict_types=1);

namespace P3s\YtDlp;

use P3s\YtDlp\Exception\ProcessFailedException;

final class ProcessResult
{
    /**
     * @param list<string>               $command
     * @param list<array<string, mixed>> $jsonLines
     */
    public function __construct(
        public readonly array $command,
        public readonly int $exitCode,
        public readonly string $stdout,
        public readonly string $stderr,
        public readonly array $jsonLines = [],
    ) {
    }

    public function isSuccessful(): bool
    {
        return 0 === $this->exitCode;
    }

    public function requireSuccessful(): self
    {
        if (!$this->isSuccessful()) {
            throw new ProcessFailedException($this);
        }

        return $this;
    }
}
