<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\DTO\Server;

use N3XT0R\LaravelWebdavServer\DTO\Server\RequestContextDto;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use PHPUnit\Framework\TestCase;

final class RequestContextDtoTest extends TestCase
{
    public function test_it_stores_principal_space_key_and_space(): void
    {
        $principal = new WebDavPrincipalValueObject('42', 'Alice');
        $space = new WebDavStorageSpaceValueObject('local', 'webdav/42');

        $dto = new RequestContextDto($principal, 'default', $space);

        $this->assertSame($principal, $dto->principal);
        $this->assertSame('default', $dto->spaceKey);
        $this->assertSame($space, $dto->space);
    }

    public function test_it_preserves_arbitrary_space_key(): void
    {
        $principal = new WebDavPrincipalValueObject('7', 'Bob');
        $space = new WebDavStorageSpaceValueObject('s3', 'uploads/7');

        $dto = new RequestContextDto($principal, 'team-uploads', $space);

        $this->assertSame('team-uploads', $dto->spaceKey);
    }
}
