# Common Questions & Clarifications

This document corrects common misconceptions based on the current package implementation.

## Q: Does the package support `{user_id}` placeholders in config paths?

No.

User isolation is resolved dynamically through `SpaceResolverInterface::resolve($principal, $spaceKey)`.
The default resolver returns a `WebDavStorageSpaceValueObject` whose `rootPath` already includes `$principal->id`.

## Q: Do I need a custom "AuthInterface" for authentication?

No. Use `CredentialValidatorInterface`.

## Q: Are there file-level authorization checks?

Not as a separate abstraction. The package authorizes at the path resource level.

Policies receive `PathResourceDto` with `disk` and `path`:

```php
use Illuminate\Contracts\Auth\Authenticatable;
use N3XT0R\LaravelWebdavServer\DTO\Auth\PathResourceDto;

final class PathPolicy
{
    public function read(Authenticatable $user, PathResourceDto $resource): bool
    {
        return str_starts_with($resource->path, 'webdav/'.$user->getAuthIdentifier().'/');
    }
}
```

## Q: Does the package support CalDAV or CardDAV?

No. The current package scope is WebDAV file access only.

ADR `0009` keeps CalDAV / CardDAV as an optional future extension path, not as an active feature.

## Q: How do I restrict access to specific spaces per user?

Override `SpaceResolverInterface`.

```php
use Illuminate\Contracts\Config\Repository as Config;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\Exception\Storage\SpaceNotConfiguredException;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final class RestrictedSpaceResolver implements SpaceResolverInterface
{
    public function __construct(private Config $config) {}

    public function resolve(WebDavPrincipalValueObject $principal, string $spaceKey): WebDavStorageSpaceValueObject
    {
        if ($spaceKey !== 'default' && ! $principal->user?->isAdmin()) {
            throw new SpaceNotConfiguredException('Unauthorized space.');
        }

        return new WebDavStorageSpaceValueObject(
            disk: 'local',
            rootPath: 'webdav/'.$principal->id,
        );
    }
}
```

## Q: How do I enable or disable package logging?

Use the `webdav-server.logging` config section.

```php
'logging' => [
    'driver' => 'stack',
    'level' => 'info',
],
```

- set `driver` to a Laravel log channel to enable logging
- set `driver` to `null` to disable package and SabreDAV logging entirely
- use `level` to choose the minimum emitted level, typically `info` or `debug`

At `info`, the package logs events such as successful or failed authentication and denied authorization checks.
At `debug`, it also logs request parsing, space resolution, Gate checks, server creation, and runtime configuration.

## Q: Can policies reject operations dynamically?

Yes. Policies are checked before every filesystem operation.

Mapped abilities:

| Ability           | Operation           |
|-------------------|---------------------|
| `read`            | `PROPFIND`, `GET`   |
| `write`           | `PUT` (overwrite)   |
| `delete`          | `DELETE`            |
| `createDirectory` | `MKCOL`             |
| `createFile`      | `PUT` (new file)    |

Denied access becomes `Sabre\DAV\Exception\Forbidden`.

## Q: Why not call `Server::start()` directly in `WebDavController`?

Because runtime execution is intentionally delegated through `ServerRunnerInterface`.

That keeps controller orchestration testable while the default `SabreServerRunner` still owns the final SabreDAV
handoff.

## Q: What exactly is the WebDAV endpoint URL?

The package route shape is `/webdav/{space}/{path?}`.

Examples:

- root of the default space: `/webdav/default`
- nested file in the default space: `/webdav/default/documents/report.pdf`

The effective SabreDAV base URI is configured separately through `webdav-server.base_uri`, which defaults to
`/webdav/`.

## Q: What does Windows Explorer / WebClient additionally require?

Server-side WebDAV compatibility is only one part of the setup.

This package provides WebDAV responses compatible with Windows WebClient, including:

- `OPTIONS`
- `PROPFIND`
- `207 Multi-Status`
- `DAV` headers
- root collection handling
- `MS-Author-Via: DAV`

On Windows, also verify:

- the `WebClient` service is running
- Basic Auth over plain `http://` is allowed on the machine, or use `https://`
- the target URL is entered with the trailing slash form, for example `http://localhost:8000/webdav/default/`

The package now answers Windows-relevant `OPTIONS` and root `PROPFIND` requests correctly, but Windows client policy
can still reject plain HTTP Basic Auth unless the workstation is configured for it.
For reliable Windows Explorer usage, prefer `https://`.

## Q: How do I link a WebDAV account to a Laravel user?

Set `webdav-server.auth.user_model` and make sure your configured account model exposes a `user()` relationship.

The resolved principal then carries the linked user as `$principal->user`, which is what Gate / policies receive.

If you use the default Eloquent-backed account model, you can create or update the link directly through the artisan
commands:

```bash
php artisan laravel-webdav-server:account:create testuser password --user-id=1
php artisan laravel-webdav-server:account:update testuser --user-id=1
```

## Q: Is this package production-ready?

Production use should still be deliberate.

The public package API is treated as structurally stable.
Validate HTTPS, credential handling, authorization rules, client interoperability, and expected filesystem load
before exposing it publicly.

## Q: Should I expect structural API changes?

No.

The package contracts, DTOs, route shape, and documented configuration keys are now intended to remain structurally
stable. Further changes should mainly be additive improvements, bug fixes, logging and documentation refinements, or
compatibility work.
