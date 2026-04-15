<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use N3XT0R\LaravelWebdavServer\Http\Controllers\WebDavController;

Route::any('/webdav/{space}/{path?}', WebDavController::class)
    ->where('path', '.*');
