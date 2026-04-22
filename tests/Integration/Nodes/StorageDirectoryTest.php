<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Nodes;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use N3XT0R\LaravelWebdavServer\Nodes\StorageDirectory;
use N3XT0R\LaravelWebdavServer\Nodes\StorageFile;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Auth\AllowAllPathAuthorization;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Auth\DenyReadPathAuthorization;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use Sabre\DAV\Exception\NotFound;

final class StorageDirectoryTest extends TestCase
{
    private string $diskRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diskRoot = sys_get_temp_dir().'/laravel-webdav-server-tests/'.str_replace('\\', '-', self::class);
        $this->app['config']->set('filesystems.disks.local.root', $this->diskRoot);

        (new Filesystem)->deleteDirectory($this->diskRoot);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->diskRoot);

        parent::tearDown();
    }

    private function makeDirectory(string $name, string $path, AllowAllPathAuthorization $authorization): StorageDirectory
    {
        return new StorageDirectory(
            $name,
            $path,
            new StorageNodeContextDto(
                disk: 'local',
                filesystem: Storage::disk('local'),
                principal: new WebDavPrincipalValueObject('42', 'Alice'),
                authorization: $authorization,
            ),
        );
    }

    public function test_get_name_returns_directory_name(): void
    {
        $this->assertSame('docs', $this->makeDirectory('docs', 'webdav/42/docs', new AllowAllPathAuthorization)->getName());
    }

    public function test_get_children_returns_nodes_from_real_filesystem(): void
    {
        Storage::disk('local')->makeDirectory('webdav/42/sub');
        Storage::disk('local')->put('webdav/42/file.txt', 'hello');
        $authorization = new AllowAllPathAuthorization;

        $children = $this->makeDirectory('42', 'webdav/42', $authorization)->getChildren();

        $this->assertCount(2, $children);
        $this->assertInstanceOf(StorageDirectory::class, $children[0]);
        $this->assertInstanceOf(StorageFile::class, $children[1]);
        $this->assertSame('sub', $children[0]->getName());
        $this->assertSame('file.txt', $children[1]->getName());
        $this->assertSame('read', $authorization->calls[0]['ability']);
    }

    public function test_get_children_returns_empty_array_when_directory_does_not_exist(): void
    {
        $this->assertSame([], $this->makeDirectory('empty', 'webdav/42/empty', new AllowAllPathAuthorization)->getChildren());
    }

    public function test_get_child_returns_storage_file_for_a_file(): void
    {
        Storage::disk('local')->put('webdav/42/report.pdf', 'pdf');

        $child = $this->makeDirectory('42', 'webdav/42', new AllowAllPathAuthorization)->getChild('report.pdf');

        $this->assertInstanceOf(StorageFile::class, $child);
        $this->assertSame('report.pdf', $child->getName());
    }

    public function test_get_child_returns_storage_directory_for_a_subdirectory(): void
    {
        Storage::disk('local')->makeDirectory('webdav/42/archive');

        $child = $this->makeDirectory('42', 'webdav/42', new AllowAllPathAuthorization)->getChild('archive');

        $this->assertInstanceOf(StorageDirectory::class, $child);
        $this->assertSame('archive', $child->getName());
    }

    public function test_get_child_throws_not_found_when_path_does_not_exist(): void
    {
        $this->expectException(NotFound::class);

        $this->makeDirectory('42', 'webdav/42', new AllowAllPathAuthorization)->getChild('ghost.txt');
    }

    public function test_child_exists_returns_true_when_path_exists_and_auth_passes(): void
    {
        Storage::disk('local')->put('webdav/42/notes.txt', 'note');

        $this->assertTrue($this->makeDirectory('42', 'webdav/42', new AllowAllPathAuthorization)->childExists('notes.txt'));
    }

    public function test_child_exists_returns_false_when_path_does_not_exist(): void
    {
        $this->assertFalse($this->makeDirectory('42', 'webdav/42', new AllowAllPathAuthorization)->childExists('ghost.txt'));
    }

    public function test_child_exists_returns_false_when_authorization_throws(): void
    {
        Storage::disk('local')->put('webdav/42/secret.txt', 'secret');

        $this->assertFalse($this->makeDirectory('42', 'webdav/42', new DenyReadPathAuthorization)->childExists('secret.txt'));
    }

    public function test_create_directory_calls_authorize_and_makes_directory(): void
    {
        $authorization = new AllowAllPathAuthorization;
        $directory = $this->makeDirectory('42', 'webdav/42', $authorization);

        $directory->createDirectory('newdir');

        $this->assertTrue(Storage::disk('local')->exists('webdav/42/newdir'));
        $this->assertSame('createDirectory', $authorization->calls[0]['ability']);
    }

    public function test_create_file_with_string_data_calls_authorize_and_puts_content(): void
    {
        $authorization = new AllowAllPathAuthorization;
        $directory = $this->makeDirectory('42', 'webdav/42', $authorization);

        $directory->createFile('new.txt', 'hello');

        $this->assertSame('hello', Storage::disk('local')->get('webdav/42/new.txt'));
        $this->assertSame('createFile', $authorization->calls[0]['ability']);
    }

    public function test_create_file_with_resource_data_reads_stream_and_puts_content(): void
    {
        $authorization = new AllowAllPathAuthorization;
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'from stream');
        rewind($resource);

        $this->makeDirectory('42', 'webdav/42', $authorization)->createFile('new.txt', $resource);

        fclose($resource);

        $this->assertSame('from stream', Storage::disk('local')->get('webdav/42/new.txt'));
    }

    public function test_delete_calls_authorize_delete_and_removes_directory_recursively(): void
    {
        Storage::disk('local')->makeDirectory('webdav/42/dir/sub');
        Storage::disk('local')->put('webdav/42/dir/file.txt', 'hello');
        $authorization = new AllowAllPathAuthorization;

        $this->makeDirectory('dir', 'webdav/42/dir', $authorization)->delete();

        $this->assertFalse(Storage::disk('local')->exists('webdav/42/dir'));
        $this->assertCount(3, array_filter(
            $authorization->calls,
            static fn (array $call): bool => $call['ability'] === 'delete'
        ));
    }
}
