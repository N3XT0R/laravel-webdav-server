<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Models;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use N3XT0R\LaravelWebdavServer\Exception\Auth\MissingUserModelConfigurationException;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;
use N3XT0R\LaravelWebdavServer\Tests\DatabaseTestCase;
use Workbench\App\Models\User;

final class WebDavAccountModelTest extends DatabaseTestCase
{
    public function test_user_returns_a_belongs_to_relation_for_the_configured_user_model(): void
    {
        $relation = (new WebDavAccountModel)->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(WebDavAccountModel::class, $relation->getChild());
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }

    public function test_user_throws_when_no_user_model_is_configured(): void
    {
        $this->expectException(MissingUserModelConfigurationException::class);

        $this->app->make(Repository::class)->set('webdav-server.auth.user_model', null);

        (new WebDavAccountModel)->user();
    }
}
