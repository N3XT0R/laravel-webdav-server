# 0003. Class Naming Convention By Suffix

## Status

Accepted

## Context

In a growing software system, the number of classes, responsibilities, and dependencies continuously increases. Without
clear naming conventions, inconsistency appears quickly, which makes readability, maintainability, and onboarding more
difficult.

This package is explicitly structured around interchangeable responsibilities such as controllers, factories,
repositories, DTOs, contracts, resolvers, validators, and runtime adapters. In such an architecture, it must be
possible to recognize the role of a class directly from its name.

At the same time, the current codebase already contains legacy names that do not fully follow one unified suffix-based
scheme. Therefore, the naming decision must serve both as a forward rule and as a migration target for existing code.

## Decision

All classes must follow a clearly defined naming scheme in which the responsibility is indicated by a suffix.

### Required suffixes

| Type                  | Suffix         | Description                    |
|-----------------------|----------------|--------------------------------|
| Services              | `*Service`     | Contains business logic        |
| Repositories          | `*Repository`  | Encapsulates data access       |
| Factory classes       | `*Factory`     | Creates objects                |
| Interfaces            | `*Interface`   | Defines contracts              |
| Value objects         | `*ValueObject` | Immutable data objects         |
| Data transfer objects | `*Dto`         | Data transport structure       |
| Exceptions            | `*Exception`   | Error cases                    |
| Controllers           | `*Controller`  | Entry logic, for example HTTP  |
| Event listeners       | `*Listener`    | Reacts to events               |
| Event dispatchers     | `*Dispatcher`  | Emits events                   |

### Examples

- `UserService`
- `OrderRepository`
- `PaymentFactory`
- `LoggerInterface`
- `EmailDto`
- `AuthenticationException`

## Rules

1. No deviation from the naming scheme

- classes without a clear suffix are not allowed for the categories covered by this ADR

2. Exactly one responsibility per class

- the name must reflect the actual role
- mixed forms such as `UserServiceRepository` are not allowed

3. Interface requirement for abstracted components

- every abstracted service or repository must have a corresponding interface
- examples:
  - `UserServiceInterface`
  - `UserService`

4. No generic names

- forbidden: `Helper`, `Manager`, `Util`
- use a clear domain-specific name instead

5. Suffix is mandatory, prefix is optional

- the domain is expressed through the prefix
- examples:
  - `UserService`
  - `InvoiceRepository`

6. Existing deviations are migrated incrementally

- this ADR is immediately normative for new code
- existing classes that do not yet follow the convention must be aligned during touching refactors or dedicated cleanup
  work

## Consequences

Advantages:

- responsibilities are visible immediately
- code readability improves
- the project structure becomes more uniform
- refactoring becomes easier
- IDEs and tooling can reason more effectively about class roles

Disadvantages:

- class names can become slightly longer
- special cases have less naming flexibility
- the team must apply the convention consistently
- existing non-conforming names introduce migration work

Rejected alternatives:

- no fixed convention
  - rejected because it leads to inconsistency and higher maintenance cost
- annotations instead of naming conventions
  - rejected because the role of a class is less visible in the code itself

## Notes

This ADR defines the target naming policy for the project. Because the current codebase still contains legacy names,
acceptance of this decision does not mean that every existing class already complies. It means that the convention is
now binding for new code and the reference point for future renames.
