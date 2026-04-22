<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\DTO\Server;

use N3XT0R\LaravelWebdavServer\DTO\Server\WebDavRequestContextDto;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpace;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use PHPUnit\Framework\TestCase;

final class WebDavRequestContextDtoTest extends TestCase
{
    public function test_it_stores_principal_space_key_and_space(): void
    {
        $principal = new WebDavPrincipal('42', 'Alice');
        $space = new WebDavStorageSpace('local', 'webdav/42');

        $dto = new WebDavRequestContextDto($principal, 'default', $space);

        $this->assertSame($principal, $dto->principal);
        $this->assertSame('default', $dto->spaceKey);
        $this->assertSame($space, $dto->space);
    }

    public function test_it_preserves_arbitrary_space_key(): void
    {
        $principal = new WebDavPrincipal('7', 'Bob');
        $space = new WebDavStorageSpace('s3', 'uploads/7');

        $dto = new WebDavRequestContextDto($principal, 'team-uploads', $space);

        $this->assertSame('team-uploads', $dto->spaceKey);
    }
}
