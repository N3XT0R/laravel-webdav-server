<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Server;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Exception\Auth\MissingCredentialsException;
use N3XT0R\LaravelWebdavServer\Server\Request\Auth\RequestBasicCredentialsExtractor;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;

final class RequestBasicCredentialsExtractorTest extends TestCase
{
    public function test_it_extracts_credentials_from_php_auth_server_values(): void
    {
        $request = Request::create('/webdav', 'PROPFIND', server: [
            'PHP_AUTH_USER' => 'alice',
            'PHP_AUTH_PW' => 'secret',
        ]);

        $extractor = new RequestBasicCredentialsExtractor;

        $this->assertSame(['alice', 'secret'], $extractor->extract($request));
    }

    public function test_it_throws_when_no_basic_credentials_are_available(): void
    {
        $this->expectException(MissingCredentialsException::class);

        $request = Request::create('/webdav', 'PROPFIND');

        $extractor = new RequestBasicCredentialsExtractor;
        $extractor->extract($request);
    }
}
