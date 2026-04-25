# 0010. Explicit Type Checks Over Reflective Runtime Predicates

## Status

Accepted

## Context

This package already follows a contract-driven, object-oriented architecture with explicit responsibilities and
replaceable collaborators.

In such a codebase, reflective runtime predicates like `is_object()` and `method_exists()` work against the intended
design style:

- they inspect structure dynamically instead of depending on explicit types and contracts
- they weaken the guarantees that typed properties, constructor injection, interfaces, and `readonly` DTOs are meant to
  provide
- they make the real collaboration model harder to understand during review because behavior depends on ad hoc runtime
  probing
- they encourage defensive procedural branching where the design should instead model capabilities explicitly

The package already has an accepted architectural direction for SOLID compliance and established design patterns.
Allowing broad reflective checks in package code would undermine that direction by making it easier to bypass clear
abstractions instead of expressing them in the type system.

Therefore the project needs one explicit rule for runtime type and capability checks in application code.

## Decision

In package code, reflective runtime predicates such as `is_object()` and `method_exists()` are forbidden as a code-style
and design rule.

Instead, code must prefer explicit object-oriented typing:

- use `instanceof` when the code needs to branch on a known concrete type, interface, or base abstraction
- use constructor typing, parameter typing, return typing, and typed properties so invalid states are excluded earlier
- introduce or reuse an explicit interface when the code depends on a capability
- move variant behavior behind polymorphism, strategy selection, or dedicated collaborators instead of probing for
  methods dynamically

Examples of the required direction:

- forbidden: `is_object($node)`
  - preferred: `$node instanceof StorageFile`
- forbidden: `method_exists($validator, 'validate')`
  - preferred: `$validator instanceof CredentialValidatorInterface`
- forbidden: branching on whether an object happens to expose a method
  - preferred: define a dedicated contract for that capability and depend on it directly

This rule is immediately normative for all new code.

Existing code that still uses these predicates must be migrated when materially touched or during dedicated cleanup
work.

## Consequences

Advantages:

- the code becomes more explicit about which abstractions it actually depends on
- static analysis and IDE support become more reliable
- responsibilities stay aligned with SOLID principles, especially interface segregation and dependency inversion
- runtime behavior becomes easier to reason about because capabilities are modeled intentionally
- review discussions can focus on contract design instead of ad hoc defensive checks

Disadvantages:

- some call sites may require introduction of small dedicated interfaces or adapters
- legacy areas that rely on dynamic checks will require incremental cleanup
- strictness reduces flexibility for quick one-off dynamic solutions

Rejected alternatives:

- allow `is_object()` and `method_exists()` when they feel convenient
  - rejected because convenience here comes from bypassing the type system and weakening architectural clarity
- allow reflective predicates only in non-critical code paths
  - rejected because the same design drift appears regardless of execution criticality
- prefer documentation over a hard rule
  - rejected because this needs to be reviewable and enforceable as a concrete team standard
