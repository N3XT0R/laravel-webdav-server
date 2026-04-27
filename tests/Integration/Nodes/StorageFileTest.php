<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Nodes;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use N3XT0R\LaravelWebdavServer\Events\WebDavFileDeletedEvent;
use N3XT0R\LaravelWebdavServer\Events\WebDavFileUpdatedEvent;
use N3XT0R\LaravelWebdavServer\Exception\Storage\StreamReadException;
use N3XT0R\LaravelWebdavServer\Nodes\StorageFile;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Auth\AllowAllPathAuthorization;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Nodes\StreamGetContentsOverrideBootstrap;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final class StorageFileTest extends TestCase
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
        StreamGetContentsOverrideBootstrap::reset();
        (new Filesystem)->deleteDirectory($this->diskRoot);

        parent::tearDown();
    }

    private function makeFile(string $name, string $path, AllowAllPathAuthorization $authorization): StorageFile
    {
        return new StorageFile(
            $name,
            $path,
            new StorageNodeContextDto(
                disk: 'local',
                filesystem: Storage::disk('local'),
                principal: new WebDavPrincipalValueObject('42', 'Alice'),
                authorization: $authorization,
            ),
        );
    }

    public function test_get_name_returns_the_filename(): void
    {
        $this->assertSame('document.pdf', $this->makeFile(
            'document.pdf',
            'webdav/42/document.pdf',
            new AllowAllPathAuthorization,
        )->getName());
    }

    public function test_get_calls_authorize_read_before_returning_content(): void
    {
        Storage::disk('local')->put('webdav/42/file.txt', 'hello');
        $authorization = new AllowAllPathAuthorization;

        $result = $this->makeFile('file.txt', 'webdav/42/file.txt', $authorization)->get();

        $this->assertSame('hello', $result);
        $this->assertSame('read', $authorization->calls[0]['ability']);
    }

    public function test_put_with_string_calls_authorize_write(): void
    {
        Event::fake();
        $authorization = new AllowAllPathAuthorization;

        $this->makeFile('file.txt', 'webdav/42/file.txt', $authorization)->put('content');

        $this->assertSame('content', Storage::disk('local')->get('webdav/42/file.txt'));
        $this->assertSame('write', $authorization->calls[0]['ability']);
        Event::assertDispatched(WebDavFileUpdatedEvent::class, function (WebDavFileUpdatedEvent $event): bool {
            return $event->disk === 'local'
                && $event->path === 'webdav/42/file.txt'
                && $event->principal->id === '42'
                && $event->bytes === 7;
        });
    }

    public function test_put_with_resource_reads_stream_and_calls_authorize_write(): void
    {
        Event::fake();
        $authorization = new AllowAllPathAuthorization;
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'streamed content');
        rewind($resource);

        $this->makeFile('file.txt', 'webdav/42/file.txt', $authorization)->put($resource);

        fclose($resource);

        $this->assertSame('streamed content', Storage::disk('local')->get('webdav/42/file.txt'));
        $this->assertSame('write', $authorization->calls[0]['ability']);
        Event::assertDispatched(WebDavFileUpdatedEvent::class, function (WebDavFileUpdatedEvent $event): bool {
            return $event->path === 'webdav/42/file.txt'
                && $event->bytes === strlen('streamed content');
        });
    }

    public function test_put_throws_when_resource_contents_cannot_be_read(): void
    {
        $this->expectException(StreamReadException::class);

        $authorization = new AllowAllPathAuthorization;
        $resource = fopen('php://memory', 'r+');
        StreamGetContentsOverrideBootstrap::failReads();

        $this->makeFile('file.txt', 'webdav/42/file.txt', $authorization)->put($resource);
    }

    public function test_delete_calls_authorize_delete(): void
    {
        Event::fake();
        Storage::disk('local')->put('webdav/42/file.txt', 'delete-me');
        $authorization = new AllowAllPathAuthorization;

        $this->makeFile('file.txt', 'webdav/42/file.txt', $authorization)->delete();

        $this->assertFalse(Storage::disk('local')->exists('webdav/42/file.txt'));
        $this->assertSame('delete', $authorization->calls[0]['ability']);
        Event::assertDispatched(WebDavFileDeletedEvent::class, function (WebDavFileDeletedEvent $event): bool {
            return $event->disk === 'local'
                && $event->path === 'webdav/42/file.txt'
                && $event->principal->id === '42';
        });
    }

    public function test_get_size_calls_authorize_read_and_returns_size(): void
    {
        Storage::disk('local')->put('webdav/42/file.txt', '12345');
        $authorization = new AllowAllPathAuthorization;

        $size = $this->makeFile('file.txt', 'webdav/42/file.txt', $authorization)->getSize();

        $this->assertSame(5, $size);
        $this->assertSame('read', $authorization->calls[0]['ability']);
    }

    public function test_get_last_modified_calls_authorize_read_and_returns_timestamp(): void
    {
        Storage::disk('local')->put('webdav/42/file.txt', 'timestamp');
        $authorization = new AllowAllPathAuthorization;

        $timestamp = $this->makeFile('file.txt', 'webdav/42/file.txt', $authorization)->getLastModified();

        $this->assertIsInt($timestamp);
        $this->assertGreaterThan(0, $timestamp);
        $this->assertSame('read', $authorization->calls[0]['ability']);
    }
}
