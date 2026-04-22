# 0007. SabreDAV Runtime Decoupling

## Status

Accepted

## Context

This package is built on top of SabreDAV, but its public architecture is intended to remain Laravel-first and
contract-driven.

SabreDAV is responsible for protocol execution, request handling details, and response emission once a configured
`Server` instance starts running.

At the same time, the package needs the Laravel-side application model to stay decoupled from SabreDAV-specific
classes and runtime behavior as much as possible.

If SabreDAV concerns are allowed to spread freely across controllers, auth, storage resolution, and authorization,
several problems appear:

- package internals become harder to test because SabreDAV runtime execution is invasive
- transport-specific classes leak into business-facing extension points
- replacing collaborators or adapting runtime behavior becomes harder
- Laravel-side architecture becomes shaped by SabreDAV internals instead of package contracts

The current implementation already maintains an explicit boundary:

- `WebDavController` handles request entry and basic auth-attempt checks
- `WebDavServerFactory` orchestrates request context resolution, storage root construction, and server configuration
- request context, auth, storage routing, and authorization are expressed through package contracts and DTOs
- `ServerRunnerInterface` is the final runtime boundary
- the default runtime adapter is `SabreServerRunner`, which executes `server->start(); exit;`

## Decision

The package keeps SabreDAV as the protocol runtime engine, but isolates SabreDAV-specific execution behind dedicated
package boundaries.

The architectural rules are:

- package extension points are defined in package contracts, not in SabreDAV interfaces
- request context, principal resolution, storage space mapping, and authorization are expressed in package DTOs and
  value objects
- SabreDAV `Server` construction is centralized in `WebDavServerFactory`
- runtime execution is delegated through `ServerRunnerInterface`
- the default `SabreServerRunner` is the place where SabreDAV takes over the request lifecycle

This means the package integrates with SabreDAV at the runtime edge, while the rest of the package speaks primarily in
Laravel- and package-level concepts.

## Consequences

Positive consequences:

- most package internals can evolve around package contracts instead of third-party runtime types
- testing stays tractable because SabreDAV process termination is isolated to one adapter boundary
- consuming applications override package collaborators through Laravel bindings instead of patching SabreDAV wiring
- the package keeps a clearer separation between protocol runtime and Laravel-side domain orchestration

Trade-offs:

- SabreDAV is still a hard runtime dependency and cannot be considered fully abstracted away
- one more adapter boundary exists between request orchestration and final protocol execution
- developers still need some SabreDAV knowledge when working at the runtime edge

Rejected alternatives:

- call SabreDAV runtime APIs directly throughout controllers and collaborators
  - rejected because it would couple package internals tightly to SabreDAV and weaken testability and replaceability
- hide the full request pipeline inside one SabreDAV-facing integration class
  - rejected because it would obscure the package architecture and reduce contract-driven extension points
- attempt to abstract all SabreDAV types out of existence
  - rejected because the package is explicitly a SabreDAV-based WebDAV server and still needs a concrete protocol engine
