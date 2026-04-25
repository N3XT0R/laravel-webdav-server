<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Stream;

final class UnreadableStreamWrapper
{
    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        return true;
    }

    public function stream_read(int $count): false
    {
        return false;
    }

    /**
     * @return array<string, int>
     */
    public function stream_stat(): array
    {
        return [];
    }

    public function stream_eof(): bool
    {
        return true;
    }
}
