# Laravel WebDAV Server

A WebDAV server package for Laravel powered by SabreDAV and Laravel's filesystem abstraction.

This package is now in `beta`. The package API, configuration structure, and extension points are documented as
structurally stable.

## Overview

This package exposes Laravel storage disks through a WebDAV endpoint.
It is designed around explicit request orchestration, pluggable authentication and authorization, and configurable
storage spaces.
Its internal architecture is intended to remain SOLID-compliant and to prefer established design patterns when they
fit recurring design problems.

Core characteristics:

- WebDAV server for Laravel, not a Flysystem WebDAV client disk
- storage selection through named `space` keys
- Basic Auth validation through package contracts
- built-in artisan commands for creating, listing, showing, and updating WebDAV account records
- path authorization through `PathAuthorizationInterface`, with Laravel Gate/Policy integration by default
- optional package and SabreDAV logging through `webdav-server.logging`
- server customization through stable extension points and additional SabreDAV plugins
- SabreDAV runtime execution isolated behind package boundaries
- SOLID-oriented design with established patterns for recurring architectural problems

## Stability

- the package is now in `beta`
- public contracts, DTOs, route shape, and configuration keys are now treated as structurally stable
- further changes are expected to focus on hardening, interoperability, documentation, and bug fixes

## Documentation

- [Getting Started](getting-started.md)
- [Configuration Reference](configuration.md)
- [Authentication & Authorization](authentication.md)
- [Architecture](architecture.md)
- [Common Questions](common-questions.md)
- [Architectural Decision Records](adr/README.md)

## Current Support Scope

Supported WebDAV operations:

- `OPTIONS`
- `PROPFIND`
- `GET`
- `PUT`
- `DELETE`
- `MKCOL`

The package route shape is `/webdav/{space}/{path?}` and the default client entry point is:

- `https://your-domain.test/webdav/default`

Tested clients:

- `WinSCP`
- `Cyberduck`
- macOS Finder
- Windows Explorer
