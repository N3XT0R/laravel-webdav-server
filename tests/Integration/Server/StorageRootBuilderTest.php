<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Server;

use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Illuminate\Contracts\Filesystem\Filesystem;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;
use N3XT0R\LaravelWebdavServer\Server\Storage\StorageRootBuilder;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpace;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final class StorageRootBuilderTest extends TestCase
{
    public function test_it_returns_a_storage_root_collection(): void
    {
        $principal = new WebDavPrincipal('42', 'Alice');
        $space = new WebDavStorageSpace('local', 'webdav/42');

        $filesystem = $this->createMock(Filesystem::class);

        $filesystemManager = $this->createMock(FilesystemManager::class);
        $filesystemManager->expects($this->once())
            ->method('disk')
            ->with('local')
            ->willReturn($filesystem);

        $authorization = $this->createMock(PathAuthorizationInterface::class);

        $builder = new StorageRootBuilder($authorization, $filesystemManager);
        $result = $builder->build($principal, $space);

        $this->assertInstanceOf(StorageRootCollection::class, $result);
    }

    public function test_it_uses_principal_id_as_the_collection_name(): void
    {
        $principal = new WebDavPrincipal('99', 'Bob');
        $space = new WebDavStorageSpace('local', 'webdav/99');

        $filesystemManager = $this->createMock(FilesystemManager::class);
        $filesystemManager->method('disk')->willReturn($this->createMock(Filesystem::class));

        $builder = new StorageRootBuilder(
            $this->createMock(PathAuthorizationInterface::class),
            $filesystemManager,
        );

        $result = $builder->build($principal, $space);

        $this->assertSame('99', $result->getName());
    }

    public function test_it_resolves_the_disk_from_the_storage_space(): void
    {
        $principal = new WebDavPrincipal('42', 'Alice');
        $space = new WebDavStorageSpace('s3', 'bucket/42');

        $filesystem = $this->createMock(Filesystem::class);

        $filesystemManager = $this->createMock(FilesystemManager::class);
        $filesystemManager->expects($this->once())
            ->method('disk')
            ->with('s3')
            ->willReturn($filesystem);

        $builder = new StorageRootBuilder(
            $this->createMock(PathAuthorizationInterface::class),
            $filesystemManager,
        );

        $builder->build($principal, $space);
    }
}
