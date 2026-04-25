<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server;

use Sabre\HTTP\Request;
use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\Sapi;

final class RecordingSabreSapi extends Sapi
{
    public static ?Request $request = null;

    public static ?ResponseInterface $response = null;

    public static function getRequest(): Request
    {
        if (self::$request instanceof Request) {
            return self::$request;
        }

        return new Request('GET', '/');
    }

    public static function sendResponse(ResponseInterface $response): void
    {
        self::$response = $response;
    }

    public static function reset(): void
    {
        self::$request = null;
        self::$response = null;
    }
}
