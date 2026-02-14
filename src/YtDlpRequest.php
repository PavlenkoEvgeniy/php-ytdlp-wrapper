<?php

declare(strict_types=1);

namespace P3s\YtDlp;

final class YtDlpRequest
{
    /**
     * @param list<string>                            $urls
     * @param array<string, scalar|list<scalar>|null> $options
     * @param list<string>                            $flags
     * @param list<string>                            $extraArguments
     */
    public function __construct(
        public readonly array $urls,
        public readonly array $options = [],
        public readonly array $flags = [],
        public readonly array $extraArguments = [],
    ) {
    }

    /**
     * @param string|list<string>                     $urls
     * @param array<string, scalar|list<scalar>|null> $options
     * @param list<string>                            $flags
     * @param list<string>                            $extraArguments
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
            $extraArguments
        );
    }
}
