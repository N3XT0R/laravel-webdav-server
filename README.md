# Laravel WebDAV Server (SabreDAV + Flysystem)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/n3xt0r/laravel-webdav-server.svg?style=flat-square)](https://packagist.org/packages/n3xt0r/laravel-webdav-server)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=N3XT0R_laravel-webdav-server&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=N3XT0R_laravel-webdav-server)
[![Develop Status](https://img.shields.io/badge/develop-unstable-orange?style=flat-square)](https://github.com/N3XT0R/laravel-webdav-server/tree/develop)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/n3xt0r/laravel-webdav-server/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/n3xt0r/laravel-webdav-server/actions)
[![Read the Docs](https://readthedocs.org/projects/laravel-webdav-server/badge/?version=latest)](https://laravel-webdav-server.readthedocs.io/en/latest/?badge=latest)
[![Maintainability](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server/maintainability.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server)
[![Code Coverage](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server/coverage.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server)
[![Total Downloads](https://img.shields.io/packagist/dt/n3xt0r/laravel-webdav-server.svg?style=flat-square)](https://packagist.org/packages/n3xt0r/laravel-webdav-server)

A **WebDAV server for Laravel** built on **SabreDAV**, fully integrated with **Laravel Flysystem**.

Expose your Laravel storage (local, S3, etc.) as a **WebDAV endpoint** and access it from any WebDAV client.

> Current version: **1.0.0-alpha.3**

---

> [!WARNING]
> This package is currently under active development and not yet production-ready.  
> APIs, configuration keys, and behaviour may change without notice between releases.  
> Use in production at your own risk.

---

## 🚀 Quickstart (Docker Development Setup)

Start the development container:

```bash
docker compose up --build -d
```

Start the WebDAV server inside the container:

```bash
docker compose exec php composer run serve
```

WebDAV endpoint:

```
http://localhost:8000/webdav/default/
```

---

## 🔑 Test Credentials

Seeded automatically:

```
Username: testuser
Password: password
```

---

## 🧪 Verify WebDAV (30 seconds)

```bash
curl -u testuser:password -X PROPFIND http://localhost:8000/webdav/default/
```

✔ If you get an XML response → WebDAV server is working

---

## 🧭 What is this?

**Laravel WebDAV Server** is a **WebDAV server implementation for Laravel applications**.

It allows you to expose your application's storage (Flysystem disks) via the WebDAV protocol.

This is not a standalone application, but a **developer-focused package** designed to:

- integrate WebDAV into existing Laravel systems
- map incoming WebDAV requests to Laravel storage disks
- process uploaded files directly within your application logic

Typical use cases include:

- handling file uploads via WebDAV instead of HTTP forms
- integrating external systems that rely on WebDAV
- building custom file processing pipelines on top of Laravel storage

---

## ❗ Server, not Client

This package:

- ✅ provides a WebDAV server for Laravel
- ❌ does NOT provide a WebDAV client (`Storage::disk('webdav')`)

---

## 🔑 Features

- WebDAV server powered by SabreDAV
- Laravel Flysystem integration (local, S3, etc.)
- User-based storage mapping
- Pluggable authentication
- Laravel policy-based authorization
- Contract-driven, extensible architecture

---

## 📦 Installation

```bash
composer require n3xt0r/laravel-webdav-server
php artisan vendor:publish --tag="laravel-webdav-server-config"
php artisan migrate
```

---

## ⚙️ Basic WebDAV Route

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

---

## 🔐 Authentication & Authorization

- Authentication: HTTP Basic Auth
- Authorization: Laravel Policies (`Gate`)

Abilities:

- read
- write
- delete
- createDirectory
- createFile

---

## 🔌 Extension Points

- `CredentialValidatorInterface`
- `SpaceResolverInterface`
- `PathAuthorizationInterface`
- `ServerRunnerInterface`

---

## 📡 Supported WebDAV Methods

- PROPFIND
- GET
- PUT
- DELETE
- MKCOL

---

## 🧪 Tested Clients

- WinSCP
- macOS Finder
- Windows Explorer
- Cyberduck

---

## ⚠️ Stability

> Version `1.0.0-alpha.3`  
> This package is in **alpha stage**

---

## 📚 Documentation

- docs/getting-started.md
- docs/configuration.md
- docs/architecture.md
- docs/authentication.md

---

## 🛠 Developer Commands

```bash
composer run test
composer run lint
composer run test:lint
composer run serve
```

---

## 📄 License

MIT License
