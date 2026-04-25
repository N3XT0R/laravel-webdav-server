<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Request\Routing;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Server\SpaceKeyResolverInterface;
use N3XT0R\LaravelWebdavServer\Exception\Storage\InvalidDefaultSpaceConfigurationException;

final readonly class RequestSpaceKeyResolver implements SpaceKeyResolverInterface
{
    /**
     * Resolve the logical storage space key from the request route or the configured default.
     *
     * @param  Request  $request  Incoming HTTP request targeting the WebDAV endpoint.
     * @return string Logical storage space key used for downstream storage resolution.
     *
     * @throws InvalidDefaultSpaceConfigurationException When no valid fallback space key is configured.
     */
    public function resolve(Request $request): string
    {
        $space = $request->route('space');

        if (is_string($space) && trim($space) !== '') {
            return trim($space);
        }

        $defaultSpace = config('webdav-server.storage.default_space', 'default');

        if (! is_string($defaultSpace) || trim($defaultSpace) === '') {
            throw new InvalidDefaultSpaceConfigurationException(
                'Missing or invalid webdav-server.storage.default_space configuration.',
            );
        }

        return trim($defaultSpace);
    }
}
