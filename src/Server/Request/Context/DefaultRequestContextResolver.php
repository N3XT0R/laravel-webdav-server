<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Request\Context;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Server\PrincipalAuthenticatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestContextResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestCredentialsExtractorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\SpaceKeyResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\DTO\Server\WebDavRequestContextDto;

final readonly class DefaultRequestContextResolver implements RequestContextResolverInterface
{
    public function __construct(
        private RequestCredentialsExtractorInterface $credentialsExtractor,
        private PrincipalAuthenticatorInterface $principalAuthenticator,
        private SpaceKeyResolverInterface $spaceKeyResolver,
        private SpaceResolverInterface $spaceResolver,
    ) {}

    public function resolve(Request $request): WebDavRequestContextDto
    {
        [$username, $password] = $this->credentialsExtractor->extract($request);

        $principal = $this->principalAuthenticator->authenticate($username, $password);
        $spaceKey = $this->spaceKeyResolver->resolve($request);
        $space = $this->spaceResolver->resolve($principal, $spaceKey);

        return new WebDavRequestContextDto(
            principal: $principal,
            spaceKey: $spaceKey,
            space: $space,
        );
    }
}
