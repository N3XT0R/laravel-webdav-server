# Getting Started: User-Specific WebDAV

This package is built around user-isolated storage through pluggable authentication, storage resolution, and path
authorization.

!!! note
    This guide describes the default package integration points for routing, configuration, and extension.

## Overview

This page walks through the default setup flow:

1. configure a storage space
2. configure logging if needed
3. create a first WebDAV account
4. understand how the URL resolves to user storage
5. register a policy if you want application-specific authorization rules
6. connect with a WebDAV client

## Configure Storage Spaces

```php
return [
    'route_prefix' => 'webdav',
    'base_uri' => '/webdav/',
    'auth' => [
        'account_model' => \App\Models\WebDavAccount::class,
        'user_model' => \App\Models\User::class,
    ],
    'logging' => [
        'driver' => 'stack',
        'level' => 'info',
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

## Configure Logging

Package logging is optional and covers both package-level events and the SabreDAV logger integration.

- set `webdav-server.logging.driver` to a Laravel log channel such as `stack`, `single`, or `stderr` to enable logging
- set `webdav-server.logging.driver` to `null` to disable package and SabreDAV logging entirely
- use `webdav-server.logging.level` to control the minimum emitted level

Typical usage:

- `info` for relevant operational events such as authentication success or failure
- `debug` for request parsing, context resolution, space resolution, authorization checks, and SabreDAV runtime setup

## Create Your First WebDAV Account

The package includes artisan commands for managing records in the configured `webdav-server.auth.account_model`.

Create a first account:

```bash
php artisan laravel-webdav-server:account:create testuser s3cr3t --display-name="Test User" --user-id=1
```

Inspect the created account:

```bash
php artisan laravel-webdav-server:account:show testuser
```

List all WebDAV accounts:

```bash
php artisan laravel-webdav-server:account:list
```

Update an existing account:

```bash
php artisan laravel-webdav-server:account:update testuser --secret=n3w-s3cr3t --enable
```

Use `php artisan laravel-webdav-server` to see the package-specific command overview.

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
- if logging is enabled, authentication outcomes are logged at `info`
- debug logging traces credential extraction and principal resolution without logging secrets

## Access From Clients

```text
macOS:   webdav://your-domain.test/webdav/default
Linux:   dav://your-domain.test/webdav/default
Windows: \\your-domain.test\webdav
```

Use the username and password from your configured WebDAV account records.
