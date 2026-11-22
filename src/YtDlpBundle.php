<?php

declare(strict_types=1);

namespace P3s\YtDlp;

use P3s\YtDlp\DependencyInjection\YtDlpExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class YtDlpBundle extends Bundle
{
    #[\Override]
    public function getContainerExtension(): YtDlpExtension
    {
        $containerExtension = $this->extension;

        if (!$containerExtension instanceof YtDlpExtension) {
            $containerExtension = new YtDlpExtension();
            $this->extension    = $containerExtension;
        }

        return $containerExtension;
    }
}
