<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Request\Routing;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Server\SpaceKeyResolverInterface;
use RuntimeException;

final readonly class RequestSpaceKeyResolver implements SpaceKeyResolverInterface
{
    public function resolve(Request $request): string
    {
        $space = $request->route('space');

        if (is_string($space) && trim($space) !== '') {
            return trim($space);
        }

        $defaultSpace = config('webdav-server.storage.default_space', 'default');

        if (! is_string($defaultSpace) || trim($defaultSpace) === '') {
            throw new RuntimeException(
                'Missing or invalid webdav-server.storage.default_space configuration.'
            );
        }

        return trim($defaultSpace);
    }
}
