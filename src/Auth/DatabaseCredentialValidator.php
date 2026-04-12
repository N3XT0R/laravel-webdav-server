<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Auth;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use N3XT0R\LaravelWebdavServer\Contracts\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final readonly class DatabaseCredentialValidator implements CredentialValidatorInterface
{
    public function __construct(
        private Config $config,
    ) {
    }

    public function validate(string $username, string $password): ?WebDavPrincipal
    {
        $modelClass = $this->config->get('webdav.auth.model');

        if (!$modelClass || !is_subclass_of($modelClass, Model::class)) {
            throw new \RuntimeException('Invalid or missing webdav.auth.model configuration');
        }

        $usernameColumn = $this->config->get('webdav.auth.username_column', 'username');
        $passwordColumn = $this->config->get('webdav.auth.password_column', 'password');
        $enabledColumn = $this->config->get('webdav.auth.enabled_column', 'enabled');
        $userIdColumn = $this->config->get('webdav.auth.user_id_column', 'id');
        $displayNameColumn = $this->config->get('webdav.auth.display_name_column', $usernameColumn);

        /** @var Model $account */
        $account = $modelClass::query()
            ->where($usernameColumn, $username)
            ->when($enabledColumn, fn($q) => $q->where($enabledColumn, true))
            ->first();

        if (!$account) {
            return null;
        }

        $hashedPassword = $account->{$passwordColumn};

        if (!Hash::check($password, $hashedPassword)) {
            return null;
        }

        return new WebDavPrincipal(
            (string)$account->{$userIdColumn},
            (string)$account->{$displayNameColumn},
        );
    }
}