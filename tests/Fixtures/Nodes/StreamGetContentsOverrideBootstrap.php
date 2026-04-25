<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Nodes;

final class StreamGetContentsOverrideState
{
    public static bool $shouldFail = false;
}

function stream_get_contents($stream, ?int $length = null, int $offset = -1): string|false
{
    if (StreamGetContentsOverrideState::$shouldFail) {
        return false;
    }

    if ($length === null && $offset === -1) {
        return \stream_get_contents($stream);
    }

    return \stream_get_contents($stream, $length ?? -1, $offset);
}

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Nodes;

use N3XT0R\LaravelWebdavServer\Nodes\StreamGetContentsOverrideState;

final class StreamGetContentsOverrideBootstrap
{
    public static function failReads(): void
    {
        StreamGetContentsOverrideState::$shouldFail = true;
    }

    public static function reset(): void
    {
        StreamGetContentsOverrideState::$shouldFail = false;
    }
}
