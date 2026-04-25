# 0014. Additional SabreDAV Plugins Via Tagged Service Provider Registration

## Status

Accepted

## Context

The package already exposes stable extension points for authentication, storage resolution, authorization, request
resolution, and runtime execution.

At the same time, SabreDAV itself is designed to be extended through `ServerPlugin` instances.

Consuming applications may need to:

- attach additional DAV lifecycle listeners
- add custom response headers or diagnostics
- integrate protocol-specific or vendor-specific SabreDAV behavior
- extend the runtime without replacing the package's default `SabreServerConfigurator`

If applications have to replace the full configurator just to register one extra SabreDAV plugin, several problems
appear:

- runtime customization becomes more invasive than necessary
- consumers must re-implement or duplicate package-default plugin registration
- package compatibility fixes in the default configurator become easier to accidentally bypass
- extension becomes harder even though SabreDAV itself already supports compositional plugins well

The package therefore needs one stable, additive way to let applications contribute extra SabreDAV plugins while
keeping the default configurator in control.

## Decision

Applications may register additional SabreDAV `ServerPlugin` instances through the Laravel container tag exposed by
`WebdavServerServiceProvider::sabrePluginTag()`.

The architectural rules are:

- `SabreServerConfigurator` remains the default package configurator
- package-default plugins are still always attached first
- tagged application plugins are attached afterward
- adding a custom SabreDAV plugin must not require replacing `ServerConfiguratorInterface`
- the extension surface for these plugins is the package-defined tag, not an ad hoc override convention

This keeps server customization additive and compositional:

- package defaults stay active
- applications can opt into extra SabreDAV behavior
- the runtime pipeline remains stable from the package's point of view

## Consequences

Advantages:

- applications can extend the SabreDAV runtime without forking or replacing the package configurator
- package-default compatibility and logging plugins remain active
- the package keeps one stable customization mechanism for SabreDAV plugin composition
- the approach aligns with SabreDAV's own plugin model and with the project's extension-oriented architecture

Trade-offs:

- consumers must understand Laravel container tagging to use this extension point
- plugin ordering matters, so package defaults and tagged plugins must remain predictable
- incorrect custom plugins can still affect runtime behavior, because SabreDAV plugins are powerful by design

Rejected alternatives:

- require consumers to replace `ServerConfiguratorInterface` for all SabreDAV plugin customization
  - rejected because it makes small runtime extensions more invasive than necessary and encourages duplication of
    package-default configurator logic
- add a new configuration array of plugin class strings in `config/webdav-server.php`
  - rejected because the package already has a strong service-provider-based extension model and container tagging gives
    better lifecycle control for constructed plugin instances
- disallow application-level SabreDAV plugins and support only package-defined defaults
  - rejected because it would unnecessarily limit legitimate runtime customization needs in consuming applications
