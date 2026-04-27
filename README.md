# Laravel WebDAV Server (SabreDAV + Flysystem)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/n3xt0r/laravel-webdav-server.svg?style=flat-square)](https://packagist.org/packages/n3xt0r/laravel-webdav-server)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=N3XT0R_laravel-webdav-server&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=N3XT0R_laravel-webdav-server)
[![Develop Status](https://img.shields.io/badge/develop-beta-yellow?style=flat-square)](https://github.com/N3XT0R/laravel-webdav-server/tree/develop)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/n3xt0r/laravel-webdav-server/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/n3xt0r/laravel-webdav-server/actions)
[![Read the Docs](https://readthedocs.org/projects/laravel-webdav-server/badge/?version=latest)](https://laravel-webdav-server.readthedocs.io/en/latest/?badge=latest)
[![Maintainability](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server/maintainability.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server)
[![Code Coverage](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server/coverage.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server)
[![Enterprise Ready](https://img.shields.io/badge/enterprise-ready-blue?style=flat-square)](#stability)

---

A WebDAV server package for Laravel powered by SabreDAV and Laravel Flysystem.

It exposes Laravel storage disks through `/webdav/{space}/{path?}` and maps each request to a configured storage space
with a user-scoped root path.

## Documentation

The full documentation is available on Read the Docs:

👉 https://laravel-webdav-server.readthedocs.io/en/latest/

The same documentation is also available in this repository:

👉 [docs/](docs/)

Read the Docs provides a structured and navigable version of the same content.

Useful entry points:

- [Getting Started](https://laravel-webdav-server.readthedocs.io/en/latest/getting-started/)
- [Configuration](https://laravel-webdav-server.readthedocs.io/en/latest/configuration/)
- [Events](https://laravel-webdav-server.readthedocs.io/en/latest/events/)
- [Server Customization](https://laravel-webdav-server.readthedocs.io/en/latest/server-customization/)
- [Authentication & Authorization](https://laravel-webdav-server.readthedocs.io/en/latest/authentication/)
- [Architecture](https://laravel-webdav-server.readthedocs.io/en/latest/architecture/)
- [Commands](https://laravel-webdav-server.readthedocs.io/en/latest/commands/)
- [Common Questions](https://laravel-webdav-server.readthedocs.io/en/latest/common-questions/)

## The Problem

- Laravel does not provide a native WebDAV server.
- Many existing packages provide WebDAV clients, for example for Flysystem, but not a server.
- Thin SabreDAV integrations often behave like black boxes inside Laravel.
- Request flow, authentication, storage resolution, and routing are often difficult to inspect or influence.
- Customization often requires replacing large parts of the integration.
- Debugging is difficult because many WebDAV clients return generic errors or no useful diagnostics.
- Client behavior differs noticeably between Windows Explorer, macOS Finder, and WinSCP.
- Windows WebClient is especially strict about `OPTIONS`, `PROPFIND`, and response correctness.

## What This Package Provides

- a WebDAV server for Laravel
- an explicit request pipeline
- Laravel Flysystem integration for storage
- Laravel Gate / policy-based authorization by default
- structured storage mapping through `spaces`
- defined contracts and extension points instead of a black-box runtime
- Laravel events for WebDAV-side file and directory mutations
- logging for authentication, routing, storage resolution, and server behavior
- WebDAV behavior compatible with common clients, including Windows WebClient

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

For full setup and configuration details, use the documentation links above.

## Installation

```bash
composer require n3xt0r/laravel-webdav-server
php artisan vendor:publish --tag="laravel-webdav-server-config"
php artisan migrate
```

The package service provider is `N3XT0R\LaravelWebdavServer\WebdavServerServiceProvider`.

## Core Concepts

- Route shape: `/webdav/{space}/{path?}`
- Authentication: HTTP Basic Auth
- Storage mapping: configured `spaces`
- Authorization: `PathAuthorizationInterface`, backed by Laravel Gate / policies by default

See Read the Docs for the complete request flow, configuration reference, and extension model.

## Compatibility Notes

- The package implements WebDAV behavior compatible with common clients, including Windows WebClient.
- Windows WebClient depends on correct `OPTIONS` handling, valid `PROPFIND` responses, valid `207 Multi-Status`
  responses, root collection handling, and `MS-Author-Via: DAV`.
- Prefer `https://`, especially for Windows clients.
- Client-side constraints such as system configuration, local policy, and `http://` versus `https://` still apply.

## Developer Commands

```bash
composer run test
composer run lint
composer run test:lint
composer run serve
```

## License

MIT License
