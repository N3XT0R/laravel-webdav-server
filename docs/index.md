# Laravel WebDAV Server

A WebDAV server package for Laravel powered by SabreDAV and Laravel's filesystem abstraction.

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
- path authorization through `PathAuthorizationInterface`, with Laravel Gate/Policy integration by default
- SabreDAV runtime execution isolated behind package boundaries
- SOLID-oriented design with established patterns for recurring architectural problems

## Documentation

- [Getting Started](getting-started.md)
- [Configuration Reference](configuration.md)
- [Authentication & Authorization](authentication.md)
- [Architecture](architecture.md)
- [Common Questions](common-questions.md)
- [Architectural Decision Records](adr/README.md)

## Current Support Scope

Supported WebDAV operations:

- `PROPFIND`
- `GET`
- `PUT`
- `DELETE`
- `MKCOL`

Tested client:

- `WinSCP`
