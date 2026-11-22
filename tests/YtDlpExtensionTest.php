<?php

declare(strict_types=1);

namespace P3s\YtDlp\Tests;

use P3s\YtDlp\DependencyInjection\YtDlpExtension;
use P3s\YtDlp\YtDlpClient;
use P3s\YtDlp\YtDlpClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class YtDlpExtensionTest extends TestCase
{
    /**
     * Checks that default configuration and service wiring are registered.
     */
    public function testLoadsDefaultConfigurationAndServices(): void
    {
        $container = new ContainerBuilder();
        $extension = new YtDlpExtension();

        $extension->load([], $container);

        self::assertSame('yt-dlp', $container->getParameter('yt_dlp.binary_path'));
        self::assertSame(['--no-warnings'], $container->getParameter('yt_dlp.default_arguments'));
        self::assertNull($container->getParameter('yt_dlp.working_directory'));
        self::assertSame(300.0, $container->getParameter('yt_dlp.timeout'));
        self::assertSame([], $container->getParameter('yt_dlp.environment'));

        self::assertTrue($container->hasDefinition(YtDlpClient::class));
        self::assertTrue($container->hasAlias(YtDlpClientInterface::class));

        self::assertSame(
            [
                '$binaryPath'       => '%yt_dlp.binary_path%',
                '$defaultArguments' => '%yt_dlp.default_arguments%',
                '$workingDirectory' => '%yt_dlp.working_directory%',
                '$timeout'          => '%yt_dlp.timeout%',
                '$environment'      => '%yt_dlp.environment%',
            ],
            $container->getDefinition(YtDlpClient::class)->getArguments(),
        );
        self::assertSame(YtDlpClient::class, (string) $container->getAlias(YtDlpClientInterface::class));
    }

    /**
     * Checks that custom configuration values are stored as parameters.
     */
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

    /**
     * Checks that an empty binary path is rejected by the configuration tree.
     */
    public function testRejectsEmptyBinaryPath(): void
    {
        $extension = new YtDlpExtension();

        $this->expectException(InvalidConfigurationException::class);

        $extension->load([['binary_path' => '']], new ContainerBuilder());
    }

    /**
     * Checks that a negative timeout is rejected by the configuration tree.
     */
    public function testRejectsNegativeTimeout(): void
    {
        $extension = new YtDlpExtension();

        $this->expectException(InvalidConfigurationException::class);

        $extension->load([['timeout' => -1]], new ContainerBuilder());
    }

    /**
     * Checks that a zero timeout is accepted because zero disables the process timeout.
     */
    public function testAcceptsZeroTimeout(): void
    {
        $container = new ContainerBuilder();
        $extension = new YtDlpExtension();

        $extension->load([['timeout' => 0]], $container);

        self::assertSame(0.0, (float) $container->getParameter('yt_dlp.timeout'));
    }
}
