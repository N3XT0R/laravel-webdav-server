<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Exception;

use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\MissingCredentialsException;
use N3XT0R\LaravelWebdavServer\Exception\DomainException;
use PHPUnit\Framework\TestCase;

final class DomainExceptionTest extends TestCase
{
    public function test_context_returns_provided_array(): void
    {
        $exception = new InvalidCredentialsException('msg', 0, ['key' => 'value']);

        $this->assertSame(['key' => 'value'], $exception->context());
    }

    public function test_context_defaults_to_empty_array(): void
    {
        $exception = new InvalidCredentialsException('msg');

        $this->assertSame([], $exception->context());
    }

    public function test_message_is_stored(): void
    {
        $exception = new MissingCredentialsException('credentials missing');

        $this->assertSame('credentials missing', $exception->getMessage());
    }

    public function test_previous_exception_is_chained(): void
    {
        $cause = new \RuntimeException('root cause');
        $exception = new InvalidCredentialsException('wrapper', 0, [], $cause);

        $this->assertSame($cause, $exception->getPrevious());
    }

    public function test_invalid_credentials_exception_is_a_domain_exception(): void
    {
        $this->assertInstanceOf(DomainException::class, new InvalidCredentialsException);
    }

    public function test_missing_credentials_exception_is_a_domain_exception(): void
    {
        $this->assertInstanceOf(DomainException::class, new MissingCredentialsException);
    }

    public function test_domain_exception_extends_php_domain_exception(): void
    {
        $this->assertInstanceOf(\DomainException::class, new InvalidCredentialsException);
    }
}
