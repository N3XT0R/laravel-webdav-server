# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
