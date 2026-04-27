# Configuration

All package configuration is loaded from `config/webdav-server.php` and accessed through the `webdav-server.*` key.

## Most Important Settings

Start with these keys in most integrations:

- `webdav-server.base_uri` for the SabreDAV base URI
- `webdav-server.storage.default_space` for the fallback space key
- `webdav-server.storage.spaces.{space}.disk` and `.root` for storage routing
- `webdav-server.auth.account_model` for the WebDAV account store
- `webdav-server.auth.user_model` if policies should receive a linked Laravel user
- `webdav-server.logging.driver` and `.level` for package and SabreDAV logging

For SabreDAV runtime extensions and tagged plugins, see [Server Customization](server-customization.md).

## Published Config Stub

The published configuration currently looks like this:

```php
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;

return [
    'route_prefix' => 'webdav',
    'base_uri' => '/webdav/',
    'browser_listing' => false,
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
                'prefix' => '/',
            ],
        ],
    ],

    'auth' => [
        'account_model' => WebDavAccountModel::class,
        'user_model' => null,

        'username_column' => 'username',
        'password_column' => 'password_encrypted',
        'enabled_column' => 'enabled',

        'user_id_column' => 'user_id',
        'display_name_column' => 'display_name',
    ],
];
```

## Top-Level Keys

| Key                              | Default    | Used by |
|----------------------------------|------------|---------|
| `webdav-server.route_prefix`     | `webdav`   | CSRF exclusion registration in `WebdavServerServiceProvider` |
| `webdav-server.base_uri`         | `/webdav/` | SabreDAV base URI in `SabreServerConfigurator` |
| `webdav-server.browser_listing`  | `false`    | Enables SabreDAV `Browser\Plugin` |
| `webdav-server.logging.driver`   | `null`     | Package logging and SabreDAV logger wiring |
| `webdav-server.logging.level`    | `info`     | Minimum package log level |

## Route Prefix And Base URI

These two settings affect different parts of the runtime.

- `route_prefix` is used for CSRF exclusion registration
- if `route_prefix` is empty, CSRF exclusion falls back to `base_uri`
- `base_uri` is used by `SabreServerConfigurator` to build the effective SabreDAV base URI for the resolved space

The package route itself is currently registered from `routes/web.php` as:

```text
/webdav/{space}/{path?}
```

That route shape is not derived dynamically from `route_prefix` or `base_uri`.

With the default configuration and the `default` space key, the effective SabreDAV base URI becomes:

```text
/webdav/default/
```

## Browser Listing

When `browser_listing` is set to `true`, the SabreDAV `Browser\Plugin` is attached to the runtime. This renders an
HTML directory listing when a WebDAV space is accessed from a browser.

```php
'browser_listing' => true,
```

The browser plugin is disabled by default because it exposes the directory tree to any authenticated HTTP client
without any additional access control beyond the package's existing path authorization.

When the browser listing is active, SabreDAV renders two HTML forms on every directory page:

- **Create folder** - submits a `POST` request that SabreDAV converts to a `MKCOL` operation internally
- **Upload file** - submits a `POST` request that SabreDAV converts to a `PUT` operation internally

Both forms work out of the box. The package route accepts `POST` for this purpose, and the WebDAV endpoint is
automatically excluded from Laravel's CSRF middleware so browser submissions are not rejected.

!!! note
    Enable this in development or internal environments only. Do not enable it in production unless path
    authorization is explicitly configured to restrict access.

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
- use `debug` during development to trace credential extraction, request-context resolution, storage resolution,
  authorization checks, server setup, and Windows-relevant DAV handling

## Storage Spaces

`DefaultSpaceResolver` reads `webdav-server.storage.spaces` and resolves a logical space key to one effective storage
target.

Relevant keys:

- `webdav-server.storage.default_space` (default: `default`)
- `webdav-server.storage.spaces.{space}.disk` (required)
- `webdav-server.storage.spaces.{space}.root` (required)
- `webdav-server.storage.spaces.{space}.prefix` (optional)

`RequestSpaceKeyResolver` determines the `spaceKey` in this order:

1. route parameter `{space}`
2. fallback `webdav-server.storage.default_space`

The resolved runtime root always appends the authenticated principal ID.

If `prefix` is missing, empty, or exactly `/`, the effective root becomes:

```text
{root}/{principal.id}
```

If `prefix` is a non-empty path segment, the effective root becomes:

```text
{root}/{prefix}/{principal.id}
```

Example:

- `disk = local`
- `root = webdav`
- `prefix = uploads`
- `principal.id = 42`
- effective root path: `webdav/uploads/42`

