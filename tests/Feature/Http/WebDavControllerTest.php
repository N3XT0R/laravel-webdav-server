<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Feature\Http;

use N3XT0R\LaravelWebdavServer\Tests\TestCase;

final class WebDavControllerTest extends TestCase
{
    public function test_request_without_basic_auth_returns_401_and_www_authenticate_header(): void
    {
        $response = $this->get('/webdav');

        $response->assertUnauthorized();
        $response->assertHeader('WWW-Authenticate', 'Basic realm="WebDAV"');
    }
}
