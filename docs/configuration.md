# Configuration

All package configuration is loaded from `config/webdav-server.php` and accessed through the `webdav-server.*` key.

## Quick Start

```php
return [
    'base_uri' => '/webdav/',
    'logging' => [
        'driver' => null,
        'level' => 'info',
    ],
    'storage' => [
        'default_space' => 'default',
        'spaces' => [
            'default' => [
                'disk' => 'local',
                'root' => 'webdav',
            ],
        ],
    ],
    'auth' => [
        'account_model' => \App\Models\WebDavAccount::class,
        'user_model' => \App\Models\User::class,
    ],
];
```

## Top-Level Keys

| Key                              | Default    | Used by                                              |
|----------------------------------|------------|------------------------------------------------------|
| `webdav-server.route_prefix`     | `webdav`   | CSRF exclusion path in `WebdavServerServiceProvider` |
| `webdav-server.base_uri`         | `/webdav/` | `SabreServerConfigurator`                            |
| `webdav-server.browser_listing`  | `false`    | `SabreServerConfigurator` — enables SabreDAV browser UI |
| `webdav-server.logging.driver`   | `null`     | package logging and SabreDAV logger wiring           |
| `webdav-server.logging.level`    | `info`     | package log filtering for `info` and `debug` output  |

## Browser Listing

When `browser_listing` is set to `true`, the SabreDAV `Browser\Plugin` is attached to the runtime. This renders an
HTML directory listing when a WebDAV space is accessed from a browser.

```php
'browser_listing' => true,
```

The browser plugin is disabled by default because it exposes the directory tree to any authenticated HTTP client
without any additional access control beyond the package's existing path authorization.

When the browser listing is active, SabreDAV renders two HTML forms on every directory page:

- **Create folder** — submits a `POST` request that SabreDAV converts to a `MKCOL` operation internally.
- **Upload file** — submits a `POST` request that SabreDAV converts to a `PUT` operation internally.

Both forms work out of the box. The package route accepts `POST` for this purpose, and the WebDAV endpoint is
automatically excluded from Laravel's CSRF middleware so browser submissions are not rejected.

> **Note:** Enable this in development or internal environments only. Do not enable it in production unless path
> authorization is explicitly configured to restrict access.

## Logging

Package logging is configured under `webdav-server.logging`.

Relevant keys:

- `webdav-server.logging.driver`
- `webdav-server.logging.level`

Behavior:

- if `driver` is `null`, package logging is disabled entirely
- if `driver` contains a Laravel log channel name such as `stack`, `single`, or `stderr`, package logs are written to
  that channel
- `level` defines the minimum package log level that will be emitted
- the same logger is also attached to SabreDAV via `SabreServerConfigurator`

Typical usage:

- use `info` to record operational events such as authentication success or failure
- use `debug` during development to trace credential extraction, request-context resolution, storage resolution, and
  server setup

## Storage Spaces

`DefaultSpaceResolver` reads `webdav-server.storage.spaces` and resolves a logical space key to one effective storage
target.

Relevant keys:

- `webdav-server.storage.default_space` (default: `default`)
- `webdav-server.storage.spaces.{space}.disk` (required)
- `webdav-server.storage.spaces.{space}.root` (required)
- `webdav-server.storage.spaces.{space}.prefix` (optional)

`WebDavServerFactory` determines the `spaceKey` in this order:

1. route parameter `{space}`
2. fallback `webdav-server.storage.default_space`

The resolved runtime root is always:

```text
{root}/{principal.id}
```

If a space defines a non-empty `prefix`, the effective root becomes:

```text
{root}/{prefix}/{principal.id}
```

Example:

- `disk = local`
- `root = webdav`
- `prefix = uploads`
- `principal.id = 42`
- effective root path: `webdav/uploads/42`

## Auth Mapping

`EloquentAccountRepository` and your configured account model use:

- `webdav-server.auth.account_model` (must be an Eloquent model class)
- `webdav-server.auth.user_model` (required if policies should operate on `$principal->user`)
- `webdav-server.auth.username_column` (default `username`)
- `webdav-server.auth.password_column` (default `password_encrypted`)
- `webdav-server.auth.enabled_column` (default `enabled`, set empty string to skip enabled checks)
- `webdav-server.auth.user_id_column` (default `user_id`)
- `webdav-server.auth.display_name_column` (default `username`)

## Failure Modes

Invalid auth and storage configuration now raises domain-specific package exceptions instead of generic runtime
exceptions.

Examples:

- `InvalidAccountConfigurationException`
- `MissingUserModelConfigurationException`
- `SpaceNotConfiguredException`
- `InvalidSpaceConfigurationException`
- `InvalidDefaultSpaceConfigurationException`

## Current Notes

- The package registers the WebDAV route shape `/webdav/{space}/{path?}`.
- `route_prefix` is used for CSRF exclusion and falls back to `base_uri` when empty.
- `logging.driver = null` disables all package and SabreDAV logging.
- `storage.spaces.*` is the active storage configuration model.
- The packaged reference policy is `src/Policies/PathPolicy.php`.

## Additional SabreDAV Plugins

Applications can register additional SabreDAV `ServerPlugin` instances through the Laravel container without replacing
the package configurator.

Register the plugin in your application service provider and tag it with
`WebdavServerServiceProvider::sabrePluginTag()`:

```php
use App\WebDav\Plugins\CustomSabrePlugin;
use N3XT0R\LaravelWebdavServer\WebdavServerServiceProvider;

public function register(): void
{
    $this->app->singleton(CustomSabrePlugin::class);
    $this->app->tag(
        [CustomSabrePlugin::class],
        WebdavServerServiceProvider::sabrePluginTag(),
    );
}
```

Tagged plugins are attached in addition to the package defaults such as the compatibility and missing-path handling
plugins.

This extension mechanism is part of the package's stable server-customization model and is documented in
[ADR 0014](adr/0014-additional-sabredav-plugins-via-tagged-service-provider-registration.md).

## Extending The Server

The package is designed so applications can customize WebDAV behavior without breaking the default runtime pipeline.

Use this when you want to:

- add extra SabreDAV protocol behavior
- register listeners for SabreDAV lifecycle events
- attach custom headers or diagnostics
- keep the package defaults while extending the runtime

Example plugin:

```php
namespace App\WebDav\Plugins;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

final class CustomSabrePlugin extends ServerPlugin
{
    public function initialize(Server $server): void
    {
        $server->on('afterMethod:OPTIONS', function ($request, $response): void {
            $response->setHeader('X-App-WebDav', 'enabled');
        });
    }

    public function getPluginName(): string
    {
        return 'app-custom-sabre-plugin';
    }
}
```

Register it in your application service provider:

```php
namespace App\Providers;

use App\WebDav\Plugins\CustomSabrePlugin;
use Illuminate\Support\ServiceProvider;
use N3XT0R\LaravelWebdavServer\WebdavServerServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CustomSabrePlugin::class);
        $this->app->tag(
            [CustomSabrePlugin::class],
            WebdavServerServiceProvider::sabrePluginTag(),
        );
    }
}
```

Result:

- your plugin is added after the package-default plugins
- `SabreServerConfigurator` remains the active configurator
- existing package behavior stays intact unless your plugin intentionally changes it