## Path Resolution

`PathResolverService` is the single authoritative location for the path assembly formula. `DefaultSpaceResolver`
delegates to it instead of assembling the path inline.

The service is bound to `PathResolverInterface` and can be replaced via the Laravel container.

### Methods

`resolvePath(WebDavPrincipalValueObject $principal, string $spaceKey): string`

Returns the user-scoped filesystem root path for the given principal and space. This is the disk-internal path, not
a URL.

```php
// disk: local, root: webdav, principal.id: 42  →  webdav/42
// disk: local, root: webdav, prefix: team, principal.id: 7  →  webdav/team/7
```

`resolveUrl(string $spaceKey): string`

Returns the public WebDAV mount URL for the given space. This is the URL a WebDAV client connects to — it does not
include any user-specific path segment.

```php
// route_prefix: webdav, spaceKey: default  →  https://app.test/webdav/default
```

### WebDavPath Facade

The `WebDavPath` Facade exposes both methods for use in application controllers and views without triggering the full
WebDAV request pipeline.

`resolveUrl()` needs only the space key and can be called without a principal:

```php
use N3XT0R\LaravelWebdavServer\Facades\WebDavPath;

$url = WebDavPath::resolveUrl('default');
// → 'https://app.test/webdav/default'
```

`resolvePath()` accepts any `AccountInterface` — the type returned by `AccountRepositoryInterface::findEnabledByUsername()`.
Outside of an active WebDAV request, retrieve the account from the repository and pass it directly:

```php
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\AccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\Facades\WebDavPath;

$account = app(AccountRepositoryInterface::class)->findEnabledByUsername($username);

$path = WebDavPath::resolvePath($account, 'default');
// → 'webdav/42'
```

`resolvePath()` also accepts a `WebDavPrincipalValueObject` — both implement `WebDavPrincipalInterface`, which is the
actual parameter type. During a WebDAV request the principal value object flows through the pipeline automatically;
outside a request the repository is the natural entry point.

This is useful for presenting WebDAV connection details to users in the frontend without requiring an active WebDAV
request.

To replace the path assembly logic, bind your own implementation to `PathResolverInterface` in your application
service provider:

```php
use App\Services\CustomPathResolver;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\PathResolverInterface;

public function register(): void
{
    $this->app->bind(PathResolverInterface::class, CustomPathResolver::class);
}
```

## Auth Mapping

`EloquentAccountRepository` reads the account model and its column mapping from `webdav-server.auth`.

Relevant keys:

- `webdav-server.auth.account_model`
- `webdav-server.auth.user_model`
- `webdav-server.auth.username_column`
- `webdav-server.auth.password_column`
- `webdav-server.auth.enabled_column`
- `webdav-server.auth.user_id_column`
- `webdav-server.auth.display_name_column`

Current defaults:

- `account_model` defaults to the package model `N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel`
- `user_model` defaults to `null`
- `username_column` defaults to `username`
- `password_column` defaults to `password_encrypted`
- `enabled_column` defaults to `enabled`
- `user_id_column` defaults to `user_id`
- `display_name_column` defaults to `display_name`

Behavior notes:

- `account_model` must be an Eloquent model class
- `user_model` is required if your policies should operate on `$principal->user`
- `enabled_column`, `user_id_column`, and `display_name_column` are treated as optional mappings
- setting one of those optional column mappings to `null` or an empty string disables that mapping

## Failure Modes

Invalid auth and storage configuration raises domain-specific package exceptions instead of generic runtime
exceptions.

Examples:

- `InvalidAccountConfigurationException`
- `MissingUserModelConfigurationException`
- `SpaceNotConfiguredException`
- `InvalidSpaceConfigurationException`
- `InvalidDefaultSpaceConfigurationException`

## Notes

- The package registers the WebDAV route shape `/webdav/{space}/{path?}`.
- `route_prefix` is used for CSRF exclusion and falls back to `base_uri` when empty.
- `base_uri` is used to build the effective SabreDAV base URI together with the resolved `spaceKey`.
- `logging.driver = null` disables all package and SabreDAV logging.
- `storage.spaces.*` is the active storage configuration model.
- `prefix = '/'` behaves like no extra prefix segment.
- The package service provider registers `Gate::policy(PathResourceDto::class, PathPolicy::class)` by default.

## Related Pages

- [Getting Started](getting-started.md)
- [Authentication & Authorization](authentication.md)
- [Architecture](architecture.md)
- [Server Customization](server-customization.md)
