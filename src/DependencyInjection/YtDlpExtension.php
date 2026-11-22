<?php

declare(strict_types=1);

namespace P3s\YtDlp\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class YtDlpExtension extends Extension
{
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();

        $processedConfiguration = $this->processConfiguration($configuration, $configs);

        $container->setParameter('yt_dlp.binary_path', $processedConfiguration['binary_path']);
        $container->setParameter('yt_dlp.default_arguments', $processedConfiguration['default_arguments']);
        $container->setParameter('yt_dlp.working_directory', $processedConfiguration['working_directory']);
        $container->setParameter('yt_dlp.timeout', $processedConfiguration['timeout']);
        $container->setParameter('yt_dlp.environment', $processedConfiguration['environment']);

        $configDirectory = \sprintf('%s/../../config', __DIR__);

        new PhpFileLoader($container, new FileLocator($configDirectory))
            ->load('services.php')
        ;
    }
}
