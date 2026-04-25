<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

final class InvalidDisplayNameAccountModel extends Model
{
    protected $table = 'webdav_accounts';

    protected $fillable = [
        'username',
        'password_encrypted',
        'enabled',
        'user_id',
        'display_name',
        'meta',
    ];

    public function getAttribute($key)
    {
        if ($key === 'display_name') {
            return ['invalid'];
        }

        return parent::getAttribute($key);
    }
}
