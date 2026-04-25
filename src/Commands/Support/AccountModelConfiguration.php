<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Commands\Support;

final readonly class AccountModelConfiguration
{
    /**
     * Create the resolved account-model configuration used by the artisan account commands.
     *
     * @param  class-string  $modelClass  Configured Eloquent model class used to store WebDAV accounts.
     * @param  string  $usernameColumn  Column that stores the Basic Auth username.
     * @param  string  $passwordColumn  Column that stores the hashed Basic Auth password.
     * @param  string|null  $enabledColumn  Optional column that stores whether the account is enabled.
     * @param  string|null  $userIdColumn  Optional column that links the WebDAV account to a Laravel user.
     * @param  string|null  $displayNameColumn  Optional column that stores the principal display name.
     */
    public function __construct(
        public string $modelClass,
        public string $usernameColumn,
        public string $passwordColumn,
        public ?string $enabledColumn,
        public ?string $userIdColumn,
        public ?string $displayNameColumn,
    ) {}
}
