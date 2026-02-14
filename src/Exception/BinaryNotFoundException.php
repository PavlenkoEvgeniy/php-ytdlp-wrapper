<?php

declare(strict_types=1);

namespace P3s\YtDlp\Exception;

final class BinaryNotFoundException extends YtDlpException
{
    public static function forPath(string $path): self
    {
        return new self(\sprintf('yt-dlp binary not found or not executable: "%s"', $path));
    }
}
