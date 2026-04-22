# Getting Started: User-Specific WebDAV

This package is designed for user-isolated storage through pluggable authentication and authorization.

## Configure Storage Spaces

```php
// config/webdav-server.php
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

## How URL Routes to User Storage

- URL: `GET /webdav/default/myfile.pdf` with Basic Auth
- `{space}` parameter: `default` (or any space key from config)
- `SpaceResolverInterface` resolves to: `local://webdav/{authenticated_principal_id}/myfile.pdf`
- Each authenticated user sees their own isolated directory tree.

## Create Your Policy

The package registers its own reference policy by default.
If you want app-specific rules, register your own policy for `WebDavPathResourceDto`:

```php
// AppServiceProvider::boot()
Gate::policy(
    \N3XT0R\LaravelWebdavServer\DTO\Auth\WebDavPathResourceDto::class,
    \App\Policies\WebDavPathPolicy::class,
);
```

```php
// app/Policies/WebDavPathPolicy.php
namespace App\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use N3XT0R\LaravelWebdavServer\DTO\Auth\WebDavPathResourceDto;

class WebDavPathPolicy
{
    public function read(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    public function write(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    public function delete(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    public function createDirectory(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    public function createFile(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    private function isUserPath(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return str_starts_with($resource->path, 'webdav/'.$user->getAuthIdentifier().'/')
            || $resource->path === 'webdav/'.$user->getAuthIdentifier();
    }
}
```

## Authentication (NOT Laravel auth())

The package uses **independent Basic Auth**, not Laravel's `auth()` middleware:

- Credentials are validated against `webdav-server.auth.account_model` table.
- Username/password columns are configurable.
- This is **not** dependent on Laravel session/guard auth.
- The authenticated user is represented as `WebDavPrincipalValueObject` (id, displayName, user relation).

## Access from Clients

```
Windows: \\your-domain.test\webdav
macOS:   webdav://your-domain.test/webdav/default
Linux:   dav://your-domain.test/webdav/default

Username: (from webdav_accounts table)
Password: (from webdav_accounts table, encrypted)
```
