<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Nodes;

final class StreamGetContentsOverride
{
    public static bool $shouldFail = false;
}

function stream_get_contents($stream, ?int $length = null, int $offset = -1): string|false
{
    if (StreamGetContentsOverride::$shouldFail) {
        return false;
    }

    if ($length === null && $offset === -1) {
        return \stream_get_contents($stream);
    }

    return \stream_get_contents($stream, $length ?? -1, $offset);
}
