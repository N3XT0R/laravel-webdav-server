<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Server;

use N3XT0R\LaravelWebdavServer\Server\Runtime\SabreServerRunner;
use PHPUnit\Framework\TestCase;
use Sabre\DAV\Server;

final class SabreServerRunnerTest extends TestCase
{
    public function test_run_starts_the_server_before_terminating_the_process(): void
    {
        $this->expectExceptionObject(new SabreServerRunnerTerminatedException);

        $runner = new SabreServerRunner(static function (): never {
            throw new SabreServerRunnerTerminatedException;
        });

        $runner->run(new class extends Server
        {
            public bool $started = false;

            public function __construct() {}

            public function start()
            {
                $this->started = true;
            }
        });
    }
}

final class SabreServerRunnerTerminatedException extends \RuntimeException {}
