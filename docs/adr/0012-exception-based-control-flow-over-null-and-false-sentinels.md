# 0012. Exception-Based Control Flow Over Null And False Sentinels

## Status

Accepted

## Context

Returning `null` or `false` as sentinel values for domain-relevant failure states makes control flow implicit.

That style causes recurring problems in a contract-driven package architecture:

- callers must remember and manually check hidden failure states after every call
- failure semantics become ambiguous because `null` or `false` do not communicate a precise reason or scope
- missed checks silently propagate invalid state deeper into the runtime
- branching logic becomes defensive and scattered instead of explicit and centralized
- static analysis can see that a nullable or boolean return is possible, but it cannot infer the real failure context

This package already prefers explicit contracts, domain-specific exception hierarchies, and clear architectural
boundaries. Allowing sentinel returns for package failure handling would undermine that direction and produce weaker,
less classifiable control flow.

Therefore the project needs one explicit rule for signaling failure states in package code.

## Decision

In package code, `null` and `false` must not be used as return values to signal domain-relevant failure or absence when
the caller is expected to branch on that condition.

Instead, such cases must be represented by a domain-specific exception that is thrown at the point of failure and
caught only at the boundary or orchestration level that can handle that context meaningfully.

Required direction:

- do not return `null` to mean "not found", "missing", "invalid", "unauthorized", or "unresolvable"
- do not return `false` to mean "operation failed", "authentication failed", "lookup failed", or "not available"
- throw a package exception from the correct domain hierarchy instead
- catch that exception only where the runtime can convert it into the appropriate next action, protocol response, or
  user-facing outcome

Examples:

- forbidden: `return null;` to signal invalid credentials
  - preferred: throw `InvalidCredentialsException`
- forbidden: `return false;` to signal lookup failure
  - preferred: throw a lookup- or storage-scoped domain exception
- forbidden: nullable return types whose `null` branch actually means domain failure
  - preferred: non-null return type plus exception-based failure signaling

This decision applies to package-internal contracts as well:

- interfaces must not model domain failures as `?Type` or `Type|false` return signatures
- domain-relevant absence must be turned into a typed exception, not a sentinel return

Boundary exception:

- if an external library, protocol, or framework interface requires `null` or `false` as part of its contract, that
  sentinel handling must be isolated to the boundary adapter
- the package's internal domain logic must still use explicit exceptions
- conversion from internal exception to external sentinel is allowed only at the narrow adapter edge where the foreign
  contract requires it

This rule is immediately normative for all new code.

Existing package code that currently uses `null` or `false` as domain-failure sentinels must be migrated when
materially touched or during dedicated cleanup work.

## Consequences

Advantages:

- control flow becomes explicit and reviewable
- failure causes are classified by type instead of hidden in sentinel values
- callers become simpler because success paths are non-null and non-boolean by default
- exception handling aligns with the package's domain-specific exception hierarchy
- invalid states are less likely to leak through multiple layers unnoticed

Disadvantages:

- more exception classes and exception mapping points may be required
- some existing contracts will need signature changes during migration
- careless over-catching could reintroduce unclear control flow if exceptions are swallowed too early

Rejected alternatives:

- allow `null` and `false` returns if they are documented well
  - rejected because documentation does not make the control flow explicit in the type hierarchy or call path
- allow sentinels in internal code but use exceptions only at the HTTP boundary
  - rejected because implicit failure propagation would still exist across the package internals
- replace only `false` returns but keep nullable domain results
  - rejected because both forms have the same architectural weakness when they represent failure states
