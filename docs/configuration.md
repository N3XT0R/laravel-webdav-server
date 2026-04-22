# Configuration

All package config is loaded from `config/webdav-server.php` and accessed with the `webdav-server.*` key.

## Quick Start

```php
return [
    'base_uri' => '/webdav/',
    'storage' => [
        'default_space' => 'default',
        'spaces' => [
            'default' => ['disk' => 'local', 'root' => 'webdav'],
        ],
    ],
    'auth' => [
        'account_model' => \N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel::class,
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

`DefaultSpaceResolver` reads `webdav-server.storage.spaces` and resolves a space by key:

- `webdav-server.storage.default_space` (default: `default`)
- `webdav-server.storage.spaces.{space}.disk` (required)
- `webdav-server.storage.spaces.{space}.root` (required)

`WebDavServerFactory` determines the `spaceKey` in this order:

1. route parameter `{space}`
2. fallback `webdav-server.storage.default_space`

Resolved runtime root is always:
`{root}/{principal.id}`

Example:

- `disk = local`
- `root = webdav`
- `principal.id = 42`
- effective WebDAV root path: `webdav/42`

If a space defines a non-empty `prefix`, the effective root becomes:
`{root}/{prefix}/{principal.id}`

## Auth Mapping

`EloquentWebDavAccountRepository` and `WebDavAccountModel` use:

- `webdav-server.auth.account_model` (must be an Eloquent model class)
- `webdav-server.auth.user_model` (required if policies need `$principal->user`)
- `webdav-server.auth.username_column` (default `username`)
- `webdav-server.auth.password_column` (default `password_encrypted`)
- `webdav-server.auth.enabled_column` (default `enabled`, set empty string to skip)
- `webdav-server.auth.user_id_column` (default `user_id`)
- `webdav-server.auth.display_name_column` (default `username`)

## Current Development Notes

- Package route is currently registered as `/webdav/{space}/{path?}` in `routes/web.php`.
- `route_prefix` is currently used for CSRF exclusion and falls back to `base_uri` when empty.
- `storage.spaces.*` is the active storage configuration model for resolver-based storage mapping.
- The packaged reference policy `src/Policies/WebDavPathPolicy.php` evaluates access against the configured spaces,
  including optional `prefix` segments.
