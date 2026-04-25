<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Auth;

use Illuminate\Auth\Access\Gate as IlluminateGate;
use N3XT0R\LaravelWebdavServer\Auth\Authorization\GatePathAuthorization;
use N3XT0R\LaravelWebdavServer\DTO\Auth\PathResourceDto;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use Sabre\DAV\Exception\Forbidden;
use Workbench\App\Models\User;

final class GatePathAuthorizationTest extends TestCase
{
    /** @var list<array{ability:string,disk:string,path:string,userId:int|string|null}> */
    private array $inspections = [];

    private IlluminateGate $gate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gate = new IlluminateGate($this->app, static fn (): null => null);

        foreach (['read', 'write', 'delete', 'createDirectory', 'createFile'] as $ability) {
            $this->gate->define($ability, function (User $user, PathResourceDto $resource) use ($ability): bool {
                $this->inspections[] = [
                    'ability' => $ability,
                    'disk' => $resource->disk,
                    'path' => $resource->path,
                    'userId' => $user->getAuthIdentifier(),
                ];

                return str_contains($resource->path, 'allowed');
            });
        }
    }

    public function test_authorize_read_does_not_throw_when_allowed(): void
    {
        $principal = $this->makePrincipal(42);

        (new GatePathAuthorization($this->gate))->authorizeRead($principal, 'local', 'webdav/42/allowed.txt');

        $this->assertSame([[
            'ability' => 'read',
            'disk' => 'local',
            'path' => 'webdav/42/allowed.txt',
            'userId' => 42,
        ]], $this->inspections);
    }

    public function test_authorize_read_throws_forbidden_when_denied(): void
    {
        $this->expectException(Forbidden::class);

        (new GatePathAuthorization($this->gate))->authorizeRead(
            $this->makePrincipal(42),
            'local',
            'webdav/42/denied.txt',
        );
    }

    public function test_authorize_write_calls_gate_with_write_ability(): void
    {
        $this->expectException(Forbidden::class);

        (new GatePathAuthorization($this->gate))->authorizeWrite(
            $this->makePrincipal(42),
            'local',
            'webdav/42/denied.txt',
        );
    }

    public function test_authorize_delete_calls_gate_with_delete_ability(): void
    {
        $this->expectException(Forbidden::class);

        (new GatePathAuthorization($this->gate))->authorizeDelete(
            $this->makePrincipal(42),
            'local',
            'webdav/42/denied.txt',
        );
    }

    public function test_authorize_create_directory_calls_gate_with_correct_ability(): void
    {
        $this->expectException(Forbidden::class);

        (new GatePathAuthorization($this->gate))->authorizeCreateDirectory(
            $this->makePrincipal(42),
            'local',
            'webdav/42/denied-dir',
        );
    }

    public function test_authorize_create_file_calls_gate_with_correct_ability(): void
    {
        $this->expectException(Forbidden::class);

        (new GatePathAuthorization($this->gate))->authorizeCreateFile(
            $this->makePrincipal(42),
            'local',
            'webdav/42/denied-file.txt',
        );
    }

    private function makePrincipal(int $id): WebDavPrincipalValueObject
    {
        $user = new User;
        $user->setRawAttributes([
            'name' => 'Alice',
            'email' => 'alice@example.test',
            'password' => 'secret',
            $user->getKeyName() => $id,
        ], true);

        return new WebDavPrincipalValueObject((string) $id, 'Alice', $user);
    }
}
