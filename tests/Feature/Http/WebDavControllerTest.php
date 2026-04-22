<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Feature\Http;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerRunnerInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;
use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;
use N3XT0R\LaravelWebdavServer\Tests\DatabaseTestCase;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server\CapturingServerRunner;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use ReflectionProperty;
use Workbench\App\Models\User;

final class WebDavControllerTest extends DatabaseTestCase
{
    private string $diskRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diskRoot = sys_get_temp_dir().'/laravel-webdav-server-tests/'.str_replace('\\', '-', self::class);

        $this->app['config']->set('filesystems.disks.local.root', $this->diskRoot);
        $this->app->bind(ServerRunnerInterface::class, CapturingServerRunner::class);

        CapturingServerRunner::reset();
        (new Filesystem)->deleteDirectory($this->diskRoot);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->diskRoot);
        CapturingServerRunner::reset();

        parent::tearDown();
    }

    public function test_request_without_basic_auth_returns_401_and_www_authenticate_header(): void
    {
        $response = $this->get('/webdav/default');

        $response->assertUnauthorized();
        $response->assertHeader('WWW-Authenticate', 'Basic realm="WebDAV"');
    }

    public function test_request_with_malformed_basic_auth_header_throws_invalid_credentials_exception(): void
    {
        $this->withoutExceptionHandling();
        $this->expectException(InvalidCredentialsException::class);

        $this->call('PROPFIND', '/webdav/default', server: [
            'HTTP_AUTHORIZATION' => 'Basic Zm9v',
        ]);
    }

    public function test_request_with_invalid_credentials_throws_invalid_credentials_exception(): void
    {
        $user = User::factory()->create();
        WebDavAccountModel::factory()
            ->withUserName('alice')
            ->withPassword('secret')
            ->withUserId((int) $user->getKey())
            ->create([
                'display_name' => 'Alice',
            ]);

        $this->withoutExceptionHandling();
        $this->expectException(InvalidCredentialsException::class);

        $this->call('PROPFIND', '/webdav/default', server: [
            'PHP_AUTH_USER' => 'alice',
            'PHP_AUTH_PW' => 'wrong-password',
        ]);
    }

    public function test_request_with_valid_basic_auth_runs_the_real_pipeline(): void
    {
        $user = User::factory()->create([
            'name' => 'Alice',
        ]);
        WebDavAccountModel::factory()
            ->withUserName('alice')
            ->withPassword('secret')
            ->withUserId((int) $user->getKey())
            ->create([
                'display_name' => 'Alice',
            ]);

        Storage::disk('local')->makeDirectory('webdav/'.$user->getKey().'/documents');
        Storage::disk('local')->put('webdav/'.$user->getKey().'/readme.txt', 'hello');

        $response = $this->call('PROPFIND', '/webdav/default', server: [
            'PHP_AUTH_USER' => 'alice',
            'PHP_AUTH_PW' => 'secret',
        ]);

        $response->assertOk();
        $response->assertJsonPath('baseUri', '/webdav/default/');
        $response->assertJsonPath('rootClass', StorageRootCollection::class);
        $response->assertJsonPath('rootName', (string) $user->getKey());
        $response->assertJsonCount(2, 'children');

        $children = collect($response->json('children'))->sortBy('name')->values()->all();

        $this->assertSame([
            ['name' => 'documents', 'type' => 'directory'],
            ['name' => 'readme.txt', 'type' => 'file'],
        ], $children);

        $server = CapturingServerRunner::$lastServer;

        $this->assertNotNull($server);

        $root = $server->tree->getNodeForPath('');
        $context = $this->readProperty($root, 'context');
        $principal = $context->principal;

        $this->assertSame('local', $context->disk);
        $this->assertSame('webdav/'.$user->getKey(), $this->readProperty($root, 'path'));
        $this->assertInstanceOf(WebDavPrincipalValueObject::class, $principal);
        $this->assertSame((string) $user->getKey(), $principal->id);
        $this->assertSame('Alice', $principal->displayName);
        $this->assertSame($user->getKey(), $principal->user?->getAuthIdentifier());
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
