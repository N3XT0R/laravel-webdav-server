<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Server\Configuration\Plugins;

use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use N3XT0R\LaravelWebdavServer\Server\Configuration\Plugins\CompatibilityLoggingPlugin;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Logging\RecordingLogger;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use Sabre\DAV\Server;
use Sabre\DAV\SimpleCollection;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

final class CompatibilityLoggingPluginTest extends TestCase
{
    public function test_it_logs_root_propfind_requests_and_completion_details(): void
    {
        $recordingLogger = new RecordingLogger;
        $logger = new WebDavLoggingService($recordingLogger, 'stderr', 'debug');
        $server = new Server(new SimpleCollection('root', []));

        $server->setBaseUri('/webdav/default/');
        (new CompatibilityLoggingPlugin($logger))->initialize($server);

        $request = new Request('PROPFIND', '/webdav/default/', [
            'Depth' => '1',
        ]);
        $request->setBaseUrl('/webdav/default/');
        $response = new Response(207, [
            'Content-Type' => ['application/xml; charset=utf-8'],
        ]);

        $server->emit('beforeMethod:PROPFIND', [$request, $response]);
        $server->emit('afterMethod:PROPFIND', [$request, $response]);

        $this->assertCount(3, $recordingLogger->records);
        $this->assertSame('Handling WebDAV request inside SabreDAV.', $recordingLogger->records[0]['message']);
        $this->assertSame('Handling PROPFIND for the WebDAV root collection.', $recordingLogger->records[1]['message']);
        $this->assertSame('Completed WebDAV PROPFIND request.', $recordingLogger->records[2]['message']);
        $this->assertSame('/webdav/default/', $recordingLogger->records[2]['context']['webdav']['base_uri']);
        $this->assertSame(207, $recordingLogger->records[2]['context']['webdav']['status']);
    }

    public function test_it_logs_options_responses_for_capability_discovery(): void
    {
        $recordingLogger = new RecordingLogger;
        $logger = new WebDavLoggingService($recordingLogger, 'stderr', 'debug');
        $server = new Server(new SimpleCollection('root', []));

        $server->setBaseUri('/webdav/default/');
        (new CompatibilityLoggingPlugin($logger))->initialize($server);

        $request = new Request('OPTIONS', '/webdav/default/');
        $request->setBaseUrl('/webdav/default/');
        $response = new Response(200, [
            'Allow' => ['OPTIONS, PROPFIND, GET'],
            'DAV' => ['1, 3'],
        ]);

        $server->emit('beforeMethod:OPTIONS', [$request, $response]);
        $server->emit('afterMethod:OPTIONS', [$request, $response]);

        $this->assertCount(2, $recordingLogger->records);
        $this->assertSame('Completed WebDAV OPTIONS request.', $recordingLogger->records[1]['message']);
        $this->assertSame('OPTIONS, PROPFIND, GET', $recordingLogger->records[1]['context']['webdav']['allow']);
        $this->assertSame('1, 3', $recordingLogger->records[1]['context']['webdav']['dav']);
    }
}
