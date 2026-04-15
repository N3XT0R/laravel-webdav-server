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

The service provider auto-registers `App\Policies\WebDavPathPolicy`. Create it:

```php
// app/Policies/WebDavPathPolicy.php
namespace App\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use N3XT0R\LaravelWebdavServer\DTO\Auth\WebDavPathResourceDto;

class WebDavPathPolicy
{
    public function read(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return true; // Your authorization logic here
    }

    public function write(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return true;
    }

    public function delete(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return true;
    }

    public function createDirectory(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return true;
    }

    public function createFile(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return true;
    }
}
```

## Authentication (NOT Laravel auth())

The package uses **independent Basic Auth**, not Laravel's `auth()` middleware:

- Credentials are validated against `webdav.auth.account_model` table.
- Username/password columns are configurable.
- This is **not** dependent on Laravel session/guard auth.
- The authenticated user is represented as `WebDavPrincipal` (id, displayName, user relation).

## Access from Clients

```
Windows: \\your-domain.test\webdav
macOS:   webdav://your-domain.test/webdav/default
Linux:   dav://your-domain.test/webdav/default

Username: (from webdav_accounts table)
Password: (from webdav_accounts table, encrypted)
```

