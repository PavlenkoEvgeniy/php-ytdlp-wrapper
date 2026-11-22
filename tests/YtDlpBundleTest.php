<?php

declare(strict_types=1);

namespace P3s\YtDlp\Tests;

use P3s\YtDlp\DependencyInjection\YtDlpExtension;
use P3s\YtDlp\YtDlpBundle;
use PHPUnit\Framework\TestCase;

final class YtDlpBundleTest extends TestCase
{
    /**
     * Checks that the bundle returns and caches its container extension.
     */
    public function testGetContainerExtensionReturnsAndCachesExtension(): void
    {
        $bundle = new YtDlpBundle();

        $extension = $bundle->getContainerExtension();

        self::assertInstanceOf(YtDlpExtension::class, $extension);
        self::assertSame($extension, $bundle->getContainerExtension());
    }

    /**
     * Checks that the extension is registered under the yt_dlp alias.
     */
    public function testExtensionAlias(): void
    {
        $bundle = new YtDlpBundle();

        self::assertSame('yt_dlp', $bundle->getContainerExtension()->getAlias());
    }
}
