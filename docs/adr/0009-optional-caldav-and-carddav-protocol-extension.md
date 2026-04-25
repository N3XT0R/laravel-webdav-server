# 0009. Optional CalDAV And CardDAV Protocol Extension

## Status

Accepted

## Context

This package currently exposes a WebDAV server for Laravel and is architected around explicit request entrypoints,
contract-driven orchestration, runtime decoupling, and replaceable collaborators.

SabreDAV itself supports WebDAV, CalDAV, and CardDAV and provides protocol-specific plugins for extending a DAV server
with calendar and address book behavior.

The package therefore has a realistic path to support CalDAV and CardDAV, but several architectural risks must be
avoided:

- the package must not imply that CalDAV and CardDAV are always active or fully available by default
- protocol expansion must not collapse the current layered architecture into protocol-specific monoliths
- controller classes must not become server bootstrap scripts that mix request entry, auth, plugin registration, and
  backend wiring
- existing authentication and request-context logic must not be duplicated for each DAV protocol
- protocol-specific resource models such as calendars and address books must not be forced into the existing file/tree
  model used for WebDAV filesystem access

The current architecture already establishes the relevant boundaries:

- `WebDavController` handles request entry concerns
- `WebDavServerFactory` orchestrates server construction
- the request pipeline resolves credentials, principal, request context, and storage selection through dedicated
  collaborators
- SabreDAV runtime execution stays behind `ServerRunnerInterface`
- package extension points are expressed through contracts and default `bindIf()` bindings

CalDAV and CardDAV support must therefore be introduced as optional, configuration-driven protocol extensions that fit
those existing boundaries.

## Decision

The package supports CalDAV and CardDAV only as optional protocol extensions to the existing WebDAV server
architecture.

The architectural rules are:

- WebDAV remains the baseline protocol surface of the package
- CalDAV and CardDAV are explicit opt-in capabilities controlled through package configuration
- configuration must honestly express which protocol set is active:
  - WebDAV only
  - WebDAV + CardDAV
  - WebDAV + CalDAV
  - WebDAV + CardDAV + CalDAV
- the package must not imply full CalDAV/CardDAV availability unless the corresponding protocol is explicitly enabled

### Controllers

When enabled, each additional DAV protocol gets its own request entrypoint:

- `CardDavController`
- `CalDavController`

These controllers follow the same architectural role as `WebDavController`:

- they handle request entry concerns only
- they delegate server construction to the package orchestration layer
- they reuse the existing runtime boundary instead of embedding direct SabreDAV startup logic

### SabreDAV integration

CalDAV and CardDAV are integrated through the corresponding SabreDAV plugins rather than through a separate monolithic
server build path.

The package keeps one centralized server-construction model and extends it through plugin registration.

This means:

- protocol enablement decides whether a protocol extension participates in server setup
- plugin registration decides how the SabreDAV server is extended for that protocol
- protocol-specific setup must live in dedicated collaborators, not inline in controllers

### Authentication and request pipeline

CalDAV and CardDAV reuse the existing authentication and request-context pipeline wherever it remains semantically
valid.

In particular, the package must continue to reuse the existing mechanisms for:

- credential extraction
- principal authentication
- request-context resolution
- runtime orchestration

If protocol-specific context or resource resolution is needed, the existing pipeline may be extended through dedicated
collaborators, but not replaced with parallel, protocol-specific authentication flows.

### Resource and backend modeling

CalDAV and CardDAV may require protocol-specific backends, node structures, and principal/resource mappings.

These models must be introduced as distinct components and must not be forced into the existing WebDAV filesystem node
types.

Differences between file storage, calendar resources, and address book resources must be represented through clear
abstractions and dedicated collaborators.

### ADR compatibility

This protocol expansion must remain compatible with the existing architecture decisions of the project:

- request entry stays in controllers
- orchestration stays in factories, resolvers, builders, configurators, and related collaborators
- runtime execution stays behind the existing runtime boundary
- SabreDAV-specific behavior remains attached at the runtime/configuration edge
- extension continues through contracts and replaceable implementations

## Consequences

Positive consequences:

- the package can grow beyond file-oriented WebDAV without pretending those additional protocols are always available
- protocol enablement remains explicit and honest in configuration and documentation
- CardDAV and CalDAV can reuse the package's existing authentication and orchestration model
- SabreDAV protocol expansion stays plugin-based and aligned with the existing runtime boundary
- protocol-specific resource models can evolve without distorting the WebDAV filesystem architecture

Trade-offs:

- the package will need additional protocol-specific abstractions, controllers, and configuration paths
- not every WebDAV-oriented collaborator will automatically apply to CalDAV and CardDAV without extension
- protocol expansion increases the number of moving parts in configuration, testing, and runtime setup
- some DAV concepts such as calendars, address books, principals, and protocol plugins introduce additional domain
  complexity that the current package does not yet model

Rejected alternatives:

- treat CalDAV and CardDAV as implicit built-in capabilities of the package
  - rejected because it would overstate the package scope and hide protocol activation semantics
- add CalDAV and CardDAV by branching into dedicated monolithic SabreDAV bootstrap controllers
  - rejected because it would violate the current layered architecture and mix concerns
- build separate authentication and request pipelines for each DAV protocol
  - rejected because it would duplicate core behavior and weaken the architecture's shared orchestration model
- model calendar and address book resources as special cases of existing filesystem nodes
  - rejected because they are distinct protocol domains and should be represented through explicit abstractions
