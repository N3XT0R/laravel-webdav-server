<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Feature\Http;

use Mockery;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;

final class WebDavControllerTest extends TestCase
{
    public function test_request_without_basic_auth_returns_401_and_www_authenticate_header(): void
    {
        $response = $this->get('/webdav');

        $response->assertUnauthorized();
        $response->assertHeader('WWW-Authenticate', 'Basic realm="WebDAV"');
    }

    public function test_request_with_malformed_basic_auth_header_throws_invalid_credentials_exception(): void
    {
        $this->withoutExceptionHandling();
        $this->expectException(InvalidCredentialsException::class);

        $this->call('PROPFIND', '/webdav', server: [
            // base64("foo") => no ":" delimiter after decoding
            'HTTP_AUTHORIZATION' => 'Basic Zm9v',
        ]);
    }

    public function test_request_with_php_auth_attempt_reaches_authentication_pipeline(): void
    {
        $validator = Mockery::mock(CredentialValidatorInterface::class);
        $validator->shouldReceive('validate')
            ->once()
            ->with('alice', 'secret')
            ->andReturnNull();

        $this->app->instance(CredentialValidatorInterface::class, $validator);
        $this->withoutExceptionHandling();
        $this->expectException(InvalidCredentialsException::class);

        $this->call('PROPFIND', '/webdav', server: [
            'PHP_AUTH_USER' => 'alice',
            'PHP_AUTH_PW' => 'secret',
        ]);
    }
}
