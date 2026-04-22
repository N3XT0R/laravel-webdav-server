# 0005. WebDAV Space Key And Storage Space Mapping

## Status

Accepted

## Context

This package exposes WebDAV endpoints through the route shape `/webdav/{space}/{path?}`.

The package must support more than one storage target, but the public URL should not be forced to mirror Laravel disk
names or raw filesystem paths directly.

There are several competing concerns:

- the request URL needs a stable routing concept that identifies the intended storage target
- consuming applications may want multiple WebDAV entry points with different disks or roots
- storage configuration must stay internal and replaceable instead of becoming part of the public protocol contract
- per-user scoping must still be applied consistently after the target storage area is selected

If the package uses disk names or concrete root paths directly in the URL contract, several problems appear:

- internal Laravel storage details leak into the public WebDAV surface
- renaming disks or changing root paths becomes a breaking WebDAV URL change
- one route parameter would carry too much meaning by mixing routing, storage config, and user scoping
- the package loses a clear boundary between request routing and storage resolution

The current runtime already separates these concerns:

- `RequestSpaceKeyResolver` resolves the `{space}` route parameter and falls back to `webdav-server.storage.default_space`
- `DefaultSpaceResolver` maps that resolved key to `webdav-server.storage.spaces.{spaceKey}`
- each configured space provides a `disk`, a `root`, and an optional `prefix`
- the final storage root path is built as `{root}[/prefix]/{principal.id}`

## Decision

The package keeps `space` as an explicit, named routing concept and treats it as a storage selection key rather than a
direct disk name or path.

The architectural rules are:

- the public route stays `/webdav/{space}/{path?}`
- `{space}` resolves to a logical `spaceKey`
- `spaceKey` is looked up in `webdav-server.storage.spaces`
- each configured space defines the storage target through `disk`, `root`, and optional `prefix`
- per-user isolation is applied after the space is resolved by appending `{principal.id}` to the configured path

The default implementation therefore maps one public route segment to one configured storage space:

- request URL: `/webdav/default/...`
- resolved key: `default`
- config lookup: `webdav-server.storage.spaces.default`
- resulting storage root: `{root}[/prefix]/{principal.id}` on the configured disk

This keeps routing terminology stable while leaving the underlying storage model configurable.

## Consequences

Positive consequences:

- the public WebDAV contract stays stable even if internal disk names or root paths change
- multiple WebDAV spaces can be configured without exposing raw filesystem configuration details
- request routing and storage resolution remain separate responsibilities
- the package can apply per-user scoping consistently after the storage space is selected
- consuming applications can override `SpaceResolverInterface` while keeping the same route-level concept

Trade-offs:

- the package introduces one additional concept (`spaceKey`) that users must understand
- route configuration and storage configuration must stay aligned
- a missing or invalid space configuration fails at runtime instead of being derivable implicitly from the URL

Rejected alternatives:

- use the route parameter directly as the Laravel disk name
  - rejected because it leaks internal storage naming into the external WebDAV contract and weakens config flexibility
- encode the full storage root in the URL
  - rejected because it exposes internal structure and couples public URLs to implementation details
- drop the space segment and support only one global storage target
  - rejected because it removes an important extension point for multi-space routing and future package growth
