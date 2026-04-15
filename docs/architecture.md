# Architecture

Every WebDAV request passes through this pipeline:

```mermaid
flowchart TD
    A([HTTP Request\n/webdav/{space}/{path?}\nBasic Auth]) --> B[WebDavController]

    B --> C[WebDavServerFactory]

    C --> D{CredentialValidatorInterface}
    D -- invalid --> E([401 Unauthorized])
    D -- valid --> F[WebDavPrincipal\nid · displayName · user]

    F --> G{SpaceResolverInterface}
    G --> H[WebDavStorageSpace\ndisk · rootPath]

    H --> I[StorageRootCollection\nSabreDAV tree root]

    I --> J[StorageDirectory]
    I --> K[StorageFile]

    J & K --> L{PathAuthorizationInterface}
    L -- denied --> M([403 Forbidden\nSabre\DAV\Exception\Forbidden])
    L -- allowed --> N[Laravel Filesystem\nStorage::disk]

    C --> O[SabreDAV Server]
    O --> P[base_uri from config\nwebdav.base_uri]
```

All extension points use `bindIf()` – bind your own implementation in `AppServiceProvider::register()` and it takes
precedence automatically.

## Runtime Notes (Current State)

- CSRF bypass is registered in `WebdavServerServiceProvider::registerCsrfException()`.
- Middleware resolution is version-tolerant: `PreventRequestForgery` (Laravel 13+) with fallback to `VerifyCsrfToken`
  (Laravel <=12).
- Route shape already includes `{space}` (`routes/web.php`), while the default factory path currently resolves storage
  without consuming the route space parameter.

