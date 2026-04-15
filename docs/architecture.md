# Architecture

Every WebDAV request passes through this runtime flow:

```mermaid
flowchart TD
    A["HTTP Request<br/>/webdav/{space}/{path?}<br/>Basic Auth"] --> B["WebDavController"]
    B --> C["WebDavServerFactory::make(request)"]

    C --> D{"Basic credentials present?"}
    D -- no --> E["RuntimeException: Missing Basic Auth credentials"]
    D -- yes --> F["CredentialValidatorInterface::validate(username, password)"]

    F -- invalid --> G["RuntimeException: Invalid WebDAV credentials"]
    F -- valid --> H["WebDavPrincipal (id, displayName, user)"]

    H --> I["SpaceResolverInterface::resolve(principal)"]
    I --> J["WebDavStorageSpace (disk, rootPath)"]

    J --> K["StorageRootCollection (SabreDAV tree root)"]
    K --> L["StorageDirectory"]
    K --> M["StorageFile"]

    L --> N{"PathAuthorizationInterface"}
    M --> N
    N -- denied --> O["SabreDAV Forbidden exception"]
    N -- allowed --> P["Laravel Filesystem (Storage::disk)"]

    C --> Q["SabreDAV Server"]
    Q --> R["setBaseUri(config('webdav.base_uri', '/webdav/'))"]
```

All extension points use `bindIf()` – bind your own implementation in `AppServiceProvider::register()` and it takes
precedence automatically.

## Runtime Notes (Current State)

- CSRF bypass is registered in `WebdavServerServiceProvider::registerCsrfException()`.
- Middleware resolution is version-tolerant: `PreventRequestForgery` (Laravel 13+) with fallback to
  `VerifyCsrfToken` (Laravel 12).
- CSRF route prefix comes from `webdav.route_prefix` and falls back to `webdav.base_uri`.
- Route shape includes `{space}` (`routes/web.php`), but the current factory call resolves storage via
  `SpaceResolverInterface::resolve($principal)` without passing the route space parameter.

