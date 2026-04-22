<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Storage\Data;

use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject;
use PHPUnit\Framework\TestCase;

final class WebDavStorageSpaceValueObjectTest extends TestCase
{
    public function test_it_stores_disk_and_root_path(): void
    {
        $space = new WebDavStorageSpaceValueObject('local', 'webdav/42');

        $this->assertSame('local', $space->disk);
        $this->assertSame('webdav/42', $space->rootPath);
    }

    public function test_it_stores_s3_disk_with_nested_path(): void
    {
        $space = new WebDavStorageSpaceValueObject('s3', 'bucket/prefix/42');

        $this->assertSame('s3', $space->disk);
        $this->assertSame('bucket/prefix/42', $space->rootPath);
    }
}
