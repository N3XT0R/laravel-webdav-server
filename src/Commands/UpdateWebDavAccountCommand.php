<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Commands;

use N3XT0R\LaravelWebdavServer\DTO\Management\AccountUpdateDto;
use N3XT0R\LaravelWebdavServer\Services\AccountManagementService;

final class UpdateWebDavAccountCommand extends AccountCommand
{
    protected $signature = 'laravel-webdav-server:account:update
        {username : Username of the WebDAV account that should be updated.}
        {--new-username= : Replace the current Basic Auth username.}
        {--secret= : Replace the stored credential with a newly hashed value.}
        {--display-name= : Replace the stored display name.}
        {--clear-display-name : Clear the stored display name.}
        {--user-id= : Replace the linked Laravel user identifier.}
        {--clear-user-id : Clear the linked Laravel user identifier.}
        {--enable : Mark the account as enabled.}
        {--disable : Mark the account as disabled.}';

    protected $description = 'Update an existing WebDAV account in the configured account model.';

    /**
     * Update one configured WebDAV account using additive CLI options.
     *
     * @param  AccountManagementService  $service  Service that handles account update business logic.
     * @return int Symfony-compatible command exit code.
     */
    public function handle(AccountManagementService $service): int
    {
        if ($this->hasConflictingOptions()) {
            return self::FAILURE;
        }

        $username = (string) $this->argument('username');
        $account = $service->findByUsername($username);

        if ($account === null) {
            $this->components->error("No WebDAV account found for username '{$username}'.");

            return self::FAILURE;
        }

        $dto = new AccountUpdateDto(
            newUsername: $this->option('new-username'),
            password: $this->option('secret'),
            displayName: $this->option('display-name'),
            clearDisplayName: (bool) $this->option('clear-display-name'),
            userId: $this->option('user-id'),
            clearUserId: (bool) $this->option('clear-user-id'),
            enabled: $this->resolveEnabledOption(),
        );

        try {
            $changed = $service->update($account, $dto);
        } catch (\InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if (! $changed) {
            $this->components->warn('No changes requested.');

            return self::FAILURE;
        }

        $mapping = $service->columnMapping();

        $this->components->info("Updated WebDAV account '{$account->getAttribute($mapping->usernameColumn)}'.");
        $this->renderAccountSummary($account, $mapping);

        return self::SUCCESS;
    }

    /**
     * Validate mutually exclusive option combinations before delegating to the service.
     *
     * @return bool `true` when conflicting options were detected and reported to the console.
     */
    private function hasConflictingOptions(): bool
    {
        if ((bool) $this->option('enable') && (bool) $this->option('disable')) {
            $this->components->error('Use either --enable or --disable, not both.');

            return true;
        }

        if ($this->option('user-id') !== null && (bool) $this->option('clear-user-id')) {
            $this->components->error('Use either --user-id or --clear-user-id, not both.');

            return true;
        }

        if ($this->option('display-name') !== null && (bool) $this->option('clear-display-name')) {
            $this->components->error('Use either --display-name or --clear-display-name, not both.');

            return true;
        }

        return false;
    }

    /**
     * Resolve the nullable boolean enabled flag from the mutually exclusive --enable / --disable options.
     *
     * @return bool|null `true` for --enable, `false` for --disable, `null` when neither was passed.
     */
    private function resolveEnabledOption(): ?bool
    {
        if ((bool) $this->option('enable')) {
            return true;
        }

        if ((bool) $this->option('disable')) {
            return false;
        }

        return null;
    }
}
