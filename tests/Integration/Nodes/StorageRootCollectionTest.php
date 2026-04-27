<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Nodes;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use N3XT0R\LaravelWebdavServer\Events\WebDav\DirectoryCreatedEvent;
use N3XT0R\LaravelWebdavServer\Events\WebDav\FileCreatedEvent;
use N3XT0R\LaravelWebdavServer\Nodes\StorageDirectory;
use N3XT0R\LaravelWebdavServer\Nodes\StorageFile;
use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Auth\AllowAllPathAuthorization;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Auth\DenyReadPathAuthorization;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use Sabre\DAV\Exception\NotFound;

final class StorageRootCollectionTest extends TestCase
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

    private function makeRoot(string $rootPath, AllowAllPathAuthorization $authorization): StorageRootCollection
    {
        return new StorageRootCollection(
            '42',
            $rootPath,
            new StorageNodeContextDto(
                disk: 'local',
                filesystem: Storage::disk('local'),
                principal: new WebDavPrincipalValueObject('42', 'Alice'),
                authorization: $authorization,
            ),
        );
    }

    public function test_get_name_returns_principal_id(): void
    {
        $this->assertSame('42', $this->makeRoot('webdav/42', new AllowAllPathAuthorization)->getName());
    }

    public function test_get_children_returns_nodes_from_real_filesystem(): void
    {
        Storage::disk('local')->makeDirectory('webdav/42/docs');
        Storage::disk('local')->put('webdav/42/readme.txt', 'hello');
        $authorization = new AllowAllPathAuthorization;

        $children = $this->makeRoot('webdav/42', $authorization)->getChildren();

        $this->assertCount(2, $children);
        $this->assertInstanceOf(StorageDirectory::class, $children[0]);
        $this->assertInstanceOf(StorageFile::class, $children[1]);
        $this->assertSame('read', $authorization->calls[0]['ability']);
        $this->assertSame('webdav/42', $authorization->calls[0]['path']);
    }

    public function test_get_children_returns_empty_array_when_root_does_not_exist(): void
    {
        $this->assertSame([], $this->makeRoot('webdav/42', new AllowAllPathAuthorization)->getChildren());
    }

    public function test_get_child_returns_file_node(): void
    {
        Storage::disk('local')->put('webdav/42/notes.md', 'note');

        $child = $this->makeRoot('webdav/42', new AllowAllPathAuthorization)->getChild('notes.md');

        $this->assertInstanceOf(StorageFile::class, $child);
        $this->assertSame('notes.md', $child->getName());
    }

    public function test_get_child_returns_directory_node(): void
    {
        Storage::disk('local')->makeDirectory('webdav/42/photos');

        $child = $this->makeRoot('webdav/42', new AllowAllPathAuthorization)->getChild('photos');

        $this->assertInstanceOf(StorageDirectory::class, $child);
        $this->assertSame('photos', $child->getName());
    }

    public function test_get_child_throws_not_found_when_path_is_missing(): void
    {
        $this->expectException(NotFound::class);

        $this->makeRoot('webdav/42', new AllowAllPathAuthorization)->getChild('missing.txt');
    }

    public function test_child_exists_returns_true_for_existing_path(): void
    {
        Storage::disk('local')->put('webdav/42/file.txt', 'hello');

        $this->assertTrue($this->makeRoot('webdav/42', new AllowAllPathAuthorization)->childExists('file.txt'));
    }

    public function test_child_exists_returns_false_for_missing_path(): void
    {
        $this->assertFalse($this->makeRoot('webdav/42', new AllowAllPathAuthorization)->childExists('ghost.txt'));
    }

    public function test_child_exists_returns_false_when_authorization_throws(): void
    {
        Storage::disk('local')->put('webdav/42/secret.txt', 'secret');

        $this->assertFalse($this->makeRoot('webdav/42', new DenyReadPathAuthorization)->childExists('secret.txt'));
    }

    public function test_create_directory_authorizes_and_makes_directory(): void
    {
        Event::fake();
        $authorization = new AllowAllPathAuthorization;
        $root = $this->makeRoot('webdav/42', $authorization);

        $root->createDirectory('backups');

        $this->assertTrue(Storage::disk('local')->exists('webdav/42/backups'));
        $this->assertSame('createDirectory', $authorization->calls[0]['ability']);
        $this->assertSame('webdav/42/backups', $authorization->calls[0]['path']);
        Event::assertDispatched(DirectoryCreatedEvent::class, function (DirectoryCreatedEvent $event): bool {
            return $event->path === 'webdav/42/backups'
                && $event->principal->id === '42';
        });
    }

    public function test_create_file_with_string_data_authorizes_and_puts_content(): void
    {
        Event::fake();
        $authorization = new AllowAllPathAuthorization;
        $root = $this->makeRoot('webdav/42', $authorization);

        $root->createFile('upload.txt', 'data');

        $this->assertSame('data', Storage::disk('local')->get('webdav/42/upload.txt'));
        $this->assertSame('createFile', $authorization->calls[0]['ability']);
        Event::assertDispatched(FileCreatedEvent::class, function (FileCreatedEvent $event): bool {
            return $event->path === 'webdav/42/upload.txt'
                && $event->bytes === 4;
        });
    }

    public function test_create_file_with_null_data_puts_empty_string(): void
    {
        $root = $this->makeRoot('webdav/42', new AllowAllPathAuthorization);

        $root->createFile('empty.txt', null);

        $this->assertSame('', Storage::disk('local')->get('webdav/42/empty.txt'));
    }
}
