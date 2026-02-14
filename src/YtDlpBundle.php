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
        if (null === $this->extension) {
            $this->extension = new YtDlpExtension();
        }

        if (!$this->extension instanceof YtDlpExtension) {
            throw new \LogicException('Invalid container extension instance.');
        }

        return $this->extension;
    }
}
