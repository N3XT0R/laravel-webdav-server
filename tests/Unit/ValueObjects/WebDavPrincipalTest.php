<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\ValueObjects;

use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use PHPUnit\Framework\TestCase;
use Workbench\App\Models\User;

final class WebDavPrincipalTest extends TestCase
{
    public function test_it_stores_id_and_display_name(): void
    {
        $principal = new WebDavPrincipal('42', 'Alice');

        $this->assertSame('42', $principal->id);
        $this->assertSame('Alice', $principal->displayName);
    }

    public function test_user_defaults_to_null(): void
    {
        $principal = new WebDavPrincipal('42', 'Alice');

        $this->assertNull($principal->user);
    }

    public function test_it_stores_user_when_provided(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Alice',
            'email' => 'alice@example.test',
            'password' => 'secret',
        ]);
        $user->setAttribute($user->getKeyName(), 42);

        $principal = new WebDavPrincipal('42', 'Alice', $user);

        $this->assertSame($user, $principal->user);
    }

    public function test_get_principal_uri_prefixes_with_principals(): void
    {
        $principal = new WebDavPrincipal('42', 'Alice');

        $this->assertSame('principals/42', $principal->getPrincipalUri());
    }

    public function test_get_principal_uri_uses_the_stored_id(): void
    {
        $principal = new WebDavPrincipal('team-a/user-7', 'Bob');

        $this->assertSame('principals/team-a/user-7', $principal->getPrincipalUri());
    }
}
