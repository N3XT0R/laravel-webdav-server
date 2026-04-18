<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Exception;

abstract class DomainException extends \DomainException
{
    public function __construct(
        string $message = "",
        int $code = 0,
        protected array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function context(): array
    {
        return $this->context;
    }
}