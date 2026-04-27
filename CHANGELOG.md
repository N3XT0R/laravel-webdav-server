# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **PathResolverService and WebDavPath Facade**
    - Added `PathResolverInterface` contract with `resolvePath()` for the user-scoped filesystem root path and `resolveUrl()` for the public WebDAV mount URL.
    - Added `PathResolverService` as the single authoritative implementation of the path assembly formula (`{root}/{prefix}/{principal.id}`); `DefaultSpaceResolver` now delegates to it internally instead of assembling the path inline.
    - Added `WebDavPath` Facade so application code can resolve a user's storage path or WebDAV mount URL without triggering the full WebDAV request pipeline — useful for controllers, views, and API responses that need to expose WebDAV endpoints to users.
    - Added `WebDavPrincipalInterface` contract (`Contracts\Auth`) with a single `getPrincipalId(): string` method; `AccountInterface` extends it and `WebDavPrincipalValueObject` implements it.
    - `resolvePath()` now accepts any `WebDavPrincipalInterface` — pass an `AccountInterface` returned by `AccountRepositoryInterface::findEnabledByUsername()` directly instead of constructing a value object manually.

- **events**
    - Added real Laravel events for WebDAV node mutations so applications can hook into file uploads and other
      filesystem changes without replacing node implementations or adding package-defined listeners.
    - The package now dispatches dedicated events for file creation, file updates, file deletion, directory creation,
      and directory deletion from the SabreDAV-backed storage nodes.
    - Added the concrete event classes `Events\WebDav\FileCreatedEvent`, `Events\WebDav\FileUpdatedEvent`,
      `Events\WebDav\FileDeletedEvent`, `Events\WebDav\DirectoryCreatedEvent`, and
      `Events\WebDav\DirectoryDeletedEvent`.
    - Event classes follow the ADR `0003` suffix convention and are dispatched through the Laravel event classes
      themselves.

### Changed

- **documentation**
    - Polished the README and Read the Docs pages for the upcoming `1.0.0` release to improve scanability,
      section flow, and entry-point visibility without changing the documented package behavior.
    - Split server-extension guidance out of `docs/configuration.md` into a dedicated `Server Customization` page so
      configuration reference content stays focused while SabreDAV runtime extension guidance remains fully documented.
    - Added a dedicated `events.md` page and linked it from Read the Docs, the repository `docs/` index, and the
      README so WebDAV node events are documented in one place.

## [1.0.0-beta.3] - 2026-04-27

### Added

- **Optional browser listing via SabreDAV Browser Plugin**
    - New `webdav-server.browser_listing` configuration key (default: `false`) to enable an HTML directory listing when
      accessing a WebDAV space from a browser.
    - When enabled, SabreDAV's `Browser\Plugin` is attached to the runtime, rendering a navigable file and directory
      view in the browser.
    - When enabled and a request arrives without credentials or with invalid credentials, the server issues an HTTP
      Basic Auth challenge (`WWW-Authenticate: Basic realm="WebDAV"`) so the browser displays its native login dialog
      instead of an error page.
    - the browser listing's built-in forms (create folder, file upload) now work correctly — `POST` is accepted on the
      WebDAV route so SabreDAV can process both form submissions internally.

### Changed

- **AccountManagementService**
    - Extracted account creation logic into a dedicated `AccountCreateService` and field-level update logic into
      `AccountUpdateService` so each class has a single, focused responsibility. `AccountManagementService` now
      orchestrates only; both public method signatures are unchanged.
    - Replaced `\InvalidArgumentException` with the domain-scoped `DuplicateUsernameException` in `create()` and
      `update()` so duplicate-username failures carry explicit package context and fit the exception hierarchy.
- **account:create and account:update commands**
    - Fixed the duplicate-username error handler in both commands to catch `DuplicateUsernameException` instead of
      `\InvalidArgumentException`, which was never matched after the exception type was aligned with the package
      exception hierarchy.

## [1.0.0-beta.2] - 2026-04-25

### Added

- **extensibility**
    - Added a package-defined container tag for user-defined SabreDAV `ServerPlugin` instances so applications can
      inject additional plugins alongside the package defaults during server configuration.
- **commands**
    - Added built-in artisan commands for creating, listing, showing, and updating WebDAV account records through the
      configured `webdav-server.auth.account_model`.

### Changed

- **compatibility**
    - Routed `OPTIONS` requests for `/webdav/{space}/{path?}` into SabreDAV so Windows Explorer / WebClient capability
      discovery reaches the DAV runtime instead of Laravel's method handling.
    - Hardened root-level `PROPFIND` handling with feature tests that cover `Depth: 0`, `Depth: 1`, empty storage
      roots, correct `207 Multi-Status` XML responses, and space-relative `href` values for `/webdav/{space}/`.
    - Handled missing-target `PROPFIND` requests such as file-creation probes as normal `404` DAV responses instead of
      bubbling `Sabre\DAV\Exception\NotFound` through the runtime and logging them as uncaught exceptions.
