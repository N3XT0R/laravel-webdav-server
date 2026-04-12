# Architecture

Every WebDAV request passes through this pipeline:

```mermaid
flowchart TD
    A([HTTP Request\nBasic Auth]) --> B[WebDavController]

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
```

All extension points use `bindIf()` – bind your own implementation in `AppServiceProvider::register()` and it takes
precedence automatically.

