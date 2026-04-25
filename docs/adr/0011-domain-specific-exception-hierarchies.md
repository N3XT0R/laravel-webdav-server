# 0011. Domain-Specific Exception Hierarchies

## Status

Accepted

## Context

This package already defines its own package-level `DomainException` base type.

That is the correct direction, but a domain-specific base class alone is not sufficient if concrete exceptions remain
flat or if code still throws generic global exceptions such as `\DomainException` or `\InvalidArgumentException`
directly.

Global SPL exceptions are too unspecific for this package architecture:

- they do not communicate which bounded context or subsystem failed
- they make failure classification harder in reviews, logging, and exception handling
- they encourage technical rather than domain-oriented modeling of error conditions
- they flatten distinct package concerns such as authentication, authorization, storage resolution, or runtime setup
  into generic exception buckets

This package is intentionally structured around explicit domains, contracts, and responsibilities. Its exception model
must follow that same structure so that failures are classified in a way that matches the package architecture.

Therefore the project needs an explicit rule for hierarchical, domain-scoped exception design.

## Decision

All domain-specific exceptions in this package must inherit from the package's own `DomainException` base class.

In addition, domain-specific exceptions must be organized hierarchically by bounded context or subsystem instead of
forming one flat list directly beneath `DomainException`.

Required direction:

- `DomainException` is the package root for domain failures
- each domain or subsystem must define an intermediate exception base when it has multiple concrete failures
- concrete exceptions must inherit from the most specific domain base available

Example:

- `InvalidCredentialsException` must extend `AuthException`
- `AuthException` must extend `DomainException`

This rule applies equally to other contexts when they grow multiple exception types, for example authorization, storage,
resolution, or protocol preparation.

The project must not throw or expose raw global exceptions such as:

- `\DomainException`
- `\InvalidArgumentException`
- other generic SPL or framework exception types when the failure belongs to a package domain context

Instead, such failures must always be translated into a domain-scoped package exception that communicates the correct
architectural context.

Normative implications:

- do not throw `\InvalidArgumentException` for a package-auth failure; throw an auth-scoped exception
- do not throw `\DomainException` directly; throw a package exception beneath the appropriate domain branch
- when input validation, state validation, or invariant failure is domain-specific, model it in the corresponding domain
  exception hierarchy
- only use non-domain exception types when the exception truly belongs to an external protocol or infrastructure
  boundary and must remain that native type

This rule is immediately normative for all new code.

Existing flat or overly generic package exceptions must be migrated toward domain hierarchies when materially touched or
during dedicated cleanup work.

## Consequences

Advantages:

- failures become classifiable by package context instead of only by generic technical category
- exception handling and logging gain a more precise scope
- reviews can reason about failure semantics from the type hierarchy alone
- the exception model becomes consistent with the project's SOLID and domain-driven architectural direction
- broader catch points remain possible without losing domain meaning, for example catching `AuthException`

Disadvantages:

- more exception classes may be needed because each domain can require its own intermediate base type
- legacy flat exception trees introduce incremental migration work
- authors must think about failure classification earlier instead of defaulting to convenient global exceptions

Rejected alternatives:

- allow direct use of `\DomainException` and `\InvalidArgumentException`
  - rejected because they do not provide a meaningful package-specific scope
- keep only one flat package `DomainException` layer without intermediate branches
  - rejected because it weakens failure classification once multiple exceptions exist in the same subsystem
- rely on message text instead of type hierarchy for classification
  - rejected because messages are less stable, less enforceable, and weaker for typed handling
