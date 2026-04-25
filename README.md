# Laravel WebDAV Server (SabreDAV + Flysystem)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/n3xt0r/laravel-webdav-server.svg?style=flat-square)](https://packagist.org/packages/n3xt0r/laravel-webdav-server)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=N3XT0R_laravel-webdav-server&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=N3XT0R_laravel-webdav-server)
[![Develop Status](https://img.shields.io/badge/develop-unstable-orange?style=flat-square)](https://github.com/N3XT0R/laravel-webdav-server/tree/develop)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/n3xt0r/laravel-webdav-server/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/n3xt0r/laravel-webdav-server/actions)
[![Read the Docs](https://readthedocs.org/projects/laravel-webdav-server/badge/?version=latest)](https://laravel-webdav-server.readthedocs.io/en/latest/?badge=latest)
[![Maintainability](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server/maintainability.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server)
[![Code Coverage](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server/coverage.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server)

A WebDAV server package for Laravel powered by SabreDAV and Laravel Flysystem.

Expose Laravel storage disks through `/webdav/{space}/{path?}` and map each request to a configured storage space plus a
user-scoped root path.

> Current version: **1.0.0-alpha.4**

> [!WARNING]
> This package is under active development and not yet production-ready.
> APIs, configuration keys, and behavior may still change between alpha releases.

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

## What This Package Does

- provides a WebDAV server for Laravel
- exposes Flysystem disks such as `local` or `s3`
- resolves storage through named `space` keys under `webdav-server.storage.spaces.*`
- scopes each resolved storage root to `{root}[/prefix]/{principal.id}`
- authenticates requests through package contracts, not Laravel session auth
- authorizes path operations through `PathAuthorizationInterface`, backed by Laravel Gate / policies by default

This package is a server integration, not a Flysystem WebDAV client disk.

## Installation

```bash
composer require n3xt0r/laravel-webdav-server
php artisan vendor:publish --tag="laravel-webdav-server-config"
php artisan migrate
```

The package service provider is `N3XT0R\LaravelWebdavServer\WebdavServerServiceProvider`.

## Route Shape

The effective WebDAV route shape is:

```text
/webdav/{space}/{path?}
```

Example:

```php
Route::match([
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

## Request Pipeline

1. `WebDavController` accepts the request.
2. `WebDavServerFactory` resolves credentials, principal, `spaceKey`, and storage space through dedicated collaborators.
3. `StorageRootBuilder` creates the SabreDAV node tree.
4. `SabreServerConfigurator` applies runtime configuration such as the effective base URI.
5. `ServerRunnerInterface` hands execution off to the runtime adapter.

The default runner is `SabreServerRunner`, which calls `Server::start()` and terminates the request lifecycle.

## Supported WebDAV Methods

- `PROPFIND`
- `GET`
- `PUT`
- `DELETE`
- `MKCOL`

## Tested Clients

- WinSCP
- macOS Finder
- Windows Explorer
- Cyberduck

## Documentation

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
