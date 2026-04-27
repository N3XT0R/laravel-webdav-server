<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Facades;

use Illuminate\Support\Facades\Facade;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\PathResolverInterface;

/**
 * @method static string resolvePath(\N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject $principal, string $spaceKey)
 * @method static string resolveUrl(string $spaceKey)
 *
 * @see PathResolverInterface
 */
final class WebDavPath extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PathResolverInterface::class;
    }
}
