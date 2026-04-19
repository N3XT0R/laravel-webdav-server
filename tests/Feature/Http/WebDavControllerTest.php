<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Feature\Http;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerRunnerInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use RuntimeException;
use Sabre\DAV\Server;

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
        $validator = $this->createMock(CredentialValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with('alice', 'secret')
            ->willReturn(null);

        $this->app->instance(CredentialValidatorInterface::class, $validator);
        $this->withoutExceptionHandling();
        $this->expectException(InvalidCredentialsException::class);

        $this->call('PROPFIND', '/webdav', server: [
            'PHP_AUTH_USER' => 'alice',
            'PHP_AUTH_PW' => 'secret',
        ]);
    }

    public function test_request_with_valid_basic_auth_calls_server_runner(): void
    {
        $validator = $this->createMock(CredentialValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with('alice', 'secret')
            ->willReturn(new WebDavPrincipal('42', 'Alice'));

        $runner = $this->createMock(ServerRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->with($this->isInstanceOf(Server::class))
            ->willThrowException(new RuntimeException('Runner called.'));

        $this->app->instance(CredentialValidatorInterface::class, $validator);
        $this->app->instance(ServerRunnerInterface::class, $runner);

        $this->withoutExceptionHandling();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Runner called.');

        $this->call('PROPFIND', '/webdav', server: [
            'PHP_AUTH_USER' => 'alice',
            'PHP_AUTH_PW' => 'secret',
        ]);
    }
}
