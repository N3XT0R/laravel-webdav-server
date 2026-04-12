<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use N3xt0r\LaravelWebdavServer\Http\Controllers\WebDavController;

Route::any('/webdav/{path?}', WebDavController::class)
    ->where('path', '.*');