- **logging**
    - Added SabreDAV-side debug logs for Windows-relevant request handling, including DAV method processing,
      root-collection `PROPFIND`, request depth, and the effective `baseUri`.
- **documentation**
    - Documented Windows WebClient requirements and the package's `OPTIONS` / root-collection compatibility behavior in
      the README and RTD FAQ pages.
    - Clarified that the package emits Windows-compatible `OPTIONS`, `PROPFIND`, `207 Multi-Status`, `DAV`, root
      collection, and `MS-Author-Via: DAV` responses, while Windows Explorer itself still works most reliably over
      `https://`.
    - Documented how applications can register additional SabreDAV plugins through the package service-provider tag
      without replacing the default configurator.
    - Documented the built-in artisan account-management commands in the README, Getting Started guide,
      Authentication guide, and RTD overview.

## [1.0.0-beta.1] - 2026-04-25

### Changed

- **stability**
    - Declared the public package API, configuration structure, route shape, and documented extension points as
      structurally stable for the `1.0.0-beta.1` release line.
    - Aligned package documentation and release messaging so the package can be communicated as a beta focused on
      hardening, compatibility, and bug fixes instead of structural API changes.
- **configuration**
    - Added package-level logging configuration via `webdav-server.logging.driver` and `webdav-server.logging.level`.
    - `driver = null` now disables both package logging and SabreDAV runtime logging completely.
- **logging**
    - Added structured `info` logs for authentication outcomes and `debug` logs for credential extraction, request
      context resolution, storage resolution, Gate-based path authorization checks, WebDAV server construction, and
      SabreDAV runtime configuration.
    - Replaced the implicit SabreDAV logger wiring with a dedicated package logging service that resolves the
      configured Laravel log channel and applies package-level log filtering.
- **documentation**
    - Documented the new logging configuration and runtime logging behavior in `README.md` and
      `docs/configuration.md`.
    - Expanded the Read the Docs pages to cover the current logging behavior, available log levels, endpoint shape,
      and the runtime flow around authentication, authorization, and SabreDAV configuration.

## [1.0.0-alpha.4] - 2026-04-25

### Changed

- **configuration**
    - Removed legacy `webdav-server.storage.disk` and `webdav-server.storage.root` defaults from the package config
      stub so the default storage model is consistently defined through `webdav-server.storage.spaces.*`.
- **authorization**
    - Updated the packaged `PathPolicy` to evaluate access against the configured storage spaces, including
      optional `prefix` segments, instead of relying on the removed legacy storage keys.
- **package-api**
    - Renamed packaged extension-point contracts and DTOs to match the ADR naming target, including
      `AccountInterface`, `AccountRepositoryInterface`, `AccountRecordDto`, `PathResourceDto`, `RequestContextDto`,
      `PathPolicy`, and `EloquentAccountRepository`.
    - Kept `WebdavServerServiceProvider` at `src/` level, but aligned its internal registration and policy wiring with
      the refactored package API and current ADR structure.
- **exceptions**
    - Replaced nullable authentication and account lookup flows with domain-specific exceptions so package consumers can
      handle auth failures, account configuration issues, and storage resolution failures explicitly.
    - Added dedicated auth and storage exception hierarchies for invalid credentials, account lookup/configuration
      errors, missing user-model configuration, invalid default space configuration, invalid storage space
      configuration, missing spaces, and stream read failures.
- **documentation**
    - Aligned configuration and authorization docs with the current `spaces` model and clarified that applications
      override the packaged reference policy by registering their own policy for `PathResourceDto`.
    - Added `ADR 0008` to make SOLID compliance normative and to require established design patterns where they clearly
      fit recurring design problems.
    - Documented the package-wide PHPDoc standard for public methods in `ADR 0013`, including DX-oriented method
      descriptions, explicit parameter and exception docs, concrete array and collection element types, and the rule to
      prefer imported short class names over fully qualified class names inside docblocks.

## [1.0.0-alpha.3] - 2026-04-22

### Added

- **server-contracts**
    - Added dedicated server-layer contracts for request credential extraction, principal authentication, request
      context
      resolution, space key resolution, storage root building, and Sabre server configuration under
      `src/Contracts/Server/`.
    - Added `ServerRunnerInterface` to decouple controller runtime execution from direct `Server::start()` calls.
- **server-registers**
    - Added modular container registration classes under `src/Providers/Registers/`:
      `RepositoryRegister`, `AuthRegister`, `StorageRegister`, `ServerRegister`, and `WebDavRegisterFactory`.
