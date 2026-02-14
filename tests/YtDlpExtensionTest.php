<?php

declare(strict_types=1);

namespace P3s\YtDlp\Tests;

use P3s\YtDlp\DependencyInjection\YtDlpExtension;
use P3s\YtDlp\YtDlpClient;
use P3s\YtDlp\YtDlpClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class YtDlpExtensionTest extends TestCase
{
    public function testLoadsDefaultConfigurationAndServices(): void
    {
        $container = new ContainerBuilder();
        $extension = new YtDlpExtension();

        $extension->load([], $container);

        self::assertSame('yt-dlp', $container->getParameter('yt_dlp.binary_path'));
        self::assertSame(['--no-warnings'], $container->getParameter('yt_dlp.default_arguments'));
        self::assertSame(300.0, $container->getParameter('yt_dlp.timeout'));
        self::assertTrue($container->hasDefinition(YtDlpClient::class));
        self::assertTrue($container->hasAlias(YtDlpClientInterface::class));
    }

    public function testLoadsCustomConfiguration(): void
    {
        $container = new ContainerBuilder();
        $extension = new YtDlpExtension();

        $extension->load([
            [
                'binary_path'       => '/usr/local/bin/yt-dlp',
                'default_arguments' => ['--no-warnings', '--no-progress'],
                'working_directory' => '/tmp/videos',
                'timeout'           => 99.5,
                'environment'       => ['HTTP_PROXY' => 'http://127.0.0.1:8080'],
            ],
        ], $container);

        self::assertSame('/usr/local/bin/yt-dlp', $container->getParameter('yt_dlp.binary_path'));
        self::assertSame(['--no-warnings', '--no-progress'], $container->getParameter('yt_dlp.default_arguments'));
        self::assertSame('/tmp/videos', $container->getParameter('yt_dlp.working_directory'));
        self::assertSame(99.5, $container->getParameter('yt_dlp.timeout'));
        self::assertSame(['HTTP_PROXY' => 'http://127.0.0.1:8080'], $container->getParameter('yt_dlp.environment'));
    }
}
