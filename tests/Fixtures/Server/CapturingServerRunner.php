<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server;

use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerRunnerInterface;
use Sabre\DAV\ICollection;
use Sabre\DAV\Server;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class CapturingServerRunner implements ServerRunnerInterface
{
    public static ?Server $lastServer = null;

    public function run(Server $server): Response
    {
        self::$lastServer = $server;

        $root = $server->tree->getNodeForPath('');
        $children = [];

        foreach ($root->getChildren() as $child) {
            $children[] = [
                'name' => $child->getName(),
                'type' => $child instanceof ICollection ? 'directory' : 'file',
            ];
        }

        return new JsonResponse([
            'baseUri' => $server->getBaseUri(),
            'rootClass' => $root::class,
            'rootName' => $root->getName(),
            'children' => $children,
        ]);
    }

    public static function reset(): void
    {
        self::$lastServer = null;
    }
}
