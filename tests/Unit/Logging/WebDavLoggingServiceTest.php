<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Logging;

use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Logging\RecordingLogger;
use PHPUnit\Framework\TestCase;

final class WebDavLoggingServiceTest extends TestCase
{
    public function test_it_logs_messages_when_logging_is_enabled_and_the_level_matches(): void
    {
        $logger = new RecordingLogger;
        $service = new WebDavLoggingService($logger, 'stderr', 'debug');

        $service->emergency('Emergency.');
        $service->alert('Alert.');
        $service->critical('Critical.');
        $service->warning('Warning.');
        $service->notice('Notice.');
        $service->info('Authentication succeeded.', ['auth' => ['username' => 'alice']]);
        $service->debug('Resolved request context.', ['webdav' => ['space_key' => 'default']]);

        $this->assertCount(7, $logger->records);
        $this->assertSame('emergency', $logger->records[0]['level']);
        $this->assertSame('alert', $logger->records[1]['level']);
        $this->assertSame('critical', $logger->records[2]['level']);
        $this->assertSame('warning', $logger->records[3]['level']);
        $this->assertSame('notice', $logger->records[4]['level']);
        $this->assertSame('info', $logger->records[5]['level']);
        $this->assertSame('debug', $logger->records[6]['level']);
        $this->assertSame('stderr', $service->driver());
        $this->assertSame('debug', $service->minimumLevel());
        $this->assertSame($service, $service->sabreLogger());
    }

    public function test_it_filters_debug_logs_when_the_minimum_level_is_info(): void
    {
        $logger = new RecordingLogger;
        $service = new WebDavLoggingService($logger, 'stderr', 'info');

        $service->debug('This should not be logged.');
        $service->info('This should be logged.');

        $this->assertCount(1, $logger->records);
        $this->assertSame('info', $logger->records[0]['level']);
    }

    public function test_it_disables_logging_when_no_logger_is_configured(): void
    {
        $service = new WebDavLoggingService(null, null, 'debug');

        $service->info('Authentication succeeded.');

        $this->assertFalse($service->isEnabled());
        $this->assertNull($service->sabreLogger());
        $this->assertNull($service->driver());
    }

    public function test_it_falls_back_to_info_for_unknown_levels(): void
    {
        $logger = new RecordingLogger;
        $service = new WebDavLoggingService($logger, 'stderr', 'verbose');

        $service->log('trace', 'Normalized unsupported trace message.');
        $service->debug('Ignored debug message because fallback is info.');
        $service->error('Logged error message.');

        $this->assertCount(2, $logger->records);
        $this->assertSame('info', $logger->records[0]['level']);
        $this->assertSame('error', $logger->records[1]['level']);
        $this->assertSame('info', $service->minimumLevel());
    }
}
