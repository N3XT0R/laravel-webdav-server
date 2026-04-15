<?php

declare(strict_types=1);
use N3XT0R\LaravelWebdavServer\Providers\WebdavServerServiceProvider;

return [
    /*
    |--------------------------------------------------------------------------
    | WebDav Server Providers
    |--------------------------------------------------------------------------
    |
    | Here you may specify the service providers for the WebDav server. These
    | providers will be registered when the server is bootstrapped.
    |
    */

    WebdavServerServiceProvider::class,
];
