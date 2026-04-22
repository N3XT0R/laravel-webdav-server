<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\DTO\Auth;

use N3XT0R\LaravelWebdavServer\DTO\Auth\WebDavPathResourceDto;
use PHPUnit\Framework\TestCase;

final class WebDavPathResourceDtoTest extends TestCase
{
    public function test_it_stores_disk_and_path(): void
    {
        $dto = new WebDavPathResourceDto('local', 'webdav/42/file.txt');

        $this->assertSame('local', $dto->disk);
        $this->assertSame('webdav/42/file.txt', $dto->path);
    }

    public function test_it_stores_s3_disk(): void
    {
        $dto = new WebDavPathResourceDto('s3', 'uploads/42/doc.pdf');

        $this->assertSame('s3', $dto->disk);
        $this->assertSame('uploads/42/doc.pdf', $dto->path);
    }
}
