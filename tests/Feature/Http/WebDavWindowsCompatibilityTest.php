<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Feature\Http;

use DOMDocument;
use DOMXPath;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerRunnerInterface;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;
use N3XT0R\LaravelWebdavServer\Tests\DatabaseTestCase;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server\HttpSabreResponseRunner;
use Workbench\App\Models\User;

final class WebDavWindowsCompatibilityTest extends DatabaseTestCase
{
    private string $diskRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diskRoot = sys_get_temp_dir().'/laravel-webdav-server-tests/'.str_replace('\\', '-', self::class);

        $this->app['config']->set('filesystems.disks.local.root', $this->diskRoot);
        $this->app->bind(ServerRunnerInterface::class, HttpSabreResponseRunner::class);

        (new Filesystem)->deleteDirectory($this->diskRoot);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->diskRoot);

        parent::tearDown();
    }

    public function test_options_request_returns_dav_and_allow_headers_for_windows_clients(): void
    {
        $this->createAccount('testuser', 'password');

        $response = $this->call('OPTIONS', '/webdav/default/', server: [
            'PHP_AUTH_USER' => 'testuser',
            'PHP_AUTH_PW' => 'password',
        ]);

        $response->assertOk();
        $response->assertHeader('DAV');
        $response->assertHeader('MS-Author-Via', 'DAV');

        $allow = (string) $response->headers->get('Allow');

        $this->assertStringContainsString('OPTIONS', $allow);
        $this->assertStringContainsString('PROPFIND', $allow);
        $this->assertStringContainsString('GET', $allow);
        $this->assertStringContainsString('PUT', $allow);
        $this->assertStringContainsString('DELETE', $allow);
    }

    public function test_propfind_depth_zero_returns_the_root_collection_as_multistatus(): void
    {
        $this->createAccount('testuser', 'password');

        $response = $this->propfind('/webdav/default/', '0');

        $response->assertStatus(207);
        $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');

        $xpath = $this->xml($response->getContent());

        $this->assertSame(1, $xpath->query('/d:multistatus')->length);
        $this->assertSame(1, $xpath->query('/d:multistatus/d:response[d:href="/webdav/default/"]')->length);
        $this->assertSame(
            1,
            $xpath->query('/d:multistatus/d:response[d:href="/webdav/default/"]/d:propstat/d:prop/d:resourcetype/d:collection')->length,
        );
    }

    public function test_propfind_depth_one_returns_a_valid_empty_root_collection_when_storage_is_missing(): void
    {
        $this->createAccount('testuser', 'password');

        $response = $this->propfind('/webdav/default/', '1');

        $response->assertStatus(207);

        $xpath = $this->xml($response->getContent());

        $this->assertSame(1, $xpath->query('/d:multistatus/d:response')->length);
        $this->assertSame(1, $xpath->query('/d:multistatus/d:response[d:href="/webdav/default/"]')->length);
    }

    public function test_propfind_depth_one_returns_root_and_children_using_space_relative_hrefs(): void
    {
        $user = $this->createAccount('testuser', 'password');

        Storage::disk('local')->makeDirectory('webdav/'.$user->getKey().'/documents');
        Storage::disk('local')->put('webdav/'.$user->getKey().'/readme.txt', 'hello');

        $response = $this->propfind('/webdav/default/', '1');

        $response->assertStatus(207);

        $xpath = $this->xml($response->getContent());

        $this->assertSame(1, $xpath->query('/d:multistatus/d:response[d:href="/webdav/default/"]')->length);
        $this->assertSame(1, $xpath->query('/d:multistatus/d:response[d:href="/webdav/default/documents/"]')->length);
        $this->assertSame(1, $xpath->query('/d:multistatus/d:response[d:href="/webdav/default/readme.txt"]')->length);
        $this->assertSame(0, $xpath->query('/d:multistatus/d:response[contains(d:href, "/'.$user->getKey().'/")]')->length);
    }

    public function test_propfind_without_credentials_returns_a_basic_auth_challenge(): void
    {
        $response = $this->call('PROPFIND', '/webdav/default/', server: [
            'HTTP_DEPTH' => '1',
            'CONTENT_TYPE' => 'application/xml',
        ], content: $this->propfindBody());

        $response->assertUnauthorized();
        $response->assertHeader('WWW-Authenticate', 'Basic realm="WebDAV"');
        $this->assertSame('Unauthorized', $response->getContent());
    }

    public function test_propfind_without_trailing_slash_keeps_the_space_base_uri_and_returns_the_root_href_with_slash(): void
    {
        $this->createAccount('testuser', 'password');

        $response = $this->propfind('/webdav/default', '1');

        $response->assertStatus(207);

        $xpath = $this->xml($response->getContent());

        $this->assertSame(1, $xpath->query('/d:multistatus/d:response[d:href="/webdav/default/"]')->length);
    }

    public function test_propfind_for_a_missing_target_file_returns_a_plain_404_response(): void
    {
        $this->createAccount('testuser', 'password');

        $response = $this->propfind('/webdav/default/test4.log', '0');

        $response->assertNotFound();
        $this->assertSame('', $response->getContent());
    }

    private function createAccount(string $username, string $password): User
    {
        $user = User::factory()->create([
            'name' => ucfirst($username),
        ]);

        WebDavAccountModel::factory()
            ->withUserName($username)
            ->withPassword($password)
            ->withUserId((int) $user->getKey())
            ->create([
                'display_name' => ucfirst($username),
            ]);

        return $user;
    }

    private function propfind(string $uri, string $depth): TestResponse
    {
        return $this->call('PROPFIND', $uri, server: [
            'PHP_AUTH_USER' => 'testuser',
            'PHP_AUTH_PW' => 'password',
            'HTTP_DEPTH' => $depth,
            'CONTENT_TYPE' => 'application/xml',
        ], content: $this->propfindBody());
    }

    private function propfindBody(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8" ?>
<d:propfind xmlns:d="DAV:">
  <d:allprop />
</d:propfind>
XML;
    }

    private function xml(string $content): DOMXPath
    {
        $document = new DOMDocument;
        $document->loadXML($content);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('d', 'DAV:');

        return $xpath;
    }
}
