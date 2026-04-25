<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use N3XT0R\LaravelWebdavServer\Exception\Auth\MissingUserModelConfigurationException;

/**
 * @property Model $user
 */
final class WebDavAccountModel extends Model
{
    use HasFactory;

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

    /**
     * Resolves the owning Laravel user model for this WebDAV account.
     *
     * @return BelongsTo<Model, $this> Belongs-to relation to the configured application user model.
     *
     * @throws MissingUserModelConfigurationException When `webdav-server.auth.user_model` is not configured.
     */
    public function user(): BelongsTo
    {
        $model = config('webdav-server.auth.user_model');

        if ($model === null) {
            throw new MissingUserModelConfigurationException(
                'No user model configured. Please set "webdav-server.auth.user_model" in your config.',
            );
        }

        return $this->belongsTo($model, 'user_id');
    }
}
