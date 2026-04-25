# Getting Started: User-Specific WebDAV

This package is built around user-isolated storage through pluggable authentication, storage resolution, and path
authorization.

## Configure Storage Spaces

```php
return [
    'auth' => [
        'account_model' => \App\Models\WebDavAccount::class,
        'user_model' => \App\Models\User::class,
    ],
    'storage' => [
        'default_space' => 'default',
        'spaces' => [
            'default' => [
                'disk' => 'local',
                'root' => 'webdav',
            ],
        ],
    ],
];
```

## How the URL Resolves to User Storage

- URL: `GET /webdav/default/myfile.pdf` with Basic Auth
- `{space}` parameter: `default`
- `RequestSpaceKeyResolver` resolves the route-level `spaceKey`
- `SpaceResolverInterface` resolves that key to one concrete storage target
- the default resolver builds `{root}[/prefix]/{principal.id}`

With `root = webdav` and authenticated principal `42`, the effective WebDAV root becomes `webdav/42`.

## Create Your Policy

The package registers its own reference policy by default.
If you want application-specific rules, register your own policy for `PathResourceDto`:

```php
use App\Policies\PathPolicy;
use Illuminate\Support\Facades\Gate;
use N3XT0R\LaravelWebdavServer\DTO\Auth\PathResourceDto;

public function boot(): void
{
    Gate::policy(PathResourceDto::class, PathPolicy::class);
}
```

```php
namespace App\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use N3XT0R\LaravelWebdavServer\DTO\Auth\PathResourceDto;

final class PathPolicy
{
    public function read(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    public function write(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    public function delete(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    public function createDirectory(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    public function createFile(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    private function isUserPath(Authenticatable $user, PathResourceDto $resource): bool
    {
        return str_starts_with($resource->path, 'webdav/'.$user->getAuthIdentifier().'/')
            || $resource->path === 'webdav/'.$user->getAuthIdentifier();
    }
}
```

## Authentication

The package uses independent Basic Auth, not Laravel's `auth()` middleware.

- credentials are validated against the configured account model
- username and password columns are configurable
- successful authentication resolves a `WebDavPrincipalValueObject`
- invalid credentials are surfaced as package auth exceptions

## Access from Clients

```text
macOS:   webdav://your-domain.test/webdav/default
Linux:   dav://your-domain.test/webdav/default
Windows: \\your-domain.test\webdav
```

Use the username and password from your configured WebDAV account records.
