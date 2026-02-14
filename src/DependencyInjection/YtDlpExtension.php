<?php

declare(strict_types=1);

namespace P3s\YtDlp\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class YtDlpExtension extends Extension
{
    /**
     * @param array<mixed> $configs
     */
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $container->setParameter('yt_dlp.binary_path', $config['binary_path']);
        $container->setParameter('yt_dlp.default_arguments', $config['default_arguments']);
        $container->setParameter('yt_dlp.working_directory', $config['working_directory']);
        $container->setParameter('yt_dlp.timeout', $config['timeout']);
        $container->setParameter('yt_dlp.environment', $config['environment']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');
    }
}
