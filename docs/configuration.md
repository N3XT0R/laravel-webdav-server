# Configuration

All package configuration is loaded from `config/webdav-server.php` and accessed through the `webdav-server.*` key.

## Quick Start

```php
return [
    'base_uri' => '/webdav/',
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

| Key                          | Default    | Used by                                              |
|------------------------------|------------|------------------------------------------------------|
| `webdav-server.route_prefix` | `webdav`   | CSRF exclusion path in `WebdavServerServiceProvider` |
| `webdav-server.base_uri`     | `/webdav/` | `SabreServerConfigurator`                            |

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
- `storage.spaces.*` is the active storage configuration model.
- The packaged reference policy is `src/Policies/PathPolicy.php`.
