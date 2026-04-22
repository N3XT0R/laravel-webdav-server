# 0004. Changelog Maintenance And Unreleased Entry Policy

## Status

Accepted

## Context

This project already maintains a human-readable `CHANGELOG.md` and states that its format is based on Keep a Changelog.

Keep a Changelog defines a changelog as a curated list of notable changes and recommends an `Unreleased` section that is
continuously maintained until the next version is released.

Without an explicit project rule, changelog maintenance tends to drift:

- code changes are merged without corresponding release notes
- noteworthy architectural and testing changes are only visible in commits or pull requests
- releases require reconstructing change history after the fact
- the `Unreleased` section stops being a reliable source of truth for upcoming releases

This project already uses a concrete `Unreleased` structure in `CHANGELOG.md`:

- section categories such as `Added`, `Changed`, and `Fixed`
- grouped topical bullets like `**testing**`, `**documentation**`, or `**adr**`
- concise, human-oriented summaries of notable package changes

That current structure should be treated as the house style for ongoing changelog maintenance.

## Decision

All notable project changes must be recorded in `CHANGELOG.md` under `## [Unreleased]` as part of the same work in
which the change is introduced.

The changelog policy is:

- follow Keep a Changelog as the governing format reference
- maintain the `Unreleased` section continuously instead of reconstructing it later
- record notable code, architecture, testing, documentation, configuration, and developer-workflow changes
- write entries for humans, not as raw commit-log fragments
- keep using the project's established `Unreleased` style unless a dedicated follow-up decision changes it

### Required format rules

- entries belong under the existing Keep a Changelog categories such as `Added`, `Changed`, `Fixed`, `Removed`, and
  other standard sections when appropriate
- within a category, changes should be grouped under a short topical label in the current house style, for example
  `- **testing**` or `- **documentation**`
- each grouped entry should summarize the externally or operationally relevant change in concise prose
- entries should describe the actual outcome, not the implementation process

### Operational rule

Any change that is significant enough to be merged is presumed significant enough to be evaluated for changelog impact.

If a change is notable, the author must update `CHANGELOG.md` in the same branch or change set.

Examples of normally notable changes in this project:

- new extension points, contracts, or runtime boundaries
- behavior changes in the WebDAV request pipeline
- authentication or authorization changes
- test architecture or tooling changes
- public configuration changes
- class renames that affect public package usage or documentation
- new ADRs or documentation that materially changes project guidance

Purely internal edits with no meaningful user, maintainer, or release impact may be omitted, but that should be the
exception, not the default.

## Consequences

Positive consequences:

- `CHANGELOG.md` remains the primary source of truth for upcoming releases
- releases are easier to prepare because notable changes are curated continuously
- maintainers and users can understand package evolution without reading commit history
- architectural, testing, and developer-experience changes remain visible alongside feature work

Trade-offs:

- every meaningful change now carries a documentation obligation
- authors must decide whether a change is notable before merging
- duplicate thinking is required when the code change is already described elsewhere, for example in an ADR or pull
  request

Rejected alternative:

- update the changelog only at release time
  - rejected because it leads to omissions, weakens release quality, and turns changelog writing into archaeology
