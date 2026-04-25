<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

final readonly class WebDavLoggingService implements LoggerInterface
{
    /**
     * @var array<string, int>
     */
    private const LEVEL_PRIORITY = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT => 1,
        LogLevel::CRITICAL => 2,
        LogLevel::ERROR => 3,
        LogLevel::WARNING => 4,
        LogLevel::NOTICE => 5,
        LogLevel::INFO => 6,
        LogLevel::DEBUG => 7,
    ];

    public function __construct(
        private ?LoggerInterface $logger,
        private ?string $driver,
        private string $minimumLevel = LogLevel::INFO,
    ) {}

    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        if (! is_string($level) || $this->logger === null) {
            return;
        }

        $normalizedLevel = $this->normalizeLevel($level);

        if (! $this->shouldLog($normalizedLevel)) {
            return;
        }

        $this->logger->log($normalizedLevel, $message, $context);
    }

    public function sabreLogger(): ?LoggerInterface
    {
        if (! $this->isEnabled()) {
            return null;
        }

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->logger !== null;
    }

    public function driver(): ?string
    {
        return $this->driver;
    }

    public function minimumLevel(): string
    {
        return $this->normalizeLevel($this->minimumLevel);
    }

    private function shouldLog(string $level): bool
    {
        $normalizedLevel = $this->normalizeLevel($level);
        $normalizedMinimumLevel = $this->normalizeLevel($this->minimumLevel);

        return self::LEVEL_PRIORITY[$normalizedLevel] <= self::LEVEL_PRIORITY[$normalizedMinimumLevel];
    }

    private function normalizeLevel(string $level): string
    {
        if (array_key_exists($level, self::LEVEL_PRIORITY)) {
            return $level;
        }

        return LogLevel::INFO;
    }
}
