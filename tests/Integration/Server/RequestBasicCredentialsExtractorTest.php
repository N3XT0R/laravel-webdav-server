<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Server;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
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

    public function test_it_throws_invalid_exception_when_php_auth_username_is_empty(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $request = Request::create('/webdav', 'PROPFIND', server: [
            'PHP_AUTH_USER' => '',
            'PHP_AUTH_PW' => 'secret',
        ]);

        (new RequestBasicCredentialsExtractor)->extract($request);
    }

    public function test_it_throws_invalid_exception_when_php_auth_password_is_empty(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $request = Request::create('/webdav', 'PROPFIND', server: [
            'PHP_AUTH_USER' => 'alice',
            'PHP_AUTH_PW' => '',
        ]);

        (new RequestBasicCredentialsExtractor)->extract($request);
    }

    public function test_it_throws_when_no_basic_credentials_are_available(): void
    {
        $this->expectException(MissingCredentialsException::class);

        $request = Request::create('/webdav', 'PROPFIND');

        $extractor = new RequestBasicCredentialsExtractor;
        $extractor->extract($request);
    }

    public function test_it_extracts_credentials_from_a_valid_authorization_header(): void
    {
        $request = Request::create('/webdav', 'PROPFIND', server: [
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode('bob:pass123'),
        ]);

        $extractor = new RequestBasicCredentialsExtractor;

        $this->assertSame(['bob', 'pass123'], $extractor->extract($request));
    }

    public function test_it_throws_missing_exception_when_authorization_scheme_is_not_basic(): void
    {
        $this->expectException(MissingCredentialsException::class);

        $request = Request::create('/webdav', 'PROPFIND', server: [
            'HTTP_AUTHORIZATION' => 'Bearer sometoken',
        ]);

        (new RequestBasicCredentialsExtractor)->extract($request);
    }

    public function test_it_throws_invalid_exception_for_malformed_base64_without_colon(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $request = Request::create('/webdav', 'PROPFIND', server: [
            // base64('usernameonly') — no colon in decoded value
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode('usernameonly'),
        ]);

        (new RequestBasicCredentialsExtractor)->extract($request);
    }

    public function test_it_throws_invalid_exception_when_username_is_empty(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $request = Request::create('/webdav', 'PROPFIND', server: [
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode(':password'),
        ]);

        (new RequestBasicCredentialsExtractor)->extract($request);
    }

    public function test_it_throws_invalid_exception_when_password_is_empty(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $request = Request::create('/webdav', 'PROPFIND', server: [
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode('user:'),
        ]);

        (new RequestBasicCredentialsExtractor)->extract($request);
    }

    public function test_it_allows_colons_in_the_password(): void
    {
        $request = Request::create('/webdav', 'PROPFIND', server: [
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode('alice:p:a:s:s'),
        ]);

        $extractor = new RequestBasicCredentialsExtractor;

        $this->assertSame(['alice', 'p:a:s:s'], $extractor->extract($request));
    }
}
