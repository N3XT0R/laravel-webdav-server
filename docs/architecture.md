# Architecture

Every WebDAV request passes through this runtime flow:

![Laravel WebDAV Server request flow](assets/architecture-request-flow.svg)

1. `WebDavController::__invoke()` accepts the incoming request for `/webdav/{space}/{path?}`.
2. If no Basic Auth attempt is present, the controller returns `401 Unauthorized` with `WWW-Authenticate`.
3. If credentials are present, `WebDavServerFactory::make(request)` builds the SabreDAV server instance.
4. `DefaultRequestContextResolver::resolve(request)` gathers the runtime context:
   - `RequestBasicCredentialsExtractor::extract(request)` parses credentials
   - `ValidatorPrincipalAuthenticator::authenticate(username, password)` resolves the principal through
     `CredentialValidatorInterface`
   - `RequestSpaceKeyResolver::resolve(request)` resolves `{space}` or falls back to
     `config('webdav-server.storage.default_space')`
   - `SpaceResolverInterface::resolve(principal, spaceKey)` resolves the effective storage target as a
     `WebDavStorageSpaceValueObject`
5. `StorageRootBuilder::build(principal, space)` creates the SabreDAV root tree:
   - `StorageRootCollection`
   - `StorageDirectory` / `StorageFile`
6. Before filesystem operations execute, node classes call `PathAuthorizationInterface`.
   On denial, the package throws `Sabre\DAV\Exception\Forbidden`.
7. Allowed operations run against the resolved Laravel filesystem disk.
8. `SabreServerConfigurator::configure(server, spaceKey)` applies runtime configuration such as the effective base URI.
9. `ServerRunnerInterface::run(server)` hands off execution to the runtime adapter.
10. The default adapter `SabreServerRunner` starts SabreDAV and terminates the request lifecycle.

All extension points are bound via `bindIf()` in `WebdavServerServiceProvider`, so app-level bindings can override defaults.

Related decisions:

- [ADR 0001: Test Architecture And Layering](adr/0001-test-architecture-and-layering.md)
- [ADR 0002: WebDAV Request Pipeline And Runtime Boundary](adr/0002-webdav-request-pipeline-and-runtime-boundary.md)
- [ADR 0005: WebDAV Space Key And Storage Space Mapping](adr/0005-webdav-space-key-and-storage-space-mapping.md)
- [ADR 0006: Path Authorization Via Laravel Gates And Policies](adr/0006-path-authorization-via-laravel-gates-and-policies.md)
- [ADR 0007: SabreDAV Runtime Decoupling](adr/0007-sabredav-runtime-decoupling.md)

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
