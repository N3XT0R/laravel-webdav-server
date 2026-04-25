<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\DTO\Management;

final readonly class AccountUpdateDto
{
    /**
     * @param  string|null  $newUsername  Replace the current username, or `null` to leave unchanged.
     * @param  string|null  $password  Replace the stored password hash, or `null` to leave unchanged.
     * @param  string|null  $displayName  Replace the stored display name, or `null` to leave unchanged.
     * @param  bool  $clearDisplayName  Set the display name to `null`; takes precedence over `$displayName`.
     * @param  mixed  $userId  Replace the linked Laravel user identifier, or `null` to leave unchanged.
     * @param  bool  $clearUserId  Set the linked user identifier to `null`; takes precedence over `$userId`.
     * @param  bool|null  $enabled  Set the enabled flag, or `null` to leave unchanged.
     */
    public function __construct(
        public ?string $newUsername = null,
        public ?string $password = null,
        public ?string $displayName = null,
        public bool $clearDisplayName = false,
        public mixed $userId = null,
        public bool $clearUserId = false,
        public ?bool $enabled = null,
    ) {}
}
