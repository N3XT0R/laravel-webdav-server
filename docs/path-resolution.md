# Path Resolution

The package provides a dedicated service and Facade for resolving WebDAV paths outside of an active WebDAV request.
This is the recommended way to expose connection details to users or to resolve storage paths in background jobs and
controllers.

## Overview

Two operations are available:

- **`resolveUrl(string $spaceKey): string`** — the public WebDAV mount URL a client connects to. The same for all
  users of a space; does not include any user-specific path segment.
- **`resolvePath(WebDavPrincipalInterface $principal, string $spaceKey): string`** — the user-scoped disk-internal
  root path following the `{root}/{prefix}/{principal.id}` formula configured under
  `webdav-server.storage.spaces`.

Both are handled by `PathResolverService`, the single authoritative implementation of the path formula.
`DefaultSpaceResolver` delegates to it internally, so the formula lives in one place.

## WebDavPath Facade

```php
use N3XT0R\LaravelWebdavServer\Facades\WebDavPath;
```

### Resolving the mount URL

No principal needed — call `resolveUrl()` with the space key:

```php
$url = WebDavPath::resolveUrl('default');
// → 'https://your-app.test/webdav/default'
```

Useful for displaying the WebDAV endpoint in a UI or returning it in an API response.

### Resolving the user-scoped storage path

`resolvePath()` accepts any `AccountInterface` — the type returned by
`AccountRepositoryInterface::findEnabledByUsername()`. Fetch the WebDAV account first, then pass it directly:

```php
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\AccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\Facades\WebDavPath;

$account = app(AccountRepositoryInterface::class)->findEnabledByUsername($username);

$path = WebDavPath::resolvePath($account, 'default');
// → 'webdav/42'
```

The path is built from the space's `root`, optional `prefix`, and the principal ID stored in the configured
`webdav-server.auth.user_id_column`.

## Path Formula

Given a space configured as:

```php
'files' => [
    'disk'   => 'local',
    'root'   => 'webdav',
    'prefix' => 'uploads',
],
```

The resolved path for principal ID `42` is:

```
webdav/uploads/42
```

If `prefix` is absent, empty, or exactly `/`:

```
webdav/42
```

## WebDavPrincipalInterface

`resolvePath()` accepts anything implementing `WebDavPrincipalInterface`
(`Contracts\Auth\WebDavPrincipalInterface`), which requires only `getPrincipalId(): string`.

- `AccountInterface` extends `WebDavPrincipalInterface` — pass repository results directly.
- `WebDavPrincipalValueObject` implements `WebDavPrincipalInterface` — the internal pipeline type, available if
  needed.

## Replacing the Path Resolver

`PathResolverInterface` is bound via `bindIf()` and can be replaced in `AppServiceProvider::register()`:

```php
use App\Services\CustomPathResolver;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\PathResolverInterface;

public function register(): void
{
    $this->app->bind(PathResolverInterface::class, CustomPathResolver::class);
}
```

Your implementation receives `WebDavPrincipalInterface $principal` and `string $spaceKey`. The replacement applies
both to `WebDavPath::resolvePath()` calls and to `DefaultSpaceResolver` internally — storage routing and Facade
output stay consistent.

## Related Pages

- [Configuration Reference](configuration.md)
- [Authentication & Authorization](authentication.md)
- [Server Customization](server-customization.md)
