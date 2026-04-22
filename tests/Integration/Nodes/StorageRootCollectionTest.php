<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Nodes;

use Illuminate\Contracts\Filesystem\Filesystem;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use N3XT0R\LaravelWebdavServer\Nodes\StorageDirectory;
use N3XT0R\LaravelWebdavServer\Nodes\StorageFile;
use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use Sabre\DAV\Exception\NotFound;

final class StorageRootCollectionTest extends TestCase
{
    private function makeRoot(
        string $rootPath,
        Filesystem $filesystem,
        PathAuthorizationInterface $authorization,
        string $principalId = '42',
    ): StorageRootCollection {
        $context = new StorageNodeContextDto(
            disk: 'local',
            filesystem: $filesystem,
            principal: new WebDavPrincipal($principalId, 'Alice'),
            authorization: $authorization,
        );

        return new StorageRootCollection($principalId, $rootPath, $context);
    }

    public function test_get_name_returns_principal_id(): void
    {
        $root = $this->makeRoot(
            'webdav/42',
            $this->createMock(Filesystem::class),
            $this->createMock(PathAuthorizationInterface::class),
            '42',
        );

        $this->assertSame('42', $root->getName());
    }

    public function test_get_children_calls_authorize_read_and_returns_nodes(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);
        $auth->expects($this->once())->method('authorizeRead');

        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->with('webdav/42')->willReturn(true);
        $fs->method('directories')->with('webdav/42')->willReturn(['webdav/42/docs']);
        $fs->method('files')->with('webdav/42')->willReturn(['webdav/42/readme.txt']);

        $root = $this->makeRoot('webdav/42', $fs, $auth);
        $children = $root->getChildren();

        $this->assertCount(2, $children);
        $this->assertInstanceOf(StorageDirectory::class, $children[0]);
        $this->assertInstanceOf(StorageFile::class, $children[1]);
    }

    public function test_get_children_returns_empty_array_when_root_does_not_exist(): void
    {
        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->willReturn(false);

        $root = $this->makeRoot('webdav/42', $fs, $this->createMock(PathAuthorizationInterface::class));

        $this->assertSame([], $root->getChildren());
    }

    public function test_get_child_returns_file_node(): void
    {
        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->willReturn(true);
        $fs->method('directories')->willReturn([]);

        $root = $this->makeRoot('webdav/42', $fs, $this->createMock(PathAuthorizationInterface::class));
        $child = $root->getChild('notes.md');

        $this->assertInstanceOf(StorageFile::class, $child);
        $this->assertSame('notes.md', $child->getName());
    }

    public function test_get_child_returns_directory_node(): void
    {
        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->willReturn(true);
        $fs->method('directories')->willReturn(['webdav/42/photos']);

        $root = $this->makeRoot('webdav/42', $fs, $this->createMock(PathAuthorizationInterface::class));
        $child = $root->getChild('photos');

        $this->assertInstanceOf(StorageDirectory::class, $child);
        $this->assertSame('photos', $child->getName());
    }

    public function test_get_child_throws_not_found_when_path_is_missing(): void
    {
        $this->expectException(NotFound::class);

        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->willReturn(false);

        $root = $this->makeRoot('webdav/42', $fs, $this->createMock(PathAuthorizationInterface::class));
        $root->getChild('missing.txt');
    }

    public function test_child_exists_returns_true_for_existing_path(): void
    {
        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->willReturn(true);

        $root = $this->makeRoot('webdav/42', $fs, $this->createMock(PathAuthorizationInterface::class));

        $this->assertTrue($root->childExists('file.txt'));
    }

    public function test_child_exists_returns_false_for_missing_path(): void
    {
        $fs = $this->createMock(Filesystem::class);
        $fs->method('exists')->willReturn(false);

        $root = $this->makeRoot('webdav/42', $fs, $this->createMock(PathAuthorizationInterface::class));

        $this->assertFalse($root->childExists('ghost.txt'));
    }

    public function test_child_exists_returns_false_when_authorization_throws(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);
        $auth->method('authorizeRead')->willThrowException(new \Sabre\DAV\Exception\Forbidden);

        $root = $this->makeRoot('webdav/42', $this->createMock(Filesystem::class), $auth);

        $this->assertFalse($root->childExists('secret.txt'));
    }

    public function test_create_directory_authorizes_and_makes_directory(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);
        $auth->expects($this->once())->method('authorizeCreateDirectory');

        $fs = $this->createMock(Filesystem::class);
        $fs->expects($this->once())->method('makeDirectory')->with('webdav/42/backups');

        $root = $this->makeRoot('webdav/42', $fs, $auth);
        $root->createDirectory('backups');
    }

    public function test_create_file_with_string_data_authorizes_and_puts_content(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);
        $auth->expects($this->once())->method('authorizeCreateFile');

        $fs = $this->createMock(Filesystem::class);
        $fs->expects($this->once())->method('put')->with('webdav/42/upload.txt', 'data');

        $root = $this->makeRoot('webdav/42', $fs, $auth);
        $root->createFile('upload.txt', 'data');
    }

    public function test_create_file_with_null_data_puts_empty_string(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);

        $fs = $this->createMock(Filesystem::class);
        $fs->expects($this->once())->method('put')->with('webdav/42/empty.txt', '');

        $root = $this->makeRoot('webdav/42', $fs, $auth);
        $root->createFile('empty.txt', null);
    }
}
