# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0-alpha.2] - 2026-04-19

### Added

- **http**
    - Proper SabreDAV response handling by delegating response output to `Server::start()` to preserve correct HTTP
      status codes and headers required by WebDAV clients.

### Changed

- **routing**
    - Updated WebDAV route handling to support full WebDAV method set (e.g. `OPTIONS`, `PROPFIND`, `PUT`, `MKCOL`, etc.)
      to ensure compatibility with standard WebDAV clients such as WinSCP.
- **server**
    - Dynamic `base_uri` resolution to include `{space}` segment (e.g. `/webdav/{space}/`) for correct SabreDAV path
      resolution and request mapping.
- **storage**
    - Standardized storage root resolution to user-scoped paths (`{root}/{userId}`) via `DefaultSpaceResolver`.
    - Normalized handling of `prefix` configuration, ignoring `/` as a no-op prefix.
- **authorization**
    - Adjusted default `WebDavPathPolicy` to operate on fully qualified storage paths (e.g. `webdav/{userId}/...`)
      instead of relative user paths.
    - Updated `GatePathAuthorization` to support nullable principals by bypassing Gate checks when no Laravel user is
      associated with the WebDAV account.

### Fixed

- **http**
    - Fixed incorrect response wrapping in `WebDavController` that previously forced all SabreDAV responses into a
      generic
      `200 OK` Laravel response, breaking WebDAV client behavior (e.g. upload preflight checks and conditional
      requests).
- **server**
    - Fixed incorrect base URI configuration that caused `{space}` to be interpreted as part of the file path instead of
      a routing segment.
- **storage**
    - Fixed duplicated user path segments (e.g. `webdav/1/1/...`) caused by combining URL-based user paths with
      resolver-based user scoping.
- **authorization**
    - Fixed path mismatch in default policy that incorrectly evaluated access against `{userId}` instead of
      `{root}/{userId}`.
    - Fixed Gate authorization failures caused by null user contexts when using account-based authentication without
      a linked Laravel user.
- **webdav**
    - Fixed `NotFound` errors during file creation caused by incorrect path resolution and invalid SabreDAV request
      handling flow.

## [1.0.0-alpha.1] - 2026-04-15

### Added

#### **webdav-server**

- Native WebDAV server integration for Laravel based on SabreDAV.
- Request pipeline with `WebDavController` and `WebDavServerFactory` for credential validation, storage space
  resolution, and SabreDAV server bootstrapping.
- Storage node tree (`StorageRootCollection`, `StorageDirectory`, `StorageFile`) mapped to Laravel filesystem disks.

#### **authentication**

- Basic Auth credential validation via `CredentialValidatorInterface` with default database-backed implementation.
- Pluggable storage space resolution via `SpaceResolverInterface` with support for route-based `{space}` selection and
  fallback to `webdav.storage.default_space`.

#### **authorization**

- Pluggable storage space resolution via `SpaceResolverInterface` with support for route-based `{space}` selection and
  fallback to `webdav.storage.default_space`.
- Storage node tree (`StorageRootCollection`, `StorageDirectory`, `StorageFile`) mapped to Laravel filesystem disks.
- Path-level authorization abstraction via `PathAuthorizationInterface` with default Gate-based policy integration.
- Policy resource DTO (`WebDavPathResourceDto`) and default policy registration for `read`, `write`, `delete`,
  `createDirectory`, and `createFile` abilities.

#### **middleware**

- Version-tolerant CSRF exclusion registration for WebDAV routes (`PreventRequestForgery` on Laravel 13+, fallback to
  `VerifyCsrfToken` on Laravel 12).

#### **configuration**

- Configurable package setup via `config/webdav-server.php`, including base URI, route prefix, storage spaces, and auth
  model mapping.

#### **extensibility**

- Extension-point bindings with `bindIf()` for repository, validator, resolver, and authorization implementations.

#### **documentation**

- Published package documentation for architecture and configuration in `docs/architecture.md` and
  `docs/configuration.md`.
