# 0003. Class Naming Convention By Suffix

## Status

Accepted

## Context

In a growing software system, the number of classes, responsibilities, and dependencies continuously increases. Without
clear naming conventions, inconsistency appears quickly, which makes readability, maintainability, and onboarding more
difficult.

This package is explicitly structured around interchangeable responsibilities such as controllers, factories,
repositories, DTOs, contracts, resolvers, validators, runtime adapters, Laravel service providers, commands, and
SabreDAV node types. In such an architecture, it must be possible to recognize the role of a class directly from its
name.

At the same time, class names must not repeat context that is already expressed by the namespace or directory
structure. Otherwise names become longer without adding information, and the code starts encoding the same concept in
two places.

At the same time, the current codebase already contains legacy names that do not fully follow one unified suffix-based
scheme. Therefore, the naming decision must serve both as a forward rule and as a migration target for existing code.

## Decision

All classes must follow a clearly defined naming scheme in which the responsibility is indicated by a suffix.

The suffix must match the actual architectural or framework role of the class. The goal is not to force every class
into a small generic list, but to make responsibilities explicit and consistent.

The prefix, when present, must add domain meaning that is not already provided by the namespace.

### Allowed and required suffixes

| Type                  | Suffix            | Description                                |
|-----------------------|-------------------|--------------------------------------------|
| Services              | `*Service`        | Contains business logic                    |
| Repositories          | `*Repository`     | Encapsulates data access                   |
| Factory classes       | `*Factory`        | Creates objects                            |
| Interfaces            | `*Interface`      | Defines contracts                          |
| Value objects         | `*ValueObject`    | Immutable data objects                     |
| Data transfer objects | `*Dto`            | Data transport structure                   |
| Exceptions            | `*Exception`      | Error cases                                |
| Controllers           | `*Controller`     | Entry logic, for example HTTP              |
| Policies              | `*Policy`         | Authorization rules                        |
| Service providers     | `*ServiceProvider`| Laravel service-provider integration       |
| Validators            | `*Validator`      | Validation of credentials or input         |
| Resolvers             | `*Resolver`       | Runtime resolution logic                   |
| Authorizers           | `*Authorization`  | Authorization adapters / guards            |
| Authenticators        | `*Authenticator`  | Authentication orchestration               |
| Backends              | `*Backend`        | Library-facing auth/runtime backend        |
| Builders              | `*Builder`        | Incremental object/tree construction       |
| Configurators         | `*Configurator`   | Runtime configuration of collaborators     |
| Runners               | `*Runner`         | Executes a prepared runtime                |
| Extractors            | `*Extractor`      | Extracts structured values from input      |
| Registers             | `*Register`       | Registration of bindings or package parts  |
| Commands              | `*Command`        | Console command entrypoints                |
| Models                | `*Model`          | ORM / persistence-backed domain records    |
| Collections           | `*Collection`     | Collection-style aggregate or root nodes   |
| Node files            | `*File`           | File-like protocol node                    |
| Node directories      | `*Directory`      | Directory-like protocol node               |
| Event listeners       | `*Listener`       | Reacts to events                           |
| Event dispatchers     | `*Dispatcher`     | Emits events                               |

### Examples

- `UserService`
- `OrderRepository`
- `PaymentFactory`
- `LoggerInterface`
- `PathPolicy`
- `ServerServiceProvider`
- `DatabaseCredentialValidator`
- `DefaultSpaceResolver`
- `SabreServerRunner`
- `EmailDto`
- `AuthenticationException`

## Rules

1. No deviation from the naming scheme

- classes without a clear role suffix are not allowed
- the suffix must be chosen from the approved role list in this ADR

2. Exactly one responsibility per class

- the name must reflect the actual role
- mixed forms such as `UserServiceRepository` are not allowed
- framework-specific roles must keep the suffix that matches the framework role
  - examples:
    - Laravel provider: `*ServiceProvider`
    - Laravel command: `*Command`
    - SabreDAV collection/root collection: `*Collection`

3. Interface requirement for abstracted components

- every abstracted service, repository, builder, resolver, runner, configurator, extractor, validator, authenticator,
  or authorization adapter should have a corresponding interface when that role is part of the package extension surface
- examples:
  - `UserServiceInterface`
  - `UserService`
  - `SpaceResolverInterface`
  - `DefaultSpaceResolver`

4. No generic names

- forbidden: `Helper`, `Manager`, `Util`
- use a clear domain-specific name instead

5. Suffix is mandatory, prefix is optional

- the domain is expressed through the prefix
- examples:
  - `UserService`
  - `InvoiceRepository`

6. Namespace context must not be duplicated in the class name

- a class name must not repeat domain or subsystem terms that are already clearly expressed by its namespace or
  directory
- move classes into the correct sub-namespace instead of encoding that context again in the class name
- prefer the shortest name that is still unambiguous inside its namespace
- examples of forbidden redundancy:
  - `DTO\Auth\WebDavAccountRecordDto`
  - `DTO\Auth\WebDavPathResourceDto`
  - `DTO\Server\WebDavRequestContextDto`
  - `N3XT0R\LaravelWebdavServer\WebdavServerServiceProvider`
- preferred direction:
  - `DTO\Auth\AccountRecordDto`
  - `DTO\Auth\PathResourceDto`
  - `DTO\Server\RequestContextDto`
  - `Server\ServiceProvider` or another correctly scoped sub-namespace with `ServiceProvider`
- the namespace exists to carry context; the class name should express the remaining specific role, not restate the
  full path

7. Names must not compensate for missing structure

- if a class name needs a long package, protocol, or subsystem prefix to be understandable, that usually indicates that
  the class belongs in a more specific namespace
- prefer moving the class to a better namespace over inventing longer names such as `WebDav*`, `LaravelWebdav*`, or
  `Server*` when that context is already architectural rather than semantic

8. Existing deviations are migrated incrementally

- this ADR is immediately normative for new code
- existing classes that do not yet follow the convention must be aligned during touching refactors or dedicated cleanup
  work

9. Framework and library semantics take precedence over artificial renaming

- a class must not be renamed into a semantically wrong suffix just to satisfy a reduced naming list
- examples of correct framework-aligned names:
  - `ServiceProvider`, not `ServerService`
  - `Command`, not `ServerService`
  - `SabreServerRunner`, not `SabreServerService`

## Consequences

Advantages:

- responsibilities are visible immediately
- code readability improves
- the project structure becomes more uniform
- refactoring becomes easier
- IDEs and tooling can reason more effectively about class roles
- class names become shorter and less repetitive

Disadvantages:

- some classes may need to move into more specific namespaces before a good short name becomes available
- special cases have less naming flexibility
- the team must apply the convention consistently
- existing non-conforming names introduce migration work
- the approved suffix list must evolve carefully when the architecture introduces a genuinely new role

Rejected alternatives:

- no fixed convention
  - rejected because it leads to inconsistency and higher maintenance cost
- annotations instead of naming conventions
  - rejected because the role of a class is less visible in the code itself
- allow redundant prefixes for package or subsystem context
  - rejected because namespaces already provide that context and repeating it in class names creates avoidable noise

## Notes

This ADR defines the target naming policy for the project. Because the current codebase still contains legacy names,
acceptance of this decision does not mean that every existing class already complies. It means that the convention is
now binding for new code and the reference point for future renames.

This ADR was intentionally broadened after initial adoption because the narrower suffix list did not correctly describe
several real roles already present in the project architecture.
