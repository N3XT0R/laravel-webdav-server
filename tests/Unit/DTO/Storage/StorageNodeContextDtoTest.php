<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\DTO\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use PHPUnit\Framework\TestCase;

final class StorageNodeContextDtoTest extends TestCase
{
    public function test_it_stores_all_properties(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $principal = new WebDavPrincipal('42', 'Alice');
        $authorization = $this->createMock(PathAuthorizationInterface::class);

        $dto = new StorageNodeContextDto(
            disk: 'local',
            filesystem: $filesystem,
            principal: $principal,
            authorization: $authorization,
        );

        $this->assertSame('local', $dto->disk);
        $this->assertSame($filesystem, $dto->filesystem);
        $this->assertSame($principal, $dto->principal);
        $this->assertSame($authorization, $dto->authorization);
    }
}
