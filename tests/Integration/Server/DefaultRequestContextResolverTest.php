<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Server;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Server\PrincipalAuthenticatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestCredentialsExtractorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\SpaceKeyResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\DTO\Server\WebDavRequestContextDto;
use N3XT0R\LaravelWebdavServer\Server\Request\Context\DefaultRequestContextResolver;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpace;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final class DefaultRequestContextResolverTest extends TestCase
{
    public function test_it_resolves_a_full_context_from_a_request(): void
    {
        $request = Request::create('/webdav/default', 'PROPFIND');
        $principal = new WebDavPrincipal('42', 'Alice');
        $space = new WebDavStorageSpace('local', 'webdav/42');

        $credentialsExtractor = $this->createMock(RequestCredentialsExtractorInterface::class);
        $credentialsExtractor->expects($this->once())
            ->method('extract')
            ->with($request)
            ->willReturn(['alice', 'secret']);

        $authenticator = $this->createMock(PrincipalAuthenticatorInterface::class);
        $authenticator->expects($this->once())
            ->method('authenticate')
            ->with('alice', 'secret')
            ->willReturn($principal);

        $spaceKeyResolver = $this->createMock(SpaceKeyResolverInterface::class);
        $spaceKeyResolver->expects($this->once())
            ->method('resolve')
            ->with($request)
            ->willReturn('default');

        $spaceResolver = $this->createMock(SpaceResolverInterface::class);
        $spaceResolver->expects($this->once())
            ->method('resolve')
            ->with($principal, 'default')
            ->willReturn($space);

        $resolver = new DefaultRequestContextResolver(
            $credentialsExtractor,
            $authenticator,
            $spaceKeyResolver,
            $spaceResolver,
        );

        $context = $resolver->resolve($request);

        $this->assertInstanceOf(WebDavRequestContextDto::class, $context);
        $this->assertSame($principal, $context->principal);
        $this->assertSame('default', $context->spaceKey);
        $this->assertSame($space, $context->space);
    }
}
