<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Auth;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use N3XT0R\LaravelWebdavServer\Auth\Authorization\GatePathAuthorization;
use N3XT0R\LaravelWebdavServer\DTO\Auth\WebDavPathResourceDto;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use Sabre\DAV\Exception\Forbidden;

final class GatePathAuthorizationTest extends TestCase
{
    private function makeAuthorization(bool $allow): GatePathAuthorization
    {
        $response = $allow ? Response::allow() : Response::deny('Access denied.');

        $innerGate = $this->createMock(Gate::class);
        $innerGate->method('inspect')->willReturn($response);

        $gate = $this->createMock(Gate::class);
        $gate->method('forUser')->willReturn($innerGate);

        return new GatePathAuthorization($gate);
    }

    private function makeDenyingAuthorizationWithAbilityCheck(string $expectedAbility): GatePathAuthorization
    {
        $innerGate = $this->createMock(Gate::class);
        $innerGate->expects($this->once())
            ->method('inspect')
            ->with($expectedAbility, $this->isInstanceOf(WebDavPathResourceDto::class))
            ->willReturn(Response::deny('denied'));

        $gate = $this->createMock(Gate::class);
        $gate->method('forUser')->willReturn($innerGate);

        return new GatePathAuthorization($gate);
    }

    public function test_authorize_read_does_not_throw_when_allowed(): void
    {
        $principal = new WebDavPrincipal('42', 'Alice');
        $auth = $this->makeAuthorization(true);

        $auth->authorizeRead($principal, 'local', 'webdav/42');

        $this->assertTrue(true);
    }

    public function test_authorize_read_throws_forbidden_when_denied(): void
    {
        $this->expectException(Forbidden::class);

        $principal = new WebDavPrincipal('42', 'Alice');
        $auth = $this->makeAuthorization(false);

        $auth->authorizeRead($principal, 'local', 'webdav/42');
    }

    public function test_authorize_write_throws_forbidden_when_denied(): void
    {
        $this->expectException(Forbidden::class);

        $principal = new WebDavPrincipal('42', 'Alice');
        $auth = $this->makeAuthorization(false);

        $auth->authorizeWrite($principal, 'local', 'webdav/42/file.txt');
    }

    public function test_authorize_delete_throws_forbidden_when_denied(): void
    {
        $this->expectException(Forbidden::class);

        $principal = new WebDavPrincipal('42', 'Alice');
        $auth = $this->makeAuthorization(false);

        $auth->authorizeDelete($principal, 'local', 'webdav/42/file.txt');
    }

    public function test_authorize_create_directory_throws_forbidden_when_denied(): void
    {
        $this->expectException(Forbidden::class);

        $principal = new WebDavPrincipal('42', 'Alice');
        $auth = $this->makeAuthorization(false);

        $auth->authorizeCreateDirectory($principal, 'local', 'webdav/42/newdir');
    }

    public function test_authorize_create_file_throws_forbidden_when_denied(): void
    {
        $this->expectException(Forbidden::class);

        $principal = new WebDavPrincipal('42', 'Alice');
        $auth = $this->makeAuthorization(false);

        $auth->authorizeCreateFile($principal, 'local', 'webdav/42/newfile.txt');
    }

    public function test_authorize_read_calls_gate_with_read_ability(): void
    {
        $this->expectException(Forbidden::class);

        $principal = new WebDavPrincipal('42', 'Alice');
        $auth = $this->makeDenyingAuthorizationWithAbilityCheck('read');

        $auth->authorizeRead($principal, 'local', 'webdav/42');
    }

    public function test_authorize_write_calls_gate_with_write_ability(): void
    {
        $this->expectException(Forbidden::class);

        $principal = new WebDavPrincipal('42', 'Alice');
        $auth = $this->makeDenyingAuthorizationWithAbilityCheck('write');

        $auth->authorizeWrite($principal, 'local', 'webdav/42/file.txt');
    }

    public function test_authorize_delete_calls_gate_with_delete_ability(): void
    {
        $this->expectException(Forbidden::class);

        $principal = new WebDavPrincipal('42', 'Alice');
        $auth = $this->makeDenyingAuthorizationWithAbilityCheck('delete');

        $auth->authorizeDelete($principal, 'local', 'webdav/42/file.txt');
    }

    public function test_authorize_create_directory_calls_gate_with_correct_ability(): void
    {
        $this->expectException(Forbidden::class);

        $principal = new WebDavPrincipal('42', 'Alice');
        $auth = $this->makeDenyingAuthorizationWithAbilityCheck('createDirectory');

        $auth->authorizeCreateDirectory($principal, 'local', 'webdav/42/dir');
    }

    public function test_authorize_create_file_calls_gate_with_correct_ability(): void
    {
        $this->expectException(Forbidden::class);

        $principal = new WebDavPrincipal('42', 'Alice');
        $auth = $this->makeDenyingAuthorizationWithAbilityCheck('createFile');

        $auth->authorizeCreateFile($principal, 'local', 'webdav/42/file.txt');
    }
}
