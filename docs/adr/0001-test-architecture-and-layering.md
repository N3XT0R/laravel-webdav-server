# 0001. Test Architecture And Layering

## Status

Accepted

## Context

The project had drifted into a test structure in which several classes under `tests/Feature/` and `tests/Integration/`
were not actually testing those layers.

Examples of the drift were:

- feature tests that instantiated classes directly instead of exercising a real HTTP request flow
- integration tests that primarily asserted collaborator interaction through PHPUnit mocks and stubs
- unit-style orchestration tests living in `tests/Integration/`
- inconsistent use of the Laravel package `TestCase` for tests that only needed plain PHPUnit

This package has a clear runtime architecture:

- `Feature` concerns are real Laravel HTTP flows through the package route and controller
- `Integration` concerns are real collaboration with framework/runtime boundaries such as Gate, filesystem disks,
  Eloquent repositories, and SabreDAV node behavior
- `Unit` concerns are isolated class behaviors that can be exercised without mocks and without crossing runtime
  boundaries

The package also has an explicit design goal of contract-driven replaceability. Overusing PHPUnit mocks in the test
suite weakens that goal because tests become tied to implementation choreography rather than real observable behavior.

## Decision

The test suite is organized by behavior layer, not by arbitrary folder history.

### Feature tests

Tests in `tests/Feature/` must execute a real Laravel HTTP request against the package endpoint.

Rules:

- use real request dispatch through Laravel test helpers such as `$this->call()`, `$this->get()`, or JSON helpers when
  applicable
- use real package services for the request pipeline
- do not use PHPUnit mocks or stubs
- allow narrowly scoped concrete test implementations only where the production runtime cannot be exercised inside the
  PHPUnit process itself

In this package, `SabreServerRunner` terminates the process via `exit`, so feature tests may replace only that final
runtime edge with a concrete test runner while keeping the rest of the pipeline real.

### Integration tests

Tests in `tests/Integration/` must exercise real collaboration with external or framework boundaries.

Examples in this package:

- Gate authorization through `GatePathAuthorization`
- real filesystem interaction through `StorageRootCollection`, `StorageDirectory`, and `StorageFile`
- real Eloquent persistence through `EloquentWebDavAccountRepository`
- real configuration-driven resolution such as `DefaultSpaceResolver`

Rules:

- prefer real framework services, real filesystem disks, and real persisted records
- do not use PHPUnit mocks or stubs
- if a collaborator needs test-specific behavior, provide a small concrete in-memory or recording implementation in
  `tests/Fixtures/`

### Unit tests

Tests in `tests/Unit/` cover isolated class behavior.

Rules:

- prefer plain `PHPUnit\Framework\TestCase` whenever Laravel infrastructure is not needed
- only use the package `tests/TestCase.php` when container/config/filesystem integration is genuinely required
- do not use PHPUnit mocks or stubs
- use concrete in-memory or recording fixtures instead

Typical unit-test targets in this package are:

- DTOs and value objects
- request/context orchestration classes
- validators and authenticators when exercised with concrete in-memory collaborators
- configuration-free adapter logic

### Test fixtures

Reusable concrete test helpers live in `tests/Fixtures/`.

These fixtures may be:

- in-memory repositories
- recording authenticators, configurators, or resolvers
- permissive or denying authorization implementations
- concrete server runners used only to avoid process termination in tests

They exist to keep tests behavior-oriented and mock-free.

### Tooling

The project uses PHPUnit for test execution.

Rules:

- write PHPUnit tests only
- do not introduce Pest tests
- local and CI execution should invoke `vendor/bin/phpunit` or the existing Composer script wrappers that resolve to
  PHPUnit-compatible execution for this repository

## Consequences

Positive consequences:

- test names, folders, and execution style now match the actual architectural layer being verified
- feature tests provide confidence in the real HTTP entrypoint and request pipeline
- integration tests verify real framework and filesystem behavior instead of mocked interaction choreography
- unit tests stay smaller, faster, and easier to understand
- the suite better reflects the package's contract-driven architecture and extension points

Trade-offs:

- some tests need small concrete helper implementations in `tests/Fixtures/`
- integration tests that use real filesystem or database services require more setup than mocked tests
- strict layer discipline requires moving tests when implementation boundaries become clearer

Operational consequence:

- new tests should be reviewed not only for correctness, but also for whether they are placed in the correct layer and
  whether they use real code paths instead of mocks
