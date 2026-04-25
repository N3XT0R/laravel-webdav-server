<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Server\Configuration\Plugins;

use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use N3XT0R\LaravelWebdavServer\Server\Configuration\Plugins\MissingPathPropFindPlugin;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Logging\RecordingLogger;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use Sabre\DAV\Server;
use Sabre\DAV\SimpleCollection;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

final class MissingPathPropFindPluginTest extends TestCase
{
    public function test_it_handles_missing_propfind_targets_with_a_plain_404_response(): void
    {
        $recordingLogger = new RecordingLogger;
        $plugin = new MissingPathPropFindPlugin(new WebDavLoggingService($recordingLogger, 'stderr', 'debug'));
        $server = new Server(new SimpleCollection('root', []));

        $server->setBaseUri('/webdav/default/');
        $plugin->initialize($server);

        $request = new Request('PROPFIND', '/webdav/default/missing.txt', [
            'Depth' => '0',
        ]);
        $request->setBaseUrl('/webdav/default/');
        $response = new Response;

        $result = $plugin->handleMissingTargetPropFind($request, $response);

        $this->assertFalse($result);
        $this->assertSame(404, $response->getStatus());
        $this->assertSame('', $response->getBodyAsString());
        $this->assertCount(1, $recordingLogger->records);
    }

    public function test_it_skips_existing_targets_and_leaves_propfind_to_core_plugin(): void
    {
        $plugin = new MissingPathPropFindPlugin(new WebDavLoggingService(new RecordingLogger, 'stderr', 'debug'));
        $server = new Server(new SimpleCollection('root', [
            new SimpleCollection('existing', []),
        ]));

        $server->setBaseUri('/webdav/default/');
        $plugin->initialize($server);

        $request = new Request('PROPFIND', '/webdav/default/existing/', [
            'Depth' => '1',
        ]);
        $request->setBaseUrl('/webdav/default/');
        $response = new Response;

        $this->assertNull($plugin->handleMissingTargetPropFind($request, $response));
        $this->assertSame(500, $response->getStatus());
    }
}
