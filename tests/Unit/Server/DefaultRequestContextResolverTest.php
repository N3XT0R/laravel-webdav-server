<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Server;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\DTO\Server\RequestContextDto;
use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use N3XT0R\LaravelWebdavServer\Server\Request\Context\DefaultRequestContextResolver;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Logging\RecordingLogger;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server\FixedRequestCredentialsExtractor;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server\FixedSpaceKeyResolver;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server\RecordingPrincipalAuthenticator;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Storage\RecordingSpaceResolver;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use PHPUnit\Framework\TestCase;

final class DefaultRequestContextResolverTest extends TestCase
{
    public function test_it_resolves_a_full_context_from_a_request(): void
    {
        $request = Request::create('/webdav/default', 'PROPFIND');
        $principal = new WebDavPrincipalValueObject('42', 'Alice');
        $space = new WebDavStorageSpaceValueObject('local', 'webdav/42');

        $credentialsExtractor = new FixedRequestCredentialsExtractor(['alice', 'secret']);
        $authenticator = new RecordingPrincipalAuthenticator($principal);
        $spaceKeyResolver = new FixedSpaceKeyResolver('default');
        $spaceResolver = new RecordingSpaceResolver($space);
        $logger = new RecordingLogger;

        $resolver = new DefaultRequestContextResolver(
            $credentialsExtractor,
            $authenticator,
            $spaceKeyResolver,
            $spaceResolver,
            new WebDavLoggingService($logger, 'stderr', 'debug'),
        );

        $context = $resolver->resolve($request);

        $this->assertInstanceOf(RequestContextDto::class, $context);
        $this->assertSame($principal, $context->principal);
        $this->assertSame('default', $context->spaceKey);
        $this->assertSame($space, $context->space);
        $this->assertCount(1, $credentialsExtractor->requests);
        $this->assertSame([['username' => 'alice', 'password' => 'secret']], $authenticator->calls);
        $this->assertCount(1, $spaceKeyResolver->requests);
        $this->assertSame([['principal' => $principal, 'spaceKey' => 'default']], $spaceResolver->calls);
        $this->assertSame('Resolved WebDAV request context.', $logger->records[0]['message']);
    }
}
