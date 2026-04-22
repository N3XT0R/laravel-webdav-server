<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Server;

use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;
use N3XT0R\LaravelWebdavServer\Server\Storage\StorageRootBuilder;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpace;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Auth\AllowAllPathAuthorization;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Filesystem\RecordingFilesystemManager;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use ReflectionProperty;

final class StorageRootBuilderTest extends TestCase
{
    public function test_it_returns_a_storage_root_collection(): void
    {
        $principal = new WebDavPrincipal('42', 'Alice');
        $space = new WebDavStorageSpace('local', 'webdav/42');
        $filesystem = app('filesystem')->disk('local');
        $manager = new RecordingFilesystemManager($filesystem);
        $authorization = new AllowAllPathAuthorization;

        $builder = new StorageRootBuilder($authorization, $manager);
        $result = $builder->build($principal, $space);

        $this->assertInstanceOf(StorageRootCollection::class, $result);
        $this->assertSame(['local'], $manager->requestedDisks);
    }

    public function test_it_uses_principal_id_as_the_collection_name(): void
    {
        $principal = new WebDavPrincipal('99', 'Bob');
        $space = new WebDavStorageSpace('local', 'webdav/99');
        $builder = new StorageRootBuilder(
            new AllowAllPathAuthorization,
            new RecordingFilesystemManager(app('filesystem')->disk('local')),
        );

        $result = $builder->build($principal, $space);

        $this->assertSame('99', $result->getName());
    }

    public function test_it_resolves_the_disk_from_the_storage_space(): void
    {
        $principal = new WebDavPrincipal('42', 'Alice');
        $space = new WebDavStorageSpace('archive', 'bucket/42');
        $manager = new RecordingFilesystemManager(app('filesystem')->disk('local'));

        $builder = new StorageRootBuilder(new AllowAllPathAuthorization, $manager);
        $root = $builder->build($principal, $space);
        $context = $this->readProperty($root, 'context');

        $this->assertSame(['archive'], $manager->requestedDisks);
        $this->assertSame('archive', $context->disk);
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
