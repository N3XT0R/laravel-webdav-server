# Laravel WebDAV Server

A WebDAV server implementation for Laravel based on SabreDAV, with seamless integration into Laravel's filesystem.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/n3xt0r/laravel-webdav-server.svg?style=flat-square)](https://packagist.org/packages/n3xt0r/laravel-webdav-server)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/n3xt0r/laravel-webdav-server/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/N3XT0R/laravel-webdav-server/actions)
[![Maintainability](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server/maintainability.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server)
[![Code Coverage](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server/coverage.svg)](https://qlty.sh/gh/N3XT0R/projects/laravel-webdav-server)
[![Total Downloads](https://img.shields.io/packagist/dt/n3xt0r/laravel-webdav-server.svg?style=flat-square)](https://packagist.org/packages/n3xt0r/laravel-webdav-server)
[![Latest Unstable Version](https://poser.pugx.org/n3xt0r/laravel-webdav-server/v/unstable)](https://packagist.org/packages/n3xt0r/laravel-webdav-server)

---

## Overview

**Laravel WebDAV Server** provides a native WebDAV server implementation for Laravel applications, built on top of
SabreDAV.

The primary goal of this package is to bridge the gap between:

- the WebDAV protocol (via SabreDAV)
- and Laravel’s filesystem abstraction (Flysystem)

Instead of working with local filesystem paths directly, this package maps WebDAV nodes to Laravel disks, making it
possible to expose any configured storage (local, S3, etc.) through a WebDAV interface.

---

## Features

- WebDAV server powered by SabreDAV
- Native integration with Laravel filesystem disks
- User-based storage mapping via pluggable resolvers
- Pluggable authentication layer (no coupling to Laravel auth)
- Clean separation between transport (WebDAV) and domain logic
- Fully extensible architecture (custom storage, auth, mapping)

---

## Architecture

The package is designed as a thin integration layer between SabreDAV and Laravel:

```
Request (WebDAV)
    ↓
SabreDAV Server
    ↓
Nodes (Collection / File / Directory)
    ↓
Laravel Filesystem (Storage::disk())
```

Core concepts:

- CredentialValidatorInterface → validates incoming WebDAV credentials
- WebDavAccountRepositoryInterface → abstracts account lookup
- SpaceResolverInterface → maps authenticated users to storage locations
- Storage Nodes → map WebDAV operations to Laravel filesystem operations

---

## Installation

Install via Composer:

```
composer require n3xt0r/laravel-webdav-server
```

Publish configuration:

```
php artisan vendor:publish --tag="laravel-webdav-server-config"
```

---

## Configuration

Example `config/webdav.php`:

```php
return [
    'base_uri' => '/webdav/',

    'storage' => [
        'disk' => 'local',
        'prefix' => 'webdav',
    ],

    'auth' => [
        'realm' => 'Laravel WebDAV',
    ],
];
```

---

## Usage

### 1. Define a route

```php
Route::any('/webdav/{path?}', \N3XT0R\LaravelWebdavServer\Http\Controllers\WebDavController::class)
    ->where('path', '.*');
```

---

### 2. Provide your own authentication

Implement:

```
N3XT0R\LaravelWebdavServer\Contracts\Auth\WebDavAccountRepositoryInterface
```

and bind it:

```php
$this->app->bind(
    WebDavAccountRepositoryInterface::class,
    \App\Repositories\UserWebDavAccountRepository::class
);
```

---

### 3. Customize storage mapping (optional)

Override the default space resolver:

```php
$this->app->bind(
    SpaceResolverInterface::class,
    \App\WebDav\UserSpaceResolver::class
);
```

---

## Example

Once configured, your WebDAV endpoint becomes available at:

```
http://your-app.test/webdav
```

You can connect using:

- Finder (macOS)
- Explorer (Windows)
- WebDAV clients (Cyberduck, cadaver, etc.)

---

## Supported Operations

- PROPFIND (directory listing)
- GET (download files)
- PUT (upload files)
- DELETE (files & directories)
- MKCOL (create directories)

---

## Notes

- Large file uploads may require optimization depending on your use case.
- The package intentionally avoids coupling to Laravel authentication or user models.
- All domain-specific behavior should be implemented in the host application.

---

## Testing

```
composer test
```

---

## Contributing

Contributions are welcome. Please ensure all tests pass and follow the existing code style.

---

## Credits

- Ilya Beliaev
- SabreDAV
- Laravel & Flysystem ecosystem

---

## License

The MIT License (MIT). See LICENSE.md for details.
