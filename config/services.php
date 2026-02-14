<?php

declare(strict_types=1);

use P3s\YtDlp\YtDlpClient;
use P3s\YtDlp\YtDlpClientInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(YtDlpClient::class)
        ->args([
            '$binaryPath' => '%yt_dlp.binary_path%',
            '$defaultArguments' => '%yt_dlp.default_arguments%',
            '$workingDirectory' => '%yt_dlp.working_directory%',
            '$timeout' => '%yt_dlp.timeout%',
            '$environment' => '%yt_dlp.environment%',
        ]);

    $services->alias(YtDlpClientInterface::class, YtDlpClient::class);
};
