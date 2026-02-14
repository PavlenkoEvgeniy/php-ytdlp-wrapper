<?php

declare(strict_types=1);

namespace P3s\YtDlp\Tests;

use P3s\YtDlp\Exception\ProcessFailedException;
use P3s\YtDlp\ProcessResult;
use PHPUnit\Framework\TestCase;

final class ProcessResultTest extends TestCase
{
    public function testRequireSuccessfulReturnsSelfForSuccess(): void
    {
        $result = new ProcessResult(['yt-dlp', '--version'], 0, 'ok', '');

        self::assertSame($result, $result->requireSuccessful());
    }

    public function testRequireSuccessfulThrowsOnFailureWithResultAttached(): void
    {
        $result = new ProcessResult(['yt-dlp', '--bad'], 2, '', 'error');

        try {
            $result->requireSuccessful();
            self::fail('Expected ProcessFailedException to be thrown.');
        } catch (ProcessFailedException $exception) {
            self::assertSame($result, $exception->getResult());
            self::assertSame(2, $exception->getCode());
            self::assertStringContainsString('yt-dlp command failed', $exception->getMessage());
        }
    }
}
