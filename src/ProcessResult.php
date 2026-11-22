<?php

declare(strict_types=1);

namespace P3s\YtDlp;

use P3s\YtDlp\Exception\ProcessFailedException;

final readonly class ProcessResult
{
    /**
     * @param string[]                  $command
     * @param array<array-key, mixed>[] $jsonLines
     */
    public function __construct(
        public array $command,
        public int $exitCode,
        public string $stdout,
        public string $stderr,
        public array $jsonLines = [],
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
