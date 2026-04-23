# 0008. SOLID Compliance And Established Design Patterns

## Status

Accepted

## Context

This package is built around explicit responsibilities, replaceable collaborators, and contract-driven extension
points. As the number of classes, extension surfaces, and runtime decisions grows, the cost of ad hoc design also
grows.

Without a shared design standard, several problems appear quickly:

- responsibilities become mixed across controllers, resolvers, builders, validators, and runtime adapters
- extension work starts to require modifications in existing classes instead of adding new implementations
- abstractions stop matching the concrete behavior they are supposed to hide
- interfaces become too broad and force consumers to depend on methods they do not need
- concrete framework or library details leak into parts of the code that should stay decoupled
- teams begin to solve the same recurring design problems with one-off custom structures instead of recognizable
  patterns

The project already uses object-oriented extension points and recurring collaboration shapes that naturally align with
well-known design principles and patterns. Examples already present in the codebase include factory-style construction,
strategy-like interchangeable components, adapter-style boundaries, and builder/configurator responsibilities.

Therefore the project needs one explicit architectural rule for class design and one explicit preference for known,
well-understood design patterns.

## Decision

All new code and all materially touched refactors must be designed to comply with SOLID principles.

The normative expectations are:

- **Single Responsibility Principle**
  - each class or module must have one clear reason to change
  - orchestration, validation, storage resolution, runtime execution, and authorization concerns must stay separated
- **Open/Closed Principle**
  - new behavior should preferably be introduced by adding implementations, collaborators, or composition points instead
    of modifying stable existing code
- **Liskov Substitution Principle**
  - implementations behind contracts must remain behaviorally substitutable for the abstractions they implement
- **Interface Segregation Principle**
  - interfaces must stay focused and client-specific rather than turning into wide "do everything" contracts
- **Dependency Inversion Principle**
  - high-level policy and orchestration code must depend on abstractions, not concrete infrastructure details

In addition, when a recurring design problem clearly matches a known design pattern, the implementation must prefer an
established pattern over an ad hoc custom structure.

This includes, but is not limited to:

- **Factory / Factory Method / Abstract Factory**
  - for object creation that would otherwise couple client code to concrete classes
- **Strategy**
  - for interchangeable runtime behavior such as resolution, validation, authorization, or backend-specific logic
- **Builder**
  - for stepwise construction of complex objects or trees
- **Adapter**
  - for isolating framework or library boundaries behind package-level contracts
- **Facade**
  - for presenting a smaller, intention-revealing interface to a more complex subsystem
- **Decorator**
  - for extending behavior compositionally without modifying the wrapped implementation

The preference is for recognized, widely understood patterns with established names and expectations.

Custom one-off "patterns" are not preferred when a standard pattern already fits the same problem.

At the same time, patterns must not be introduced ceremonially:

- a pattern is required when it genuinely clarifies a recurring design problem
- a simpler direct implementation is still valid when no real pattern-level problem exists
- the project must not introduce unnecessary abstraction layers just to claim pattern usage

## Consequences

Advantages:

- design discussions gain a shared vocabulary
- responsibilities stay clearer and more stable over time
- extension points become easier to add without destabilizing existing code
- framework and vendor details remain better contained at the edges
- new contributors can recognize intent from familiar principles and pattern shapes
- refactoring decisions become easier to justify and review

Disadvantages:

- design work becomes more opinionated and review standards become stricter
- some solutions may require more classes or interfaces than a minimal ad hoc implementation
- incorrect or over-eager pattern application can increase complexity if the team is not disciplined
- legacy code that does not fully align introduces ongoing cleanup work

Rejected alternatives:

- allow each feature to choose its own design style
  - rejected because it leads to inconsistency, weaker maintainability, and avoidable architectural drift
- require only SOLID but stay neutral on patterns
  - rejected because recurring problems benefit from a shared, conventional set of solutions and terminology
- require a design pattern for every non-trivial class
  - rejected because it encourages ceremony and over-engineering instead of clarity
