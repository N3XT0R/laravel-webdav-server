# AGENTS.md – Laravel WebDAV Server

## Project Overview

Laravel package (`n3xt0r/laravel-webdav-server`) that wraps **SabreDAV** to expose Laravel Flysystem storage disks as a
WebDAV endpoint. Requires PHP 8.4+ and Laravel 12+.

---

## Architecture & Data Flow

Every WebDAV request passes through this pipeline:

```
HTTP Request (Basic Auth)
  → WebDavController
  → WebDavServerFactory::make()
      → CredentialValidatorInterface::validate()  → WebDavPrincipal
      → SpaceResolverInterface::resolve()         → WebDavStorageSpace (disk + rootPath)
      → StorageRootCollection (SabreDAV tree root)
          → StorageDirectory / StorageFile nodes
              → PathAuthorizationInterface (before every FS operation)
              → Laravel Filesystem (Flysystem disk)
```

Key files: `src/Server/Factory/WebDavServerFactory.php`, `src/Nodes/StorageRootCollection.php`,
`src/Http/Controllers/WebDavController.php`.

---

## Extension Points (Contracts)

All implementations are bound with `bindIf()` – override any of these in your app's `ServiceProvider`:

| Contract                           | Default Implementation            | Override to…                     |
|------------------------------------|-----------------------------------|----------------------------------|
| `CredentialValidatorInterface`     | `DatabaseCredentialValidator`     | Custom auth (LDAP, API token, …) |
| `WebDavAccountRepositoryInterface` | `EloquentWebDavAccountRepository` | Non-Eloquent user stores         |
| `SpaceResolverInterface`           | `DefaultSpaceResolver`            | Per-user disk/path routing       |
| `PathAuthorizationInterface`       | `GatePathAuthorization`           | Custom authorization logic       |

`DefaultSpaceResolver` maps `principal.id` → `webdav.storage.prefix/{id}` on `webdav-server.storage.disk`.

---

## Authorization (Policies)

`GatePathAuthorization` calls `Gate::forUser($principal->user)->inspect($ability, $resource)`.  
Policy resource class: `WebDavPathResourceDto` (`disk`, `path` properties).  
Policy abilities: `read`, `write`, `delete`, `createDirectory`, `createFile`.

Register via: `Gate::policy(WebDavPathResourceDto::class, YourPolicy::class);`

The service provider auto-registers `App\Policies\WebDavPathPolicy` – ensure that class exists in the consuming app.

---

## Configuration

Config file: `config/webdav-server.php` (publish with `--tag="laravel-webdav-server-config"`).  
Accessed in code under the `webdav.*` key (e.g. `webdav.storage.disk`, `webdav.base_uri`).

`webdav-server.auth.model` **must** be set to a concrete Eloquent model class for the default
`EloquentWebDavAccountRepository` to work.

---

## Developer Workflows

```bash
# Run tests with PHPUnit in the PHP Docker container
docker compose exec -T php vendor/bin/phpunit --no-coverage

# Run a targeted test file with PHPUnit in the PHP Docker container
docker compose exec -T php vendor/bin/phpunit --no-coverage tests/Feature/Http/WebDavControllerTest.php

# Lint / format code (Laravel Pint)
composer run lint       # auto-fix
composer run test:lint  # dry-run check

# Serve workbench app (Orchestra Testbench)
composer run serve      # http://0.0.0.0:8000

# Build workbench assets
composer run build
```

Tests live in `tests/`. Extend `N3XT0R\LaravelWebdavServer\Tests\TestCase` (Orchestra Testbench + `WithWorkbench`). The
workbench Laravel app is in `workbench/`.

Use PHPUnit only for test execution in this project. Do not introduce Pest tests or Pest-style test files.
Prefer running tests inside the `php` Docker service via `docker compose exec -T php ...` so the execution environment
matches the project setup.

---

## Code Conventions

- `declare(strict_types=1)` in every PHP file.
- Concrete implementations use `final readonly class` with constructor property promotion.
- Contracts are PHP interfaces under `src/Contracts/` – never add logic there.
- DTOs and value objects (`WebDavPrincipal`, `WebDavStorageSpace`, `WebDavPathResourceDto`) are all `readonly`.
- SabreDAV exceptions (`Sabre\DAV\Exception\Forbidden`, `NotFound`) are thrown directly from nodes – do not wrap them in
  Laravel HTTP exceptions.

---

## Key Directories

```
src/Contracts/        – All extension-point interfaces
src/Nodes/            – SabreDAV node implementations (StorageRootCollection, StorageDirectory, StorageFile)
src/Storage/Resolvers – DefaultSpaceResolver
src/Auth/             – Validators, Backends, Authorization
src/DTO/Auth/         – WebDavPathResourceDto, WebDavAccountRecordDto
workbench/            – Full Laravel app used for local development
config/webdav-server.php – Package configuration stub
```

## Agent Execution Rules

For routine implementation tasks, use a lightweight workflow by default.

### Default behavior

- Do not use full TDD workflows unless explicitly requested.
- Do not run the full test suite unless explicitly requested.
- Do not repeatedly execute local tests after each small change.
- Prefer minimal, targeted changes over broad refactoring.
- Prefer reading only the files directly relevant to the requested change.
- Keep reasoning and output concise unless deeper analysis is explicitly requested.

### Validation

- CI is the primary validation mechanism for this project.
- Local validation should be limited to the smallest relevant scope.
- If tests are needed, run only the most relevant targeted test file or test case.
- Prefer `docker compose exec -T php vendor/bin/phpunit --no-coverage <target>` for local validation.
- Run the full suite with `docker compose exec -T php vendor/bin/phpunit --no-coverage` only when explicitly requested
  or when a broader refactor justifies it.

### Escalation rules

Use a heavier workflow only when one of the following is true:

- the user explicitly asks for TDD
- the change is security-critical
- the change affects authentication or authorization
- the change impacts public package APIs
- the change requires broad refactoring across multiple components

### For small tasks

For small or well-scoped implementation tasks:

- implement directly
- avoid full-project analysis
- avoid broad architectural rewrites
- avoid unnecessary verification loops
