# Laravel WebDAV Server (SabreDAV + Flysystem)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/n3xt0r/laravel-webdav-server.svg?style=flat-square)](https://packagist.org/packages/n3xt0r/laravel-webdav-server)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=N3XT0R_laravel-webdav-server&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=N3XT0R_laravel-webdav-server)
[![Develop Status](https://img.shields.io/badge/develop-beta-yellow?style=flat-square)](https://github.com/N3XT0R/laravel-webdav-server/tree/develop)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/n3xt0r/laravel-webdav-server/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/n3xt0r/laravel-webdav-server/actions)
[![Read the Docs](https://readthedocs.org/projects/laravel-webdav-server/badge/?version=latest)](https://laravel-webdav-server.readthedocs.io/en/latest/?badge=latest)
[![Maintainability](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server/maintainability.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server)
[![Code Coverage](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server/coverage.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server)

A WebDAV server package for Laravel powered by SabreDAV and Laravel Flysystem.

Expose Laravel storage disks through `/webdav/{space}/{path?}` and map each request to a configured storage space plus a
user-scoped root path.

> Current version: **1.0.0-beta.1**

> [!IMPORTANT]
> The public package API, configuration structure, and extension points are treated as structurally stable.
> The beta phase is focused on hardening, compatibility validation, documentation, and bug fixes rather than
> structural API changes.

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

## Stability

- the package is now in `beta`
- package contracts, DTOs, configuration keys, and the route structure are now considered structurally stable
- future beta changes should remain additive or bug-fix oriented instead of reshaping the public package API

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
