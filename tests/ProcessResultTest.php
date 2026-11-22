<?php

declare(strict_types=1);

namespace P3s\YtDlp\Tests;

use P3s\YtDlp\Exception\ProcessFailedException;
use P3s\YtDlp\ProcessResult;
use PHPUnit\Framework\TestCase;

final class ProcessResultTest extends TestCase
{
    /**
     * Checks that requiring success on a successful result returns the same result.
     */
    public function testRequireSuccessfulReturnsSelfForSuccess(): void
    {
        $result = new ProcessResult(['yt-dlp', '--version'], 0, 'ok', '');

        self::assertSame($result, $result->requireSuccessful());
    }

    /**
     * Checks that requiring success on a failed result throws with the result attached.
     */
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

    /**
     * Checks that a custom failure message and all result fields are preserved.
     */
    public function testProcessFailureKeepsCustomMessageAndResultFields(): void
    {
        $result = new ProcessResult(
            ['yt-dlp', '--version'],
            5,
            'out',
            'err',
            [['id' => 'video-1']],
        );

        $exception = new ProcessFailedException($result, 'custom failure');

        self::assertSame('custom failure', $exception->getMessage());
        self::assertSame(5, $exception->getCode());
        self::assertSame(['yt-dlp', '--version'], $exception->getResult()->command);
        self::assertSame('out', $exception->getResult()->stdout);
        self::assertSame('err', $exception->getResult()->stderr);
        self::assertSame([['id' => 'video-1']], $exception->getResult()->jsonLines);
    }
}
