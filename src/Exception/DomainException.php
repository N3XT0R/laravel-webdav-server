<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Exception;

abstract class DomainException extends \DomainException
{
    /**
     * @param  string  $message  Human-readable error message for the domain failure.
     * @param  int  $code  Optional exception code for programmatic consumers.
     * @param  array<string, mixed>  $context  Structured domain context that helps callers log or inspect the failure.
     * @param  \Throwable|null  $previous  Previous exception that caused this domain exception, if any.
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        protected array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns additional structured context for this domain exception.
     *
     * @return array<string, mixed> Arbitrary key-value pairs describing the failing domain state.
     */
    public function context(): array
    {
        return $this->context;
    }
}
