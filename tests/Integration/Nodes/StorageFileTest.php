<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Nodes;

use Illuminate\Contracts\Filesystem\Filesystem;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use N3XT0R\LaravelWebdavServer\Nodes\StorageFile;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final class StorageFileTest extends TestCase
{
    private function makeFile(
        string $name,
        string $path,
        Filesystem $filesystem,
        PathAuthorizationInterface $authorization,
    ): StorageFile {
        $principal = new WebDavPrincipal('42', 'Alice');

        $context = new StorageNodeContextDto(
            disk: 'local',
            filesystem: $filesystem,
            principal: $principal,
            authorization: $authorization,
        );

        return new StorageFile($name, $path, $context);
    }

    public function test_get_name_returns_the_filename(): void
    {
        $file = $this->makeFile(
            'document.pdf',
            'webdav/42/document.pdf',
            $this->createMock(Filesystem::class),
            $this->createMock(PathAuthorizationInterface::class),
        );

        $this->assertSame('document.pdf', $file->getName());
    }

    public function test_get_calls_authorize_read_before_returning_content(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);
        $auth->expects($this->once())->method('authorizeRead');

        $fs = $this->createMock(Filesystem::class);
        $fs->method('get')->with('webdav/42/file.txt')->willReturn('hello');

        $file = $this->makeFile('file.txt', 'webdav/42/file.txt', $fs, $auth);

        $this->assertSame('hello', $file->get());
    }

    public function test_put_with_string_calls_authorize_write(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);
        $auth->expects($this->once())->method('authorizeWrite');

        $fs = $this->createMock(Filesystem::class);
        $fs->expects($this->once())->method('put')->with('webdav/42/file.txt', 'content');

        $file = $this->makeFile('file.txt', 'webdav/42/file.txt', $fs, $auth);
        $file->put('content');
    }

    public function test_put_with_resource_reads_stream_and_calls_authorize_write(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);
        $auth->expects($this->once())->method('authorizeWrite');

        $fs = $this->createMock(Filesystem::class);
        $fs->expects($this->once())->method('put')->with('webdav/42/file.txt', 'streamed content');

        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'streamed content');
        rewind($resource);

        $file = $this->makeFile('file.txt', 'webdav/42/file.txt', $fs, $auth);
        $file->put($resource);

        fclose($resource);
    }

    public function test_delete_calls_authorize_delete(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);
        $auth->expects($this->once())->method('authorizeDelete');

        $fs = $this->createMock(Filesystem::class);
        $fs->expects($this->once())->method('delete')->with('webdav/42/file.txt');

        $file = $this->makeFile('file.txt', 'webdav/42/file.txt', $fs, $auth);
        $file->delete();
    }

    public function test_get_size_calls_authorize_read_and_returns_size(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);
        $auth->expects($this->once())->method('authorizeRead');

        $fs = $this->createMock(Filesystem::class);
        $fs->method('size')->with('webdav/42/file.txt')->willReturn(1024);

        $file = $this->makeFile('file.txt', 'webdav/42/file.txt', $fs, $auth);

        $this->assertSame(1024, $file->getSize());
    }

    public function test_get_last_modified_calls_authorize_read_and_returns_timestamp(): void
    {
        $auth = $this->createMock(PathAuthorizationInterface::class);
        $auth->expects($this->once())->method('authorizeRead');

        $fs = $this->createMock(Filesystem::class);
        $fs->method('lastModified')->with('webdav/42/file.txt')->willReturn(1700000000);

        $file = $this->makeFile('file.txt', 'webdav/42/file.txt', $fs, $auth);

        $this->assertSame(1700000000, $file->getLastModified());
    }
}
