<?php

declare(strict_types=1);

namespace P3s\YtDlp;

interface YtDlpClientInterface
{
    public function run(YtDlpRequest $request): ProcessResult;

    /**
     * @param string|list<string>                     $urls
     * @param array<string, scalar|list<scalar>|null> $options
     */
    public function download(string|array $urls, array $options = []): ProcessResult;

    /**
     * @param string|list<string>                     $urls
     * @param array<string, scalar|list<scalar>|null> $options
     *
     * @return list<array<string, mixed>>
     */
    public function extractInfo(string|array $urls, array $options = []): array;

    /**
     * @param string|list<string>                     $urls
     * @param array<string, scalar|list<scalar>|null> $options
     */
    public function rawJson(string|array $urls, array $options = []): ProcessResult;
}