- **tests**
    - Added focused unit tests for the extracted server components:
      `RequestBasicCredentialsExtractor`, `RequestSpaceKeyResolver`, and `ValidatorPrincipalAuthenticator`.
    - Added and extended HTTP feature tests for `WebDavController`, including auth-attempt branches and the valid
      Basic Auth happy-path delegation to the runner.
- **spaces**
    - Restored `{space}` URL segment (`/webdav/{space}/{path?}`) as the primary driver for per-request disk
      selection. `RequestSpaceKeyResolver` reads the segment from the route and falls back to
      `webdav-server.storage.default_space`. `DefaultSpaceResolver` maps the resolved key to a configured
      `WebDavStorageSpace` (disk + rootPath) via `webdav-server.storage.spaces.{spaceKey}`, so each space in the URL
      corresponds directly to a distinct Flysystem disk and root path.
    - `StorageNodeContextDto` carries the already-resolved `FilesystemAdapter` instance for the selected space,
      eliminating any need to re-select the disk inside node operations.
- **architecture-decision-records**
    - Added an ADR index under `docs/adr/README.md`.
    - Added `ADR 0001` for test architecture and layer boundaries.
    - Added `ADR 0002` for the explicit WebDAV request pipeline and the `ServerRunnerInterface` runtime boundary.
    - Added `ADR 0003` for class naming conventions by suffix.
    - Added `ADR 0004` to require continuous `CHANGELOG.md` maintenance in `Unreleased`, following Keep a Changelog
      and the project's current section/grouping style.

### Changed

- **webdav-server**
    - Refactored `WebDavServerFactory` into a pure orchestration component and delegated responsibilities to dedicated
      collaborators (`DefaultRequestContextResolver`, `StorageRootBuilder`, `SabreServerConfigurator`).
- **request-pipeline**
    - Introduced `WebDavRequestContextDto` as a structured transport object carrying `principal`, `spaceKey`, and
      resolved storage `space` through the factory pipeline.
- **namespaces**
    - Reorganized server implementation namespaces by responsibility:
      `Server\Factory`, `Server\Request\Auth`, `Server\Request\Context`, `Server\Request\Routing`,
      `Server\Auth`, `Server\Storage`, and `Server\Configuration`.
- **service-provider**
    - Simplified `WebdavServerServiceProvider::packageRegistered()` to delegate all bindings to
      `WebDavRegisterFactory::registerAll()`.
- **http**
    - Updated controller and provider imports to the new server sub-namespaces introduced by the refactor.
    - Refactored `WebDavController` to delegate runtime execution to `ServerRunnerInterface` (default:
      `SabreServerRunner`) for improved testability of the successful request path.
- **documentation**
    - Updated `docs/architecture.md` to reflect the current runtime flow, extracted server components,
      `webdav-server.*` configuration keys, and the current route shape.
- **testing**
    - Reworked the test suite to enforce real feature, integration, and unit boundaries.
    - Moved miscategorized tests to the correct layer and removed placeholder coverage.
    - Replaced PHPUnit mocks/stubs in the package test suite with concrete in-memory, recording, filesystem-backed,
      and Gate-backed test fixtures.
    - Standardized test execution on PHPUnit and documented Docker-based execution through the `php` service.
- **naming**
    - Renamed `WebDavPrincipal` to `WebDavPrincipalValueObject`.
    - Renamed `WebDavAccount` to `WebDavAccountModel`.
    - Renamed `WebDavStorageSpace` to `WebDavStorageSpaceValueObject`.
    - Renamed related factory and unit test files to match the new class names and keep PSR-4 and factory resolution
      consistent.
- **adr**
    - Broadened `ADR 0003` to cover legitimate project roles such as `*Backend`, `*Register`, `*File`, and
      `*Directory`, so the naming policy matches the actual Laravel and SabreDAV architecture.

### Fixed

- **authorization**
    - Fixed `TypeError` in `GatePathAuthorization::authorize()` caused by passing `null` to `method_exists()` when a
      `WebDavPrincipal` has no linked Laravel user (`$principal->user === null`). The null guard is now evaluated before
      calling `method_exists`.
- **auth**
    - Fixed missing empty-credential validation in `RequestBasicCredentialsExtractor` for the `PHP_AUTH_USER` /
      `PHP_AUTH_PW` code path. An empty username or password now consistently throws `InvalidCredentialsException`,
      matching the behaviour already enforced for the `Authorization` header path.
- **documentation**
    - Corrected architecture documentation mismatches around route shape (`/webdav/{path?}`), space key resolution
      fallback behavior, and base URI configuration source.

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
