<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\DTO\Storage;

use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Auth\AllowAllPathAuthorization;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final class StorageNodeContextDtoTest extends TestCase
{
    public function test_it_stores_all_properties(): void
    {
        $filesystem = app('filesystem')->disk('local');
        $principal = new WebDavPrincipal('42', 'Alice');
        $authorization = new AllowAllPathAuthorization();

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
