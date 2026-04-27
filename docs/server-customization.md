# Server Customization

This page covers SabreDAV runtime extensions that go beyond configuration keys alone.

Use this when you want to:

- add extra SabreDAV protocol behavior
- register listeners for SabreDAV lifecycle events
- attach custom headers or diagnostics
- keep the package defaults while extending the runtime

## Additional SabreDAV Plugins

Applications can register additional SabreDAV `ServerPlugin` instances through the Laravel container without replacing
the package configurator.

Register the plugin in your application service provider and tag it with
`WebdavServerServiceProvider::sabrePluginTag()`:

```php
use App\WebDav\Plugins\CustomSabrePlugin;
use N3XT0R\LaravelWebdavServer\WebdavServerServiceProvider;

public function register(): void
{
    $this->app->singleton(CustomSabrePlugin::class);
    $this->app->tag(
        [CustomSabrePlugin::class],
        WebdavServerServiceProvider::sabrePluginTag(),
    );
}
```

Tagged plugins are attached after the package defaults:

- `MissingPathPropFindPlugin`
- `CompatibilityLoggingPlugin`
- optional `Browser\Plugin` when `browser_listing` is enabled

This extension mechanism is part of the package's stable server-customization model and is documented in
[ADR 0014](adr/0014-additional-sabredav-plugins-via-tagged-service-provider-registration.md).

## Example Plugin

```php
namespace App\WebDav\Plugins;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

final class CustomSabrePlugin extends ServerPlugin
{
    public function initialize(Server $server): void
    {
        $server->on('afterMethod:OPTIONS', function ($request, $response): void {
            $response->setHeader('X-App-WebDav', 'enabled');
        });
    }

    public function getPluginName(): string
    {
        return 'app-custom-sabre-plugin';
    }
}
```

Register it in your application service provider:

```php
namespace App\Providers;

use App\WebDav\Plugins\CustomSabrePlugin;
use Illuminate\Support\ServiceProvider;
use N3XT0R\LaravelWebdavServer\WebdavServerServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CustomSabrePlugin::class);
        $this->app->tag(
            [CustomSabrePlugin::class],
            WebdavServerServiceProvider::sabrePluginTag(),
        );
    }
}
```

Result:

- your plugin is added after the package-default plugins
- `SabreServerConfigurator` remains the active configurator
- existing package behavior stays intact unless your plugin intentionally changes it

## Replacing PathResolverInterface

`PathResolverInterface` controls how the user-scoped filesystem root path and the WebDAV mount URL are assembled.
The default implementation is `PathResolverService`.

To override the path assembly formula, implement the interface and bind it in your service provider:

```php
namespace App\Services;

use N3XT0R\LaravelWebdavServer\Contracts\Storage\PathResolverInterface;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final class CustomPathResolver implements PathResolverInterface
{
    public function resolvePath(WebDavPrincipalValueObject $principal, string $spaceKey): string
    {
        // Custom path assembly logic.
        return 'custom/'.$spaceKey.'/'.$principal->id;
    }

    public function resolveUrl(string $spaceKey): string
    {
        // Custom URL assembly logic.
        return 'https://files.example.com/'.$spaceKey;
    }
}
```

```php
use App\Services\CustomPathResolver;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\PathResolverInterface;

public function register(): void
{
    $this->app->bind(PathResolverInterface::class, CustomPathResolver::class);
}
```

Replacing `PathResolverInterface` affects both `DefaultSpaceResolver` (which delegates path assembly to it) and the
`WebDavPath` Facade (which resolves through the same binding).
