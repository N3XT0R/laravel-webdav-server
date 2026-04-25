<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Request\Context;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Server\PrincipalAuthenticatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestContextResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestCredentialsExtractorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\SpaceKeyResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\DTO\Server\RequestContextDto;
use N3XT0R\LaravelWebdavServer\Exception\DomainException;

final readonly class DefaultRequestContextResolver implements RequestContextResolverInterface
{
    /**
     * Create the default request-context resolver for WebDAV requests.
     *
     * @param  RequestCredentialsExtractorInterface  $credentialsExtractor  Extractor used to parse Basic Auth credentials from the request.
     * @param  PrincipalAuthenticatorInterface  $principalAuthenticator  Authenticator used to validate credentials and resolve the principal.
     * @param  SpaceKeyResolverInterface  $spaceKeyResolver  Resolver used to determine the logical storage space key.
     * @param  SpaceResolverInterface  $spaceResolver  Resolver used to map the logical space key to a concrete disk and root path.
     */
    public function __construct(
        private RequestCredentialsExtractorInterface $credentialsExtractor,
        private PrincipalAuthenticatorInterface $principalAuthenticator,
        private SpaceKeyResolverInterface $spaceKeyResolver,
        private SpaceResolverInterface $spaceResolver,
    ) {}

    /**
     * Resolve the full runtime context needed to construct the WebDAV server for the request.
     *
     * @param  Request  $request  Incoming HTTP request targeting the WebDAV endpoint.
     * @return RequestContextDto Runtime DTO containing principal, space key, and resolved storage space.
     *
     * @throws DomainException When credentials, auth, or storage resolution fails.
     */
    public function resolve(Request $request): RequestContextDto
    {
        [$username, $password] = $this->credentialsExtractor->extract($request);

        $principal = $this->principalAuthenticator->authenticate($username, $password);
        $spaceKey = $this->spaceKeyResolver->resolve($request);
        $space = $this->spaceResolver->resolve($principal, $spaceKey);

        return new RequestContextDto(
            principal: $principal,
            spaceKey: $spaceKey,
            space: $space,
        );
    }
}
