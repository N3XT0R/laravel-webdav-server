# Common Questions & Clarifications

This document corrects common misconceptions about the package based on its actual implementation.

## Q: Does the package support `{user_id}` placeholders in config paths?

**A: No.** The package does **not** use string placeholders like `'path' => 'users/{user_id}'`.

Instead, user isolation happens via:

- **`SpaceResolverInterface::resolve($principal, $spaceKey)`**: Resolves storage space dynamically at runtime.
- **Default implementation** (`DefaultSpaceResolver`): Returns a `WebDavStorageSpace` with a path that includes
  `$principal->id`:

```php
// In DefaultSpaceResolver
return new WebDavStorageSpace(
    disk: $disk,
    rootPath: trim($root, '/') . '/' . $principal->id,  // User ID is appended here
);
```

So if `principal->id` is `42` and `config('webdav.storage.spaces.default.root')` is `webdav`, the resolved root is
`webdav/42`.

---

## Q: Do I need to implement an "AuthInterface" for custom authentication?

**A: No. Use `CredentialValidatorInterface` instead.**

See [docs/authentication.md](authentication.md) for custom authentication examples and best practices.

---

## Q: Are there file-level (per-file) authorization checks?

**A: No, only path-level checks.** Policies operate at the **path resource level**, not per individual file.

The policy receives `WebDavPathResourceDto` with `disk` and `path` properties:

```php
class WebDavPathPolicy
{
    public function read(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        // $resource->disk = 'local'
        // $resource->path = 'webdav/42/documents/report.pdf'
        
        // You can check folder prefixes, but not "allow this file but deny that file in the same folder"
        return str_starts_with($resource->path, 'webdav/42/');
    }
}
```

**Limitation:** WebDAV clients (Windows Explorer, Finder) typically expect full read/write permissions for the mounted
directory. Fine-grained per-file authorization is not ideal for WebDAV.

---

## Q: Does the package support CalDAV or CardDAV?

**A: No.** This package provides **WebDAV (file access) only**.

- It does not support CalDAV (calendar) or CardDAV (contacts).
- If you need calendar/contact sync, consider `monicahq/laravel-sabre` instead.

---

## Q: How do I restrict access to specific spaces per user?

**A: Override `SpaceResolverInterface`.**

The default resolver allows any space key from config. To restrict by user:

```php
// app/Services/RestrictedSpaceResolver.php
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

class RestrictedSpaceResolver implements SpaceResolverInterface
{
    public function __construct(private Config $config) {}

    public function resolve(WebDavPrincipal $principal, string $spaceKey): WebDavStorageSpace
    {
        // Example: Only allow 'default' space for non-admin users
        if ($spaceKey !== 'default' && !$principal->user?->isAdmin()) {
            throw new RuntimeException('Unauthorized space');
        }

        // Delegate to default resolver or implement your logic
        return $this->defaultResolve($spaceKey);
    }
}
```

Register in `AppServiceProvider`:

```php
$app->bind(SpaceResolverInterface::class, RestrictedSpaceResolver::class);
```

---

## Q: Can policies reject operations dynamically?

**A: Yes.** Policies are checked before every filesystem operation.

All five abilities are checked at the right times:

| Ability           | Operation       |
|-------------------|-----------------|
| `read`            | PROPFIND, GET   |
| `write`           | PUT (overwrite) |
| `delete`          | DELETE          |
| `createDirectory` | MKCOL           |
| `createFile`      | PUT (new file)  |

If a policy denies access, a `Sabre\DAV\Exception\Forbidden` is thrown and the WebDAV client receives a 403 error.

Example:

```php
public function delete(Authenticatable $user, WebDavPathResourceDto $resource): bool
{
    // Deny deletion of files in the 'archive' folder
    if (str_contains($resource->path, '/archive/')) {
        return false;
    }
    return true;
}
```

---

## Q: How do I link a WebDAV account to a Laravel user?

**A: Set `webdav-server.auth.user_model` in config and use the `user_id` column.**

See [docs/authentication.md](authentication.md#linking-webdav-accounts-to-laravel-users) for full details and code
examples.

---

## Q: What's the difference between this package and `monicahq/laravel-sabre`?

| Feature                | n3xt0r/laravel-webdav-server          | monicahq/laravel-sabre                  |
|------------------------|---------------------------------------|-----------------------------------------|
| **Focus**              | WebDAV (files) only                   | Full SabreDAV (WebDAV, CalDAV, CardDAV) |
| **Setup**              | Config-based (simple)                 | Code-based (complex)                    |
| **Laravel Filesystem** | Native integration                    | Manual node mapping                     |
| **User isolation**     | Built-in via `SpaceResolverInterface` | Manual per-request logic                |
| **Policies**           | Per-path checks                       | Per-node checks                         |
| **Extensibility**      | Contracts (bindIf)                    | Direct plugin API                       |

**Choose n3xt0r if:** You want a quick WebDAV server for file sharing with minimal setup.

**Choose monicahq/laravel-sabre if:** You need CalDAV/CardDAV or a highly customizable multi-protocol DAV server.

---

## Q: Is this package production-ready?

**A: Use with caution.** See the warning in `README.md`.

The package is under active development. APIs and configuration keys may change between releases. Consider:

- Running thorough security audits before exposing to the public.
- Testing HTTPS enforcement (WebDAV transmits Basic Auth credentials).
- Monitoring performance with your expected file sizes and user counts.

