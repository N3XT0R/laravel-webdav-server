<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Commands;

use N3XT0R\LaravelWebdavServer\Services\AccountManagementService;

final class CreateWebDavAccountCommand extends AccountCommand
{
    protected $signature = 'laravel-webdav-server:account:create
        {username : Username used for HTTP Basic Auth.}
        {secret : Plain-text credential that will be hashed before storage.}
        {--display-name= : Optional principal display name shown to WebDAV clients.}
        {--user-id= : Optional linked Laravel user identifier.}
        {--disabled : Create the account in a disabled state.}';

    protected $description = 'Create a WebDAV account record in the configured account model.';

    /**
     * Create a new WebDAV account in the configured account model.
     *
     * @param  AccountManagementService  $service  Service that handles account creation business logic.
     * @return int Symfony-compatible command exit code.
     */
    public function handle(AccountManagementService $service): int
    {
        try {
            $account = $service->create(
                username: (string) $this->argument('username'),
                password: (string) $this->argument('secret'),
                displayName: $this->option('display-name'),
                userId: $this->option('user-id'),
                enabled: ! (bool) $this->option('disabled'),
            );
        } catch (\InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Created WebDAV account '{$this->argument('username')}'.");
        $this->renderAccountSummary($account, $service->columnMapping());

        return self::SUCCESS;
    }
}
