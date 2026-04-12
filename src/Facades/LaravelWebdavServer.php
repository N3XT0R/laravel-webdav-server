<?php

namespace N3XT0R\LaravelWebdavServer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \N3XT0R\LaravelWebdavServer\LaravelWebdavServer
 */
class LaravelWebdavServer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \N3XT0R\LaravelWebdavServer\LaravelWebdavServer::class;
    }
}
