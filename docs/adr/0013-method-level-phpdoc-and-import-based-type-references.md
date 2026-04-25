# 0013. Method-Level PHPDoc And Import-Based Type References

## Status

Accepted

## Context

This package is intentionally structured around explicit contracts, domain-specific exceptions, DTOs, value objects,
and replaceable runtime collaborators.

That architecture only helps contributors and consumers if method-level behavior is understandable at the point of use.
Type declarations alone are not sufficient for that in this project because they do not explain:

- what a public method is responsible for in package terms
- what each parameter means in the current runtime flow
- which exceptions are part of the method contract
- what array and collection contents actually contain
- how a caller should interpret framework or library boundary methods

Without a consistent documentation style, several forms of drift appear:

- public APIs become readable only by tracing implementations manually
- array and collection shapes remain implicit and error-prone
- exception contracts are known only from implementation details
- docblocks alternate between helpful package-oriented descriptions and generic noise
- comments start using fully qualified class names directly, which makes them harder to scan and duplicates import
  information already handled by the language

The project already favors explicitness in naming, contracts, exception hierarchies, and runtime boundaries.
Method-level documentation must follow that same direction.

## Decision

All public methods in package code must include a method-level PHPDoc block when they are part of the maintained code
surface.

The required documentation style is:

- explain what the method does in direct, DX-oriented language
- document each parameter with its semantic role, not only its technical type
- document relevant thrown exceptions when they are part of the callable contract
- document the return value in caller-oriented terms
- describe concrete contents for arrays and collections, for example `list<INode>` or `array<string, mixed>`

The goal is that a contributor can understand the practical contract of a public method from its signature and PHPDoc
without immediately opening the implementation.

### Type-reference rule for docblocks

When a docblock refers to a class, interface, exception, collection item type, or generic type parameter, it must
prefer imported short names over fully qualified class names whenever the language import system can express that
reference clearly.

Required direction:

- import the type with a `use` statement
- reference the short class name inside the PHPDoc

Examples:

- preferred:
  - `use Sabre\\DAV\\Exception\\Forbidden;`
  - `@throws Forbidden`
- preferred:
  - `use Illuminate\\Support\\Collection;`
  - `@return Collection<int, PathResourceDto>`
- forbidden:
  - `@throws \Sabre\DAV\Exception\Forbidden`
- forbidden:
  - `@return \Illuminate\Support\Collection<int, \N3XT0R\LaravelWebdavServer\DTO\Auth\PathResourceDto>`

Boundary note:

- scalar types, native arrays, and shapes such as `array<string, mixed>` obviously do not require imports
- if a type cannot be imported meaningfully, use the clearest available notation, but imported short names remain the
  default rule

### Scope

This ADR is immediately normative for:

- new public methods
- existing public methods that are materially touched during refactors or feature work

Private and protected methods may also be documented when that materially improves readability, but the hard
requirement in this ADR applies to public methods.

## Consequences

Advantages:

- package APIs become easier to understand directly in the IDE
- parameter meaning and exception behavior become visible without implementation-tracing
- array and collection contracts become explicit and reviewable
- docblocks stay shorter and more readable because they use imported short names instead of fully qualified class names
- documentation style aligns with the project's broader preference for explicit, intention-revealing code

Disadvantages:

- every public API change now carries an additional documentation obligation
- refactors that touch public methods may require extra import cleanup
- reviewers must enforce documentation quality instead of only checking for the existence of a docblock

Rejected alternatives:

- rely on native PHP types only
  - rejected because parameter meaning, exception behavior, and collection contents would remain underspecified
- document only externally published APIs
  - rejected because package maintainability also depends on internal public method surfaces being understandable
- allow fully qualified names freely in docblocks
  - rejected because they create unnecessary visual noise and duplicate namespace information already modeled through
    imports
