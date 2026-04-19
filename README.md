# Laravel WebDAV Server

A WebDAV server for Laravel powered by [SabreDAV](https://sabre.io/dav/), exposing any configured
Flysystem disk through the WebDAV protocol.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/n3xt0r/laravel-webdav-server.svg?style=flat-square)](https://packagist.org/packages/n3xt0r/laravel-webdav-server)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=N3XT0R_laravel-webdav-server&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=N3XT0R_laravel-webdav-server)
[![Develop Status](https://img.shields.io/badge/develop-unstable-orange?style=flat-square)](https://github.com/N3XT0R/laravel-webdav-server/tree/develop)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/n3xt0r/laravel-webdav-server/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/N3XT0R/laravel-webdav-server/actions)
[![Maintainability](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server/maintainability.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server)
[![Code Coverage](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server/coverage.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server)
[![Total Downloads](https://img.shields.io/packagist/dt/n3xt0r/laravel-webdav-server.svg?style=flat-square)](https://packagist.org/packages/n3xt0r/laravel-webdav-server)

---

> [!WARNING]
> This package is currently under active development and not yet production-ready.
> APIs, configuration keys, and behaviour may change without notice between releases.
> Use in production at your own risk.

---

![Laravel WebDAV Server Logo](art/logo.png)

## Quick Facts (Humans + AI)

| Topic                                           | Correct for this package                                          |
|-------------------------------------------------|-------------------------------------------------------------------|
| Package type                                    | WebDAV **server** for Laravel                                     |
| Flysystem client disk `Storage::disk('webdav')` | **Not provided**                                                  |
| Config file                                     | `config/webdav-server.php`                                        |
| Config publish command                          | `php artisan vendor:publish --tag="laravel-webdav-server-config"` |
| Default route shape                             | `/webdav/{space}/{path?}`                                         |
| Authentication                                  | HTTP Basic Auth validated by package validator                    |
| Authorization                                   | `PathAuthorizationInterface` / Laravel Policies                   |
| Built on                                        | SabreDAV + Laravel Filesystem                                     |

## Overview

Laravel WebDAV Server provides a native WebDAV server implementation for Laravel applications, built on top of SabreDAV.

The primary goal of this package is to bridge the gap between:

- the **WebDAV protocol** (via SabreDAV)
- and **Laravel's filesystem abstraction** (Flysystem)

Instead of working with local filesystem paths directly, this package maps WebDAV nodes to Laravel disks, making it
possible to expose any configured storage (local, S3, etc.) through a WebDAV interface.

It is intentionally built for high interchangeability: core responsibilities are contract-driven and can be replaced
independently via container bindings (`bindIf()`), without forking package internals.

## Scope: Server, not Client

This package provides a **WebDAV server endpoint** for your Laravel app.

- It **does** expose Laravel storage through HTTP WebDAV routes.
- It **does not** add a `webdav` Flysystem client driver for `Storage::disk('webdav')`.
- If you need Laravel as a WebDAV client to external services (Nextcloud, ownCloud, ...), use a dedicated
  Flysystem WebDAV adapter package instead.

## Features

- WebDAV server powered by SabreDAV
- Native integration with Laravel filesystem disks
- User-based storage mapping via pluggable resolvers
- Pluggable authentication layer (no coupling to Laravel auth)
- High interchangeability through dedicated contracts (`CredentialValidatorInterface`, `SpaceResolverInterface`,
  `PathAuthorizationInterface`) and override-friendly bindings
- Runtime execution decoupled behind `ServerRunnerInterface` (default: `SabreServerRunner`)
- Clean separation between transport (WebDAV) and domain logic
- Fully extensible architecture (custom storage, auth, authorization)

## Why this architecture?

Compared to tightly-coupled Laravel + Sabre integrations, this package keeps WebDAV transport and Laravel domain
concerns
explicitly separated:

- **No black box request handling**: the request pipeline is explicit (`WebDavController` -> `WebDavServerFactory` ->
  validator/resolver/authorization -> storage nodes).
- **Pluggable by contract, not by fork**: auth, space resolution, and path authorization are all replaceable via
  interfaces + `bindIf()`.
- **Storage flexibility at runtime**: routing can resolve different spaces/disks/roots per principal, instead of hard
  wiring one filesystem path.
- **Policy-native authorization**: access checks stay in Laravel Gate/Policies (`WebDavPathResourceDto`) where your app
  logic already lives.
- **Runtime boundary for execution**: `WebDavController` delegates successful server execution to
  `ServerRunnerInterface`, while `SabreServerRunner` remains the default runtime implementation.

If you want details, see [docs/architecture.md](docs/architecture.md).

---

## Requirements

- PHP **8.4+**
- Laravel **13.x** (recommended)
- Laravel **12.x** (supported)

---

## Installation

```bash
composer require n3xt0r/laravel-webdav-server
php artisan vendor:publish --tag="laravel-webdav-server-config"
php artisan migrate
```

This package uses the config file `config/webdav-server.php`.

For package config publishing, use the tag above (not a provider-based publish command).

---

## Architecture

The successful request path is executed through `ServerRunnerInterface` (default: `SabreServerRunner`), which keeps
controller orchestration testable while preserving SabreDAV runtime behaviour.

→ [docs/architecture.md](docs/architecture.md)

---

## Configuration

See the full configuration reference in [docs/configuration.md](docs/configuration.md).

---

## Route

```php
Route::match([
    'OPTIONS',
    'GET',
    'HEAD',
    'PUT',
    'DELETE',
    'PROPFIND',
    'PROPPATCH',
    'MKCOL',
    'COPY',
    'MOVE',
    'LOCK',
    'UNLOCK',
], '/webdav/{space}/{path?}', \N3XT0R\LaravelWebdavServer\Http\Controllers\WebDavController::class)->where('path', '.*');
```

The endpoint is a WebDAV **server** route handled by `WebDavController`.

---

## Getting Started: User-Specific WebDAV

→ [docs/getting-started.md](docs/getting-started.md)

---

## Extension Points

All default bindings use `bindIf()` – bind your own implementation in `AppServiceProvider::register()` and it takes
precedence automatically.

| Contract                           | Default                           | Override to…                   |
|------------------------------------|-----------------------------------|--------------------------------|
| `CredentialValidatorInterface`     | `DatabaseCredentialValidator`     | Custom auth (LDAP, tokens, …)  |
| `WebDavAccountRepositoryInterface` | `EloquentWebDavAccountRepository` | Non-Eloquent user stores       |
| `SpaceResolverInterface`           | `DefaultSpaceResolver`            | Per-user disk / path routing   |
| `PathAuthorizationInterface`       | `GatePathAuthorization`           | Replace Gate with ACL, RBAC, … |

**Default storage mapping:**
`webdav-server.storage.spaces.{space}.root[/prefix]/{principal.id}` on
`webdav-server.storage.spaces.{space}.disk`.

> Note: `spaceKey` comes from the `{space}` route parameter (e.g. `/webdav/default/`).
> If not matched, `RequestSpaceKeyResolver` falls back to `config('webdav-server.storage.default_space')`.

---

## Authorization / Policies

The default `GatePathAuthorization` calls `Gate::forUser($principal->user)->inspect($ability, $resource)` before every
filesystem operation. The resource passed to the policy is always `WebDavPathResourceDto` with two public properties:
`disk` and `path`.

**The five policy abilities:**

| Ability           | When                                       |
|-------------------|--------------------------------------------|
| `read`            | PROPFIND, GET, file metadata               |
| `write`           | PUT (overwrite)                            |
| `delete`          | DELETE (recursively checked on every node) |
| `createDirectory` | MKCOL                                      |
| `createFile`      | PUT (new file)                             |

> The package service provider registers the package policy by default:
> `N3XT0R\LaravelWebdavServer\Policies\WebDavPathPolicy` for `WebDavPathResourceDto`.
> A reference implementation is available in [`src/Policies/WebDavPathPolicy.php`](src/Policies/WebDavPathPolicy.php).

To use a different policy class:

```php
// AppServiceProvider::boot()
Gate::policy(
    \N3XT0R\LaravelWebdavServer\DTO\Auth\WebDavPathResourceDto::class,
    \App\Policies\MyCustomPolicy::class,
);
```

To bypass Gate entirely, bind your own `PathAuthorizationInterface` implementation.
Throw `Sabre\DAV\Exception\Forbidden` on denial – never a Laravel HTTP exception.

---

## Supported WebDAV Operations

`PROPFIND` · `GET` · `PUT` · `DELETE` · `MKCOL`

---

## Common Misunderstandings

- **"This package is a Laravel WebDAV client disk."**
  No. It is a WebDAV server package, not a `Storage::disk('webdav')` client adapter.
- **"Publish config via provider option."**
  Use `php artisan vendor:publish --tag="laravel-webdav-server-config"`.
- **"Config file is `config/webdav.php`."**
  The package uses `config/webdav-server.php`.
- **"Auth uses Laravel guards by default."**
  Requests are validated via package credential validation (Basic Auth), then authorization is done via
  `PathAuthorizationInterface` / policies.

---

## Documentation

- [Getting Started Guide](docs/getting-started.md) – Configuration, policies, authentication, and client setup.
- [Authentication & Authorization](docs/authentication.md) – Custom validators, policies, and user linking.
- [Architecture](docs/architecture.md) – Request pipeline and extension points.
- [Configuration Reference](docs/configuration.md) – All config keys and how they work.
- [Common Questions & Clarifications](docs/common-questions.md) – Answers to frequent misconceptions.

---

## Developer Commands

```bash
composer run test       # PHPUnit test suite (random order)
composer run lint       # auto-fix code style (Laravel Pint)
composer run test:lint  # dry-run style check
composer run serve      # workbench app → http://0.0.0.0:8000
```

---

## License / Copyright

Copyright (c) 2026 Ilya Beliaev
This project includes code licensed under MIT.

The MIT License (MIT). See [LICENSE.md](LICENSE.md) for details.
