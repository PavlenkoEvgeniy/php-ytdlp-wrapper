<?php

declare(strict_types=1);

namespace P3s\YtDlp\Exception;

use P3s\YtDlp\ProcessResult;

final class ProcessFailedException extends YtDlpException
{
    public function __construct(
        private readonly ProcessResult $result,
        string $message = '',
    ) {
        if ('' === $message) {
            $message = \sprintf(
                'yt-dlp command failed with exit code %d: %s',
                $result->exitCode,
                \implode(' ', $result->command)
            );
        }

        parent::__construct($message, $result->exitCode);
    }

    public function getResult(): ProcessResult
    {
        return $this->result;
    }
}
