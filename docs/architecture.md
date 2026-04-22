# Architecture

Every WebDAV request passes through this runtime flow:

```mermaid
flowchart TD
    A["HTTP Request<br/>/webdav/{space}/{path?}<br/>Basic Auth"] --> B["WebDavController::__invoke()"]

    B --> C{"Basic auth attempt present?"}
    C -- no --> D["401 Unauthorized + WWW-Authenticate"]
    C -- yes --> E["WebDavServerFactory::make(request)"]

    E --> F["DefaultRequestContextResolver::resolve(request)"]
    F --> G["RequestBasicCredentialsExtractor::extract(request)"]
    G -- missing/malformed --> H["MissingCredentialsException / InvalidCredentialsException"]

    F --> I["ValidatorPrincipalAuthenticator::authenticate(username, password)"]
    I --> J["CredentialValidatorInterface::validate(...)"]
    J -- invalid --> K["InvalidCredentialsException"]
    J -- valid --> L["WebDavPrincipalValueObject"]

    F --> M["RequestSpaceKeyResolver::resolve(request)"]
    M --> N["route('space') or config('webdav-server.storage.default_space')"]
    N -- invalid config --> O["RuntimeException"]

    F --> P["SpaceResolverInterface::resolve(principal, spaceKey)"]
    P --> Q["WebDavStorageSpaceValueObject (disk, rootPath)"]

    E --> R["StorageRootBuilder::build(principal, space)"]
    R --> S["StorageRootCollection"]
    S --> T["StorageDirectory / StorageFile"]
    T --> U{"PathAuthorizationInterface"}
    U -- denied --> V["Sabre\\DAV\\Exception\\Forbidden"]
    U -- allowed --> W["Laravel Filesystem disk"]

    E --> X["new Sabre\\DAV\\Server(root)"]
    X --> Y["SabreServerConfigurator::configure(server, spaceKey)"]
    Y --> Z["baseUri = /{webdav-server.base_uri}/{spaceKey}/ + logger"]
    Z --> AA["ServerRunnerInterface::run(server)"]
    AA --> AB["SabreServerRunner::run() => server->start(); exit;"]
```

All extension points are bound via `bindIf()` in `WebdavServerServiceProvider`, so app-level bindings can override defaults.

Related decisions:

- [ADR 0001: Test Architecture And Layering](adr/0001-test-architecture-and-layering.md)
- [ADR 0002: WebDAV Request Pipeline And Runtime Boundary](adr/0002-webdav-request-pipeline-and-runtime-boundary.md)
- [ADR 0005: WebDAV Space Key And Storage Space Mapping](adr/0005-webdav-space-key-and-storage-space-mapping.md)

## Runtime Notes (Current State)

- Route shape is `'/webdav/{space}/{path?}'` in `routes/web.php`.
- `spaceKey` is resolved from the `{space}` route parameter via `RequestSpaceKeyResolver`; falls back to `config('webdav-server.storage.default_space', 'default')` if the parameter is absent.
- Auth-related extractor/authenticator failures throw domain exceptions:
  - `MissingCredentialsException`
  - `InvalidCredentialsException`
- Controller runtime execution is delegated via `ServerRunnerInterface`.
- Default runner is `SabreServerRunner`, which starts SabreDAV and terminates the request lifecycle.
- CSRF bypass is registered in `WebdavServerServiceProvider::registerCsrfException()`.
- CSRF middleware resolution is version-tolerant:
  - `Illuminate\Foundation\Http\Middleware\PreventRequestForgery` (Laravel 13+)
  - fallback: `Illuminate\Foundation\Http\Middleware\VerifyCsrfToken` (Laravel 12)
- CSRF route prefix comes from `config('webdav-server.route_prefix')` and falls back to `config('webdav-server.base_uri')`.
- Base URI for SabreDAV is configured in `SabreServerConfigurator` via `config('webdav-server.base_uri', '/webdav/')`.
