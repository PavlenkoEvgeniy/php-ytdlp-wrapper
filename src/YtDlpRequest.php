<?php

declare(strict_types=1);

namespace P3s\YtDlp;

final readonly class YtDlpRequest
{
    /**
     * @param string[]                            $urls
     * @param array<string, scalar|scalar[]|null> $options
     * @param string[]                            $flags
     * @param string[]                            $extraArguments
     */
    public function __construct(
        public array $urls,
        public array $options = [],
        public array $flags = [],
        public array $extraArguments = [],
    ) {
    }

    /**
     * @param string|string[]                     $urls
     * @param array<string, scalar|scalar[]|null> $options
     * @param string[]                            $flags
     * @param string[]                            $extraArguments
     */
    public static function create(
        string|array $urls,
        array $options = [],
        array $flags = [],
        array $extraArguments = [],
    ): self {
        return new self(
            \is_array($urls) ? $urls : [$urls],
            $options,
            $flags,
            $extraArguments,
        );
    }
}
