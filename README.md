# Laravel WebDAV Server (SabreDAV + Flysystem)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/n3xt0r/laravel-webdav-server.svg?style=flat-square)](https://packagist.org/packages/n3xt0r/laravel-webdav-server)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=N3XT0R_laravel-webdav-server&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=N3XT0R_laravel-webdav-server)
[![Develop Status](https://img.shields.io/badge/develop-beta-yellow?style=flat-square)](https://github.com/N3XT0R/laravel-webdav-server/tree/develop)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/n3xt0r/laravel-webdav-server/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/n3xt0r/laravel-webdav-server/actions)
[![Read the Docs](https://readthedocs.org/projects/laravel-webdav-server/badge/?version=latest)](https://laravel-webdav-server.readthedocs.io/en/latest/?badge=latest)
[![Maintainability](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server/maintainability.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server)
[![Code Coverage](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server/coverage.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server)
[![Enterprise Ready](https://img.shields.io/badge/enterprise-ready-blue?style=flat-square)](#stability)

A WebDAV server package for Laravel powered by SabreDAV and Laravel Flysystem. Designed for structured, policy-driven,
and extensible WebDAV integrations in Laravel applications.

Expose Laravel storage disks through `/webdav/{space}/{path?}` and map each request to a configured storage space plus a
user-scoped root path.

This README provides a quick overview. For full documentation, see Read the Docs.

> Current version: **1.0.0-beta.1**

> [!IMPORTANT]
> The public package API, configuration structure, and extension points are treated as structurally stable.
> The beta phase is focused on hardening, compatibility validation, documentation, and bug fixes rather than
> structural API changes.

## Documentation

Full documentation is available on Read the Docs:

👉 https://laravel-webdav-server.readthedocs.io/en/latest/

Start there if you want to:

- install and configure the package
- understand the request pipeline
- customize authentication, storage, or authorization
- extend the SabreDAV runtime with additional plugins
- explore architecture and extension points

Direct entry points:

- [Getting Started](https://laravel-webdav-server.readthedocs.io/en/latest/getting-started/)
- [Configuration](https://laravel-webdav-server.readthedocs.io/en/latest/configuration/)
- [Authentication & Authorization](https://laravel-webdav-server.readthedocs.io/en/latest/authentication/)
- [Architecture](https://laravel-webdav-server.readthedocs.io/en/latest/architecture/)
- [Common Questions](https://laravel-webdav-server.readthedocs.io/en/latest/common-questions/)
- [ADRs](https://laravel-webdav-server.readthedocs.io/en/latest/adr/README/)

## Use Cases

This package is a good fit if you want to:

- expose Laravel storage disks as a WebDAV server endpoint
- map requests to user-scoped storage roots
- integrate WebDAV access with Laravel-based authentication and authorization
- customize storage resolution, request handling, or SabreDAV runtime behavior without forking the package

This package is not a Flysystem WebDAV client disk.

## Where To Start

Use this README if you want a quick overview and a local development entry point.

Use Read the Docs if you want the full package documentation.

| If you want to...                         | Start here                                                                                               |
|-------------------------------------------|----------------------------------------------------------------------------------------------------------|
| run the package locally                   | [Quickstart](#quickstart)                                                                                |
| install it in an application              | [Getting Started](https://laravel-webdav-server.readthedocs.io/en/latest/getting-started/)               |
| configure spaces, auth, or logging        | [Configuration](https://laravel-webdav-server.readthedocs.io/en/latest/configuration/)                   |
| understand runtime flow and boundaries    | [Architecture](https://laravel-webdav-server.readthedocs.io/en/latest/architecture/)                     |
| customize auth, storage, or authorization | [Authentication & Authorization](https://laravel-webdav-server.readthedocs.io/en/latest/authentication/) |
| extend the SabreDAV runtime               | [Configuration](https://laravel-webdav-server.readthedocs.io/en/latest/configuration/)                   |
| review architectural decisions            | [ADRs](https://laravel-webdav-server.readthedocs.io/en/latest/adr/README/)                               |

## Quickstart

```bash
docker compose up --build -d
docker compose exec php composer run serve
```

Default endpoint:

```text
http://localhost:8000/webdav/default/
```

Seeded workbench credentials:

```text
Username: testuser
Password: password
```

Quick verification:

```bash
curl -u testuser:password -X PROPFIND http://localhost:8000/webdav/default/
```

For full setup, configuration, and extension guidance, use the documentation on Read the Docs.

## Installation

```bash
composer require n3xt0r/laravel-webdav-server
php artisan vendor:publish --tag="laravel-webdav-server-config"
php artisan migrate
```

The package service provider is `N3XT0R\LaravelWebdavServer\WebdavServerServiceProvider`.

For full installation and configuration guidance, use:

- [Getting Started](https://laravel-webdav-server.readthedocs.io/en/latest/getting-started/)
- [Configuration](https://laravel-webdav-server.readthedocs.io/en/latest/configuration/)

## What This Package Does

- provides a WebDAV server for Laravel
- exposes Flysystem disks such as `local` or `s3`
- resolves storage through named `space` keys under `webdav-server.storage.spaces.*`
- scopes each resolved storage root to `{root}[/prefix]/{principal.id}`
- authenticates requests through package contracts, not Laravel session auth
- authorizes path operations through `PathAuthorizationInterface`, backed by Laravel Gate / policies by default
- allows server customization through stable extension points and additional SabreDAV plugins

This package is a server integration, not a Flysystem WebDAV client disk.

## Stability

- the package is now in `beta`
- package contracts, DTOs, configuration keys, and the route structure are now considered structurally stable
- future beta changes should remain additive or bug-fix oriented instead of reshaping the public package API

## Route Shape

The effective WebDAV route shape is:

```text
/webdav/{space}/{path?}
```

Example:

```php
Route::match([
    'OPTIONS',
    'GET',
    'PUT',
    'DELETE',
    'PROPFIND',
    'MKCOL',
], '/webdav/{space}/{path?}', \N3XT0R\LaravelWebdavServer\Http\Controllers\WebDavController::class)
    ->where('path', '.*');
```

## Authentication And Authorization

- Authentication uses HTTP Basic Auth.
- Default auth flow uses `DatabaseCredentialValidator` plus `EloquentAccountRepository`.
- Authorization uses `PathAuthorizationInterface`.
- The default adapter is `GatePathAuthorization`, which passes `PathResourceDto` into Laravel Gate / policies.

Policy abilities:

- `read`
- `write`
- `delete`
- `createDirectory`
- `createFile`

## Extension Points

- `CredentialValidatorInterface`
- `AccountRepositoryInterface`
- `SpaceResolverInterface`
- `PathAuthorizationInterface`
- `RequestCredentialsExtractorInterface`
- `PrincipalAuthenticatorInterface`
- `RequestContextResolverInterface`
- `SpaceKeyResolverInterface`
- `StorageRootBuilderInterface`
- `ServerConfiguratorInterface`
- `ServerRunnerInterface`

Additional SabreDAV `ServerPlugin` instances can be registered from your application service provider via the package
container tag:

```php
use App\WebDav\Plugins\CustomSabrePlugin;
use N3XT0R\LaravelWebdavServer\WebdavServerServiceProvider;

public function register(): void
{
    $this->app->singleton(CustomSabrePlugin::class);
    $this->app->tag(
        [CustomSabrePlugin::class],
        WebdavServerServiceProvider::sabrePluginTag(),
    );
}
```

Tagged plugins are added after the package defaults during SabreDAV server configuration.

## Server Customization

The package keeps its public WebDAV API stable, but the SabreDAV runtime itself can still be extended.

Typical customization points:

- replace package contracts such as `CredentialValidatorInterface`, `SpaceResolverInterface`, or
  `PathAuthorizationInterface`
- keep the default configurator and attach additional SabreDAV `ServerPlugin` instances
- combine your own SabreDAV behavior with the package defaults instead of replacing the whole runtime setup

Example: register a custom SabreDAV plugin from your application:

```php
namespace App\WebDav\Plugins;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

final class CustomSabrePlugin extends ServerPlugin
{
    public function initialize(Server $server): void
    {
        $server->on('afterMethod:PROPFIND', function (): void {
            // Add custom behavior after PROPFIND handling.
        });
    }

    public function getPluginName(): string
    {
        return 'app-custom-sabre-plugin';
    }
}
```

```php
namespace App\Providers;

use App\WebDav\Plugins\CustomSabrePlugin;
use Illuminate\Support\ServiceProvider;
use N3XT0R\LaravelWebdavServer\WebdavServerServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CustomSabrePlugin::class);
        $this->app->tag(
            [CustomSabrePlugin::class],
            WebdavServerServiceProvider::sabrePluginTag(),
        );
    }
}
```

That plugin will then be added alongside the package defaults during `SabreServerConfigurator::configure()`.

## Request Pipeline

1. `WebDavController` accepts the request.
2. `WebDavServerFactory` resolves credentials, principal, `spaceKey`, and storage space through dedicated collaborators.
3. `StorageRootBuilder` creates the SabreDAV node tree.
4. `SabreServerConfigurator` applies runtime configuration such as the effective base URI and optional SabreDAV
   logging.
5. `ServerRunnerInterface` hands execution off to the runtime adapter.

The default runner is `SabreServerRunner`, which calls `Server::start()` and terminates the request lifecycle.

## Logging

Package logging is configured through `webdav-server.logging`.

- set `driver` to a Laravel log channel such as `stack`, `single`, or `stderr` to enable logging
- set `driver` to `null` to disable package and SabreDAV logging entirely
- use `level = info` for operational events such as authentication success or failure
- use `level = debug` to additionally trace credential extraction, request-context resolution, storage resolution, and
  SabreDAV server setup during development
- debug logging also traces Windows-relevant `OPTIONS` and root-level `PROPFIND` handling inside SabreDAV

## Supported WebDAV Methods

- `OPTIONS`
- `PROPFIND`
- `GET`
- `PUT`
- `DELETE`
- `MKCOL`

## Windows WebClient Notes

Windows Explorer / WebClient support depends on the client machine in addition to server behavior.

This package provides WebDAV responses compatible with Windows WebClient, including:

- `OPTIONS`
- `PROPFIND`
- `207 Multi-Status`
- `DAV` headers
- root collection handling
- `MS-Author-Via: DAV`

Client-side requirements still apply:

- the `WebClient` Windows service must be running
- Basic Auth over plain `http://` may require `BasicAuthLevel` to be enabled in the Windows registry, or use `https://`
- the package answers `OPTIONS` and root `PROPFIND` requests for `/webdav/{space}/` in a WebDAV-compatible way, but
  Windows client policy can still block non-HTTPS Basic Auth on the workstation
- for reliable Windows Explorer usage, prefer `https://`

## Tested Clients

- WinSCP
- macOS Finder
- Windows Explorer
- Cyberduck

## Documentation

The README is intentionally a quick entry point. The full documentation lives on Read the Docs:

👉 https://laravel-webdav-server.readthedocs.io/en/latest/

Read the Docs entry points:

- [Getting Started](https://laravel-webdav-server.readthedocs.io/en/latest/getting-started/)
- [Configuration](https://laravel-webdav-server.readthedocs.io/en/latest/configuration/)
- [Authentication & Authorization](https://laravel-webdav-server.readthedocs.io/en/latest/authentication/)
- [Architecture](https://laravel-webdav-server.readthedocs.io/en/latest/architecture/)
- [Common Questions](https://laravel-webdav-server.readthedocs.io/en/latest/common-questions/)
- [ADRs](https://laravel-webdav-server.readthedocs.io/en/latest/adr/README/)

Repository-local mirrors:

- [Getting Started](docs/getting-started.md)
- [Configuration](docs/configuration.md)
- [Authentication & Authorization](docs/authentication.md)
- [Architecture](docs/architecture.md)
- [Common Questions](docs/common-questions.md)
- [ADR Index](docs/adr/README.md)

## Developer Commands

```bash
composer run test
composer run lint
composer run test:lint
composer run serve
```

## License

MIT License
