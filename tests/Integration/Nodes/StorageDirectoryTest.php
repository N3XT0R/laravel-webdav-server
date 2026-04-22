<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Nodes;

use Illuminate\Contracts\Filesystem\Filesystem;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use N3XT0R\LaravelWebdavServer\Nodes\StorageDirectory;
use N3XT0R\LaravelWebdavServer\Nodes\StorageFile;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;

final class StorageDirectoryTest extends TestCase
{
    private function makeDirectory(
        string $name,
        string $path,
        Filesystem $filesystem,
        PathAuthorizationInterface $authorization,
    ): StorageDirectory {
        $context = new StorageNodeContextDto(
            disk: 'local',
            filesystem: $filesystem,
            principal: new WebDavPrincipal('42', 'Alice'),
            authorization: $authorization,
        );

        return new StorageDirectory($name, $path, $context);
    }

    public function test_get_name_returns_directory_name(): void
    {
        $directory = $this->makeDirectory(
            'docs',
            'webdav/42/docs',
            $this->createMock(Filesystem::class),
            $this->createMock(PathAuthorizationInterface::class),
        );

        $this->assertSame('docs', $directory->getName());
    }

    public function test_get_children_calls_authorize_read_and_returns_nodes(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);
        $auth->expects($this->once())->method('authorizeRead');

        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->with('webdav/42')->willReturn(true);
        $fs->method('directories')->with('webdav/42')->willReturn(['webdav/42/sub']);
        $fs->method('files')->with('webdav/42')->willReturn(['webdav/42/file.txt']);

        $directory = $this->makeDirectory('42', 'webdav/42', $fs, $auth);

        $children = $directory->getChildren();

        $this->assertCount(2, $children);
        $this->assertInstanceOf(StorageDirectory::class, $children[0]);
        $this->assertInstanceOf(StorageFile::class, $children[1]);
        $this->assertSame('sub', $children[0]->getName());
        $this->assertSame('file.txt', $children[1]->getName());
    }

    public function test_get_children_returns_empty_array_when_directory_does_not_exist(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);

        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->with('webdav/42/empty')->willReturn(false);

        $directory = $this->makeDirectory('empty', 'webdav/42/empty', $fs, $auth);

        $this->assertSame([], $directory->getChildren());
    }

    public function test_get_child_returns_storage_file_for_a_file(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);

        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->willReturn(true);
        $fs->method('directories')->willReturn([]);

        $directory = $this->makeDirectory('42', 'webdav/42', $fs, $auth);
        $child = $directory->getChild('report.pdf');

        $this->assertInstanceOf(StorageFile::class, $child);
        $this->assertSame('report.pdf', $child->getName());
    }

    public function test_get_child_returns_storage_directory_for_a_subdirectory(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);

        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->willReturn(true);
        $fs->method('directories')->willReturn(['webdav/42/archive']);

        $directory = $this->makeDirectory('42', 'webdav/42', $fs, $auth);
        $child = $directory->getChild('archive');

        $this->assertInstanceOf(StorageDirectory::class, $child);
        $this->assertSame('archive', $child->getName());
    }

    public function test_get_child_throws_not_found_when_path_does_not_exist(): void
    {
        $this->expectException(NotFound::class);

        $auth = $this->createMock(PathAuthorizationInterface::class);

        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->willReturn(false);

        $directory = $this->makeDirectory('42', 'webdav/42', $fs, $auth);
        $directory->getChild('ghost.txt');
    }

    public function test_child_exists_returns_true_when_path_exists_and_auth_passes(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);

        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->willReturn(true);

        $directory = $this->makeDirectory('42', 'webdav/42', $fs, $auth);

        $this->assertTrue($directory->childExists('notes.txt'));
    }

    public function test_child_exists_returns_false_when_path_does_not_exist(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);

        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->willReturn(false);

        $directory = $this->makeDirectory('42', 'webdav/42', $fs, $auth);

        $this->assertFalse($directory->childExists('ghost.txt'));
    }

    public function test_child_exists_returns_false_when_authorization_throws(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);
        $auth->method('authorizeRead')->willThrowException(new Forbidden);

        $directory = $this->makeDirectory(
            '42', 'webdav/42',
            $this->createMock(Filesystem::class),
            $auth,
        );

        $this->assertFalse($directory->childExists('secret.txt'));
    }

    public function test_create_directory_calls_authorize_and_makes_directory(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);
        $auth->expects($this->once())->method('authorizeCreateDirectory');

        $fs = $this->createMock(Filesystem::class);
        $fs->expects($this->once())->method('makeDirectory')->with('webdav/42/newdir');

        $directory = $this->makeDirectory('42', 'webdav/42', $fs, $auth);
        $directory->createDirectory('newdir');
    }

    public function test_create_file_with_string_data_calls_authorize_and_puts_content(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);
        $auth->expects($this->once())->method('authorizeCreateFile');

        $fs = $this->createMock(Filesystem::class);
        $fs->expects($this->once())->method('put')->with('webdav/42/new.txt', 'hello');

        $directory = $this->makeDirectory('42', 'webdav/42', $fs, $auth);
        $directory->createFile('new.txt', 'hello');
    }

    public function test_create_file_with_resource_data_reads_stream_and_puts_content(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);
        $auth->expects($this->once())->method('authorizeCreateFile');

        $fs = $this->createMock(Filesystem::class);
        $fs->expects($this->once())->method('put')->with('webdav/42/new.txt', 'from stream');

        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'from stream');
        rewind($resource);

        $directory = $this->makeDirectory('42', 'webdav/42', $fs, $auth);
        $directory->createFile('new.txt', $resource);

        fclose($resource);
    }

    public function test_delete_calls_authorize_delete_and_removes_directory_recursively(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);
        $auth->expects($this->exactly(3))->method('authorizeDelete');

        $fs = $this->createMock(Filesystem::class);
        $fs->method('files')->willReturnMap([
            ['webdav/42/dir', ['webdav/42/dir/file.txt']],
            ['webdav/42/dir/sub', []],
        ]);
        $fs->method('directories')->willReturnMap([
            ['webdav/42/dir', ['webdav/42/dir/sub']],
            ['webdav/42/dir/sub', []],
        ]);
        $fs->expects($this->once())->method('delete')->with('webdav/42/dir/file.txt');
        $fs->expects($this->exactly(2))->method('deleteDirectory');

        $directory = $this->makeDirectory('dir', 'webdav/42/dir', $fs, $auth);
        $directory->delete();
    }
}
