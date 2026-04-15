<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class WebDavAccount extends Model
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

    protected $casts = [
        'enabled' => 'bool',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        $model = config('webdav.auth.user_model');

        if ($model === null) {
            throw new RuntimeException(
                'No user model configured. Please set "webdav.auth.user_model" in your config.'
            );
        }

        return $this->belongsTo($model, 'user_id');
    }
}
