# Configuration

All package config is loaded from `config/webdav-server.php` and accessed with the `webdav.*` key.

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
        'account_model' => \N3XT0R\LaravelWebdavServer\Models\WebDavAccount::class,
        'user_model' => \App\Models\User::class,
    ],
];
```

## Top-Level Keys

| Key                   | Default    | Used by                                 |
|-----------------------|------------|-----------------------------------------|
| `webdav.route_prefix` | `webdav`   | CSRF exclusion path in `WebdavServerServiceProvider` |
| `webdav.base_uri`     | `/webdav/` | `WebDavServerFactory::setBaseUri()`     | 

## Storage Spaces

`DefaultSpaceResolver` reads `webdav.storage.spaces` and resolves a space by key:

- `webdav.storage.default_space` (default: `default`)
- `webdav.storage.spaces.{space}.disk` (required)
- `webdav.storage.spaces.{space}.root` (required)

`WebDavServerFactory` determines the `spaceKey` in this order:

1. route parameter `{space}`
2. fallback `webdav.storage.default_space`

Resolved runtime root is always:
`{root}/{principal.id}`

Example:

- `disk = local`
- `root = webdav`
- `principal.id = 42`
- effective WebDAV root path: `webdav/42`

## Auth Mapping

`EloquentWebDavAccountRepository` and `WebDavAccount` use:

- `webdav.auth.account_model` (must be an Eloquent model class)
- `webdav.auth.user_model` (required if policies need `$principal->user`)
- `webdav.auth.username_column` (default `username`)
- `webdav.auth.password_column` (default `password_encrypted`)
- `webdav.auth.enabled_column` (default `enabled`, set empty string to skip)
- `webdav.auth.user_id_column` (default `user_id`)
- `webdav.auth.display_name_column` (default `username`)

## Current Development Notes

- Package route is currently registered as `/webdav/{space}/{path?}` in `routes/web.php`.
- `route_prefix` is currently used for CSRF exclusion and falls back to `base_uri` when empty.
- Legacy keys `webdav.storage.disk` and `webdav.storage.root` are still present in the config stub and are used by the
  packaged example policy (`src/Policies/WebDavPathPolicy.php`).
- New integrations should prefer `storage.spaces.*` for resolver-based storage mapping.

