# Authentication & Authorization

This document explains how authentication and authorization work in the package, with practical examples for common use
cases.

## Authentication Overview

The package uses **independent Basic Auth**, validated by `CredentialValidatorInterface`.

- Credentials are **not** validated against Laravel's `auth()` guard.
- Username/password come from the `webdav-server.auth.account_model` table.
- On successful validation, a `WebDavPrincipalValueObject` is returned (containing id, displayName, and optional user relation).

---

## Default: Database-Backed Authentication

By default, `DatabaseCredentialValidator` validates against `webdav_accounts` table:

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

Passwords are stored encrypted (hashed).

---

## Custom Authentication

To implement custom authentication (LDAP, API tokens, etc.), implement `CredentialValidatorInterface`:

```php
// app/Services/LdapCredentialValidator.php
namespace App\Services;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

class LdapCredentialValidator implements CredentialValidatorInterface
{
    public function validate(string $username, string $password): ?WebDavPrincipalValueObject
    {
        // Example: Validate against LDAP
        if (!$this->validateLdap($username, $password)) {
            return null;
        }

        // Get or create user in Laravel app
        $user = \App\Models\User::where('email', $username)->first();

        if (!$user) {
            $user = \App\Models\User::create([
                'email' => $username,
                'name' => $username,
                'password' => bcrypt(str_random(32)), // Temporary
            ]);
        }

        // Return WebDAV principal
        return new WebDavPrincipalValueObject(
            id: (string) $user->id,
            displayName: $user->name,
            user: $user,
        );
    }

    private function validateLdap(string $username, string $password): bool
    {
        // Your LDAP logic here
        return true;
    }
}
```

Register in your `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use App\Services\LdapCredentialValidator;

public function register()
{
    $this->app->bind(CredentialValidatorInterface::class, LdapCredentialValidator::class);
}
```

---

## Authorization Overview

Authorization is handled by `PathAuthorizationInterface` and Laravel Policies.

Every filesystem operation (read, write, delete, etc.) checks the policy before execution.

---

## Default: Gate-Based Policies

By default, `GatePathAuthorization` uses Laravel's Gate system.

The service provider auto-registers `App\Policies\WebDavPathPolicy`. Create it in your app:

```php
// app/Policies/WebDavPathPolicy.php
namespace App\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use N3XT0R\LaravelWebdavServer\DTO\Auth\WebDavPathResourceDto;

class WebDavPathPolicy
{
    public function read(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        // $user is WebDavPrincipalValueObject with optional user relation
        // $resource->disk = 'local'
        // $resource->path = 'webdav/42/documents/report.pdf'

        // Example: Allow if path starts with user's root
        return str_starts_with($resource->path, 'webdav/'.$user->id.'/');
    }

    public function write(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    public function delete(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    public function createDirectory(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    public function createFile(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return $this->isUserPath($user, $resource);
    }

    private function isUserPath(Authenticatable $user, WebDavPathResourceDto $resource): bool
    {
        return str_starts_with($resource->path, 'webdav/'.$user->id.'/');
    }
}
```

---

## Advanced: Custom Authorization Handler

To replace Gate-based authorization entirely, implement `PathAuthorizationInterface`:

```php
// app/Services/CustomPathAuthorization.php
namespace App\Services;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use Sabre\DAV\Exception\Forbidden;

class CustomPathAuthorization implements PathAuthorizationInterface
{
    public function authorizeRead(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        if (!$this->canRead($principal, $path)) {
            throw new Forbidden('Read access denied');
        }
    }

    public function authorizeWrite(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        if (!$this->canWrite($principal, $path)) {
            throw new Forbidden('Write access denied');
        }
    }

    public function authorizeDelete(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        if (!$this->canDelete($principal, $path)) {
            throw new Forbidden('Delete access denied');
        }
    }

    public function authorizeCreateDirectory(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        if (!$this->canCreateDirectory($principal, $path)) {
            throw new Forbidden('Directory creation denied');
        }
    }

    public function authorizeCreateFile(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        if (!$this->canCreateFile($principal, $path)) {
            throw new Forbidden('File creation denied');
        }
    }

    // Your authorization logic
    private function canRead(WebDavPrincipalValueObject $principal, string $path): bool
    {
        return true; // Implement your logic
    }

    private function canWrite(WebDavPrincipalValueObject $principal, string $path): bool
    {
        return true;
    }

    private function canDelete(WebDavPrincipalValueObject $principal, string $path): bool
    {
        return true;
    }

    private function canCreateDirectory(WebDavPrincipalValueObject $principal, string $path): bool
    {
        return true;
    }

    private function canCreateFile(WebDavPrincipalValueObject $principal, string $path): bool
    {
        return true;
    }
}
```

Register in `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use App\Services\CustomPathAuthorization;

public function register()
{
    $this->app->bind(PathAuthorizationInterface::class, CustomPathAuthorization::class);
}
```

---

## Linking WebDAV Accounts to Laravel Users

To associate a WebDAV account with your app's user model:

```php
// config/webdav-server.php
'auth' => [
    'account_model' => \App\Models\WebDavAccount::class,
    'user_model' => \App\Models\User::class,
    'user_id_column' => 'user_id',
],
```

The `WebDavAccountModel` model includes a `user()` relationship:

```php
// In your policy
public function read(Authenticatable $user, WebDavPathResourceDto $resource): bool
{
    // $user is a WebDavAccountModel
    $appUser = $user->user; // Get associated Laravel user

    if (!$appUser) {
        return false;
    }

    // Check Laravel user permissions
    return $appUser->can('access-webdav');
}
```

---

## WebDAV Client Access

Clients authenticate with Basic Auth (username/password from `webdav_accounts` table):

```
URL:      webdav://your-domain.test/webdav/default
Username: (from webdav_accounts.username)
Password: (from webdav_accounts.password_encrypted, decrypted at runtime)
```

The authenticated principal is determined by `CredentialValidatorInterface::validate()`.

---

## Security Notes

- **Always use HTTPS** for production (Basic Auth transmits credentials in Base64).
- WebDAV clients typically cache credentials — consider password rotation policies.
- The `WebDavPrincipalValueObject` object is available throughout the request lifecycle via policy/authorization checks.
- Never store plaintext passwords; the default `DatabaseCredentialValidator` expects hashed passwords.
