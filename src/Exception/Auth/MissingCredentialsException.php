<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Exception\Auth;

use N3XT0R\LaravelWebdavServer\Exception\DomainException;
use Illuminate\Contracts\Debug\ShouldntReport;

class MissingCredentialsException extends DomainException implements ShouldntReport
{

}