# Laravel WebDAV Server

A WebDAV server package for Laravel powered by SabreDAV and Laravel's filesystem abstraction.

It exposes Laravel storage disks through a WebDAV endpoint and keeps request handling, storage resolution, and
authorization explicit.

## Start Here

Start with [Getting Started](getting-started.md) for installation and the first working setup.
Use [Configuration Reference](configuration.md) when you need the available keys and runtime options quickly.

- [Getting Started](getting-started.md)
- [Configuration Reference](configuration.md)
- [Events](events.md)
- [Server Customization](server-customization.md)
- [Authentication & Authorization](authentication.md)
- [Architecture](architecture.md)
- [Commands](commands.md)
- [Common Questions](common-questions.md)
- [Architectural Decision Records](adr/README.md)

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
- server customization through documented extension points and additional SabreDAV plugins
- SabreDAV runtime execution isolated behind package boundaries
- SOLID-oriented design with established patterns for recurring architectural problems

## Supported WebDAV Operations

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
