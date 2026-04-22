# 0002. WebDAV Request Pipeline And Runtime Boundary

## Status

Accepted

## Context

This package exposes a WebDAV server endpoint inside a Laravel application, but the actual protocol runtime is
implemented by SabreDAV.

That creates two competing concerns:

- the package must keep the request flow explicit, replaceable, and understandable from Laravel's perspective
- the package must still hand off execution to the SabreDAV runtime, which takes over protocol handling and response
  generation

If request handling and SabreDAV runtime execution are collapsed into one class, several problems appear:

- controller responsibilities become blurred
- authentication, routing, space resolution, authorization, and node construction become harder to replace independently
- testing becomes brittle because `server->start()` terminates the request lifecycle
- consumers of the package lose clear extension points for auth, storage routing, and authorization

The package already has an explicit internal flow:

- `WebDavController` accepts the request and checks whether a Basic Auth attempt exists
- `WebDavServerFactory` builds the SabreDAV `Server`
- `DefaultRequestContextResolver` resolves credentials, principal, space key, and storage space
- `StorageRootBuilder` creates the WebDAV root tree for the resolved principal and storage space
- `SabreServerConfigurator` applies SabreDAV runtime configuration
- `ServerRunnerInterface` executes the prepared server

## Decision

The package keeps the WebDAV request pipeline explicit and separates request orchestration from runtime execution.

The architectural rules are:

- `WebDavController` is responsible only for request entry concerns
- server construction happens in `WebDavServerFactory`
- request context resolution happens through dedicated resolver/authenticator/extractor components
- storage tree construction happens through `StorageRootBuilder`
- SabreDAV runtime configuration happens through `ServerConfiguratorInterface`
- final runtime execution happens through `ServerRunnerInterface`

`ServerRunnerInterface` is the explicit runtime boundary.

The default implementation remains `SabreServerRunner`, which calls `server->start(); exit;`, but the package does not
hardcode that behavior into the controller or factory.

All major extension points in the request pipeline continue to be exposed through contracts and default `bindIf()`
bindings so that consuming applications can replace them without forking the package.

## Consequences

Positive consequences:

- the full request path is explicit and traceable from controller to filesystem access
- authentication, space resolution, authorization, and runtime execution stay independently replaceable
- the controller remains thin and focused
- tests can verify orchestration and request handling without embedding SabreDAV process termination everywhere
- the architecture aligns with the package goal of contract-driven extensibility

Trade-offs:

- the request flow spans more classes than a monolithic controller-based implementation
- understanding the full runtime path requires familiarity with several collaborators
- the runtime boundary introduces one additional abstraction layer

Rejected alternative:

- call `Server::start()` directly inside `WebDavController`
  - rejected because it couples request orchestration to SabreDAV runtime execution, weakens replaceability, and makes
    testing harder
