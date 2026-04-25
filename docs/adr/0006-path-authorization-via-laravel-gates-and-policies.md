# 0006. Path Authorization Via Laravel Gates And Policies

## Status

Accepted

## Context

This package authenticates WebDAV requests independently from Laravel guards, but authorization decisions still need to
reflect application-specific access rules.

The package therefore needs an authorization model that:

- can decide per operation and per path
- can work with Laravel users linked from authenticated WebDAV principals
- lets consuming applications express access rules in normal Laravel application code
- remains replaceable for applications that prefer ACL, RBAC, or another authorization backend

The package performs authorization before filesystem operations on WebDAV nodes such as reads, writes, deletes, and
directory creation.

If those authorization rules are hardcoded directly into node classes or spread across SabreDAV-specific code paths,
several problems appear:

- application access rules become harder to understand and maintain
- consuming applications lose the ability to reuse Laravel's existing authorization model
- transport-level code becomes mixed with domain-specific permission logic
- replacing the authorization strategy becomes harder

The current implementation already exposes a dedicated boundary:

- WebDAV nodes call `PathAuthorizationInterface`
- the default implementation is `GatePathAuthorization`
- `GatePathAuthorization` maps WebDAV operations to named abilities
- the policy resource is `PathResourceDto` with `disk` and `path`
- the package service provider registers `PathPolicy` for that resource by default

## Decision

The package uses Laravel Gates and Policies as the default path-authorization model while keeping authorization behind
`PathAuthorizationInterface`.

The architectural rules are:

- WebDAV nodes do not embed policy logic directly
- authorization is requested through `PathAuthorizationInterface`
- the default adapter delegates to `Gate::forUser(...)->inspect(...)`
- the policy resource passed through Gate is `PathResourceDto`
- WebDAV operations map to the five package abilities: `read`, `write`, `delete`, `createDirectory`, and `createFile`
- authorization denial is surfaced as `Sabre\DAV\Exception\Forbidden`, not as a Laravel HTTP exception

This keeps the default authorization model aligned with Laravel application conventions without making Gates and
Policies a hard requirement for the whole package.

## Consequences

Positive consequences:

- consuming applications can express WebDAV access rules with familiar Laravel Gates and Policies
- authorization logic stays out of node and transport classes
- access checks are operation-specific and path-aware
- the package keeps a clear override point through `PathAuthorizationInterface`
- the protocol layer still returns WebDAV-native denial behavior via `Sabre\DAV\Exception\Forbidden`

Trade-offs:

- the default authorization model depends on a Laravel user context being meaningful for the consuming application
- developers must understand the package-specific resource DTO and ability names
- the package introduces one translation layer between WebDAV operations and Laravel authorization calls

Rejected alternatives:

- implement authorization rules directly inside WebDAV node classes
  - rejected because it mixes domain policy with transport and filesystem behavior and reduces replaceability
- expose only one coarse-grained allow/deny hook for all operations
  - rejected because WebDAV operations have materially different semantics and need distinct policy decisions
- hardcode a non-Laravel ACL system as the default
  - rejected because the package is built for Laravel applications and should integrate cleanly with Laravel's standard
    authorization model by default
