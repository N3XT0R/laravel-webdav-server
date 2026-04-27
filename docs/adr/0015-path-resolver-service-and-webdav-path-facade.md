# 0015. PathResolverService And WebDavPath Facade For Centralized Path Resolution

## Status

Accepted

## Context

The package assembles user-scoped storage paths in two places. `DefaultSpaceResolver` reads the configured root and
optional prefix from the space definition and combines them with the authenticated principal's identifier to produce
the disk-relative path handed to Flysystem. Because this formula lived entirely inside `DefaultSpaceResolver`, it was
not accessible to application code without going through SabreDAV internals or duplicating the logic.

Consuming applications may need to:

- resolve the public WebDAV mount URL for a space (e.g. to display it in a UI or return it in an API response)
- resolve the disk-internal user path for a space (e.g. to run background jobs on a user's storage area)
- replace the path formula to apply a custom layout without replacing the full space resolver

Without a dedicated service, application code has no stable, replaceable access point for either operation. Duplicating
the path formula outside `DefaultSpaceResolver` would produce two sources of truth that can diverge silently.

ADR 0008 already identifies the Facade pattern as approved for "presenting a smaller, intention-revealing interface to a
more complex subsystem". The Laravel service container's `bindIf()` pattern, used throughout this package, makes any
interface-backed service replaceable without editing package source.

## Decision

A `WebDavPrincipalInterface` is introduced in `Contracts\Auth` with a single method `getPrincipalId(): string`.
`AccountInterface` extends it, and `WebDavPrincipalValueObject` implements it. This gives both the internal pipeline
and external application code a shared, minimal contract for principal identity without leaking the internal value
object into the public API.

A `PathResolverInterface` is introduced with two methods:

- `resolvePath(WebDavPrincipalInterface $principal, string $spaceKey): string` — returns the disk-internal,
  user-scoped path following the `{root}/{prefix}/{principal.id}` formula. Accepts any `AccountInterface` or
  `WebDavPrincipalValueObject`.
- `resolveUrl(string $spaceKey): string` — returns the public WebDAV mount URL for the given space key. This is the
  same URL for every user of a space; it does not include the user-scoped path.

`PathResolverService` is the default implementation. It reads the space configuration from the Laravel config
repository, validates the root, assembles the path parts, and builds the mount URL from `app.url` and
`webdav-server.route_prefix`.

`DefaultSpaceResolver` delegates the path formula to `PathResolverInterface` via constructor injection. It no longer
assembles the `{root}/{prefix}/{principal.id}` string itself.

`WebDavPath` is a Laravel Facade backed by `PathResolverInterface::class`. It exposes both methods as static calls
for ergonomic use in application code (views, controllers, job classes).

`PathResolverInterface` is bound to `PathResolverService` in `StorageRegister` via `bindIf()`. Applications that need
a custom path layout can replace only this binding in `AppServiceProvider::register()`.

The architectural rules are:

- `resolvePath()` is the single authoritative location for the user-scoped disk path formula
- `resolveUrl()` returns the public WebDAV mount URL only — it never includes the user-scoped path
- `DefaultSpaceResolver` must delegate to `PathResolverInterface`; it must not re-implement the formula
- the Facade accessor must point to the interface, not the concrete class, to keep the override path working

## Consequences

Advantages:

- the path formula lives once; `DefaultSpaceResolver` and application code share the same implementation
- `PathResolverInterface` is DI-replaceable via `bindIf()` without touching `DefaultSpaceResolver`
- the `WebDavPath` Facade gives application code (views, controllers, background jobs) ergonomic access to mount URLs
  and user paths
- the change introduces no BC break: `SpaceResolverInterface` is unchanged and `DefaultSpaceResolver` is `final`, so
  constructor changes are not a public API concern

Trade-offs:

- `resolveUrl()` intentionally excludes the user path; consumers building full per-user WebDAV URLs must call both
  `resolveUrl()` and `resolvePath()` and combine the results themselves — this is intentional because the public
  WebDAV address space and the internal disk layout are separate concerns
- an extra abstraction layer is added for what is ultimately string concatenation; the trade-off is justified by the
  single-source-of-truth and replaceability benefits

Rejected alternatives:

- keep the path formula inside `DefaultSpaceResolver` and expose a separate helper alongside it
  - rejected because it produces two sources of truth and offers no override path for the formula without replacing the
    full space resolver
- add a static helper function without a service contract
  - rejected because static helpers cannot be replaced via the container, which contradicts the package's
    extension-oriented architecture
- make `resolveUrl()` accept an optional `WebDavPrincipalValueObject` to optionally include the user path
  - rejected because the public WebDAV mount URL and the internal disk path are different concerns; combining them in
    one method conflates the public API with storage internals
