# Authentication & Authorization

This document explains how authentication and authorization work in the package and which extension points you can
override.

The authentication and authorization extension points documented here are part of the structurally stable public
package surface for the current `beta` release line.

## Authentication Overview

The package uses independent HTTP Basic Auth validated through `CredentialValidatorInterface`.

- credentials are not checked through Laravel's `auth()` guard
- username and password come from the configured `webdav-server.auth.account_model`
- on success, the validator returns a `WebDavPrincipalValueObject`
- on failure, the validator throws a package auth exception such as `InvalidCredentialsException`
- if `webdav-server.logging.driver` is configured, authentication outcomes are logged at `info`
- debug logging traces credential extraction and validator flow without logging raw credentials

## Default: Database-Backed Authentication

By default, `DatabaseCredentialValidator` uses `EloquentAccountRepository` plus the configured account model:

```php
// config/webdav-server.php
'auth' => [
    'account_model' => \App\Models\WebDavAccount::class,
    'user_model' => \App\Models\User::class,
    'username_column' => 'username',
    'password_column' => 'password_encrypted',
    'enabled_column' => 'enabled',
    'user_id_column' => 'user_id',
    'display_name_column' => 'username',
],
```

Passwords must be stored as hashes.

## Managing WebDAV Accounts With Artisan

For the default Eloquent-backed account model, the package ships with dedicated artisan commands.

Create an account:

```bash
php artisan laravel-webdav-server:account:create testuser password --display-name="Test User" --user-id=1
```

Show one account:

```bash
php artisan laravel-webdav-server:account:show testuser
```

List all accounts:

```bash
php artisan laravel-webdav-server:account:list
```

Update an account:

```bash
php artisan laravel-webdav-server:account:update testuser --password=new-password --disable
```

Important behavior:

- the commands use the configured `webdav-server.auth.account_model`
- username, password, enabled, linked-user, and display-name fields follow the configured auth column mapping
- passwords passed to the create and update commands are always stored as hashes
- the root command `php artisan laravel-webdav-server` shows the package-specific command entry points

## Custom Authentication

To implement custom authentication such as LDAP or API token lookup, implement `CredentialValidatorInterface`:

```php
namespace App\Services;

use App\Models\User;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final class LdapCredentialValidator implements CredentialValidatorInterface
{
    public function validate(string $username, string $password): WebDavPrincipalValueObject
    {
        if (! $this->validateLdap($username, $password)) {
            throw new InvalidCredentialsException('Invalid WebDAV credentials.');
        }

        $user = User::firstOrCreate(
            ['email' => $username],
            ['name' => $username, 'password' => bcrypt(bin2hex(random_bytes(16)))],
        );

        return new WebDavPrincipalValueObject(
            id: (string) $user->getAuthIdentifier(),
            displayName: $user->name,
            user: $user,
        );
    }

    private function validateLdap(string $username, string $password): bool
    {
        return true;
    }
}
```

Register it in your application:

```php
use App\Services\LdapCredentialValidator;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;

public function register(): void
{
    $this->app->bind(CredentialValidatorInterface::class, LdapCredentialValidator::class);
}
```

## Authorization Overview

Authorization is handled by `PathAuthorizationInterface`.

The default implementation is `GatePathAuthorization`, which delegates to Laravel Gate / policies and throws
`Sabre\DAV\Exception\Forbidden` when access is denied.

If logging is enabled, `GatePathAuthorization` emits:

- `debug` before the Gate check with ability, principal, disk, and path context
- `info` when access is denied

## Default: Gate-Based Policies

The package registers its own reference policy for `PathResourceDto`.
If your application needs custom rules, register your own policy in the app:

```php
use App\Policies\PathPolicy;
use Illuminate\Support\Facades\Gate;
use N3XT0R\LaravelWebdavServer\DTO\Auth\PathResourceDto;

public function boot(): void
{
    Gate::policy(PathResourceDto::class, PathPolicy::class);
}
```

```php
namespace App\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use N3XT0R\LaravelWebdavServer\DTO\Auth\PathResourceDto;

final class PathPolicy
{
    public function read(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    public function write(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    public function delete(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    public function createDirectory(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    public function createFile(Authenticatable $user, PathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    private function isUserPath(Authenticatable $user, PathResourceDto $resource): bool
    {
        return str_starts_with($resource->path, 'webdav/'.$user->getAuthIdentifier().'/')
            || $resource->path === 'webdav/'.$user->getAuthIdentifier();
    }
}
```

## Custom Authorization Adapter

To replace Gate-based authorization entirely, implement `PathAuthorizationInterface`:

```php
namespace App\Services;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use Sabre\DAV\Exception\Forbidden;

final class CustomPathAuthorization implements PathAuthorizationInterface
{
    public function authorizeRead(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        if (! $this->canRead($principal, $disk, $path)) {
            throw new Forbidden('Read access denied.');
        }
    }

    public function authorizeWrite(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        if (! $this->canWrite($principal, $disk, $path)) {
            throw new Forbidden('Write access denied.');
        }
    }

    public function authorizeDelete(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        if (! $this->canDelete($principal, $disk, $path)) {
            throw new Forbidden('Delete access denied.');
        }
    }

    public function authorizeCreateDirectory(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        if (! $this->canCreateDirectory($principal, $disk, $path)) {
            throw new Forbidden('Directory creation denied.');
        }
    }

    public function authorizeCreateFile(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        if (! $this->canCreateFile($principal, $disk, $path)) {
            throw new Forbidden('File creation denied.');
        }
    }

    private function canRead(WebDavPrincipalValueObject $principal, string $disk, string $path): bool
    {
        return true;
    }

    private function canWrite(WebDavPrincipalValueObject $principal, string $disk, string $path): bool
    {
        return true;
    }

    private function canDelete(WebDavPrincipalValueObject $principal, string $disk, string $path): bool
    {
        return true;
    }

    private function canCreateDirectory(WebDavPrincipalValueObject $principal, string $disk, string $path): bool
    {
        return true;
    }

    private function canCreateFile(WebDavPrincipalValueObject $principal, string $disk, string $path): bool
    {
        return true;
    }
}
```

Register it in your application:

```php
use App\Services\CustomPathAuthorization;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;

public function register(): void
{
    $this->app->bind(PathAuthorizationInterface::class, CustomPathAuthorization::class);
}
```

## Linked Laravel Users

If your account model links to a Laravel user, the resolved principal carries that user in `$principal->user`.
That is the user object Gate / policies receive by default.

If your policy logic depends on linked users, make sure:

- `webdav-server.auth.user_model` is configured
- your configured account model exposes a `user()` relationship

## Related Runtime Exceptions

The authentication and authorization pipeline uses package-specific exceptions instead of generic runtime failures.

Common examples:

- `MissingCredentialsException` when no Basic Auth credentials can be extracted
- `InvalidCredentialsException` when the credential validator rejects the supplied credentials
- `AccountNotFoundException` or `AccountDisabledException` when the default account repository rejects the record
- `UnauthenticatedPrincipalException` when a principal is requested before successful authentication

Authorization denials remain mapped to `Sabre\DAV\Exception\Forbidden` because that is the SabreDAV boundary contract.
