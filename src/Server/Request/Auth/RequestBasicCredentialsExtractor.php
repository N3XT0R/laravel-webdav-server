<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Request\Auth;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestCredentialsExtractorInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\MissingCredentialsException;
use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;

final readonly class RequestBasicCredentialsExtractor implements RequestCredentialsExtractorInterface
{
    /**
     * @param  WebDavLoggingService  $logger  Package logger used to trace credential extraction without logging secrets.
     */
    public function __construct(
        private WebDavLoggingService $logger,
    ) {}

    /**
     * Extract Basic Auth credentials from either PHP auth server values or the `Authorization` header.
     *
     * @param  Request  $request  Incoming HTTP request targeting the WebDAV endpoint.
     * @return array{0:string,1:string}
     *                                  Tuple of `[username, password]` extracted from the request.
     *
     * @throws MissingCredentialsException When no usable Basic Auth credentials are present.
     * @throws InvalidCredentialsException When the Basic Auth payload is malformed or incomplete.
     */
    public function extract(Request $request): array
    {
        $serverCredentials = $this->extractServerCredentials($request);

        if ($serverCredentials !== null) {
            $this->logger->debug('Extracted WebDAV credentials from PHP auth server values.', [
                'auth' => [
                    'username' => $serverCredentials[0],
                    'source' => 'php_auth',
                ],
                'request' => [
                    'method' => $request->getMethod(),
                    'uri' => $request->getRequestUri(),
                ],
            ]);

            return $serverCredentials;
        }

        return $this->extractAuthorizationHeaderCredentials($request);
    }

    /**
     * @return array{0:string,1:string}|null
     */
    private function extractServerCredentials(Request $request): ?array
    {
        $username = $request->getUser();
        $password = $request->getPassword();

        if (! is_string($username) || ! is_string($password)) {
            return null;
        }

        $this->assertCredentialsAreComplete($request, $username, $password);

        return [$username, $password];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function extractAuthorizationHeaderCredentials(Request $request): array
    {
        $authorization = $this->extractAuthorizationHeader($request);
        $decoded = $this->decodeAuthorizationHeader($request, $authorization);
        [$username, $password] = $this->splitDecodedCredentials($decoded);

        $this->assertCredentialsAreComplete($request, $username, $password);

        $this->logger->debug('Extracted WebDAV credentials from the Authorization header.', [
            'auth' => [
                'username' => $username,
                'source' => 'authorization_header',
            ],
            'request' => [
                'method' => $request->getMethod(),
                'uri' => $request->getRequestUri(),
            ],
        ]);

        return [$username, $password];
    }

    private function extractAuthorizationHeader(Request $request): string
    {
        $authorization = $request->headers->get('Authorization');

        if (! is_string($authorization) || ! str_starts_with($authorization, 'Basic ')) {
            $this->logger->info('Missing WebDAV Basic Auth credentials.', [
                'request' => [
                    'method' => $request->getMethod(),
                    'uri' => $request->getRequestUri(),
                ],
            ]);

            throw new MissingCredentialsException(
                message: 'Basic Auth credentials are required to access the WebDAV server.',
                context: [
                    'request' => [
                        'method' => $request->getMethod(),
                        'uri' => $request->getRequestUri(),
                        'headers' => $request->headers->all(),
                    ],
                ],
            );
        }

        return $authorization;
    }

    private function decodeAuthorizationHeader(Request $request, string $authorization): string
    {
        $decoded = base64_decode(substr($authorization, 6), true);

        if (! is_string($decoded) || ! str_contains($decoded, ':')) {
            $this->logger->info('Malformed WebDAV Basic Auth header.', [
                'request' => [
                    'method' => $request->getMethod(),
                    'uri' => $request->getRequestUri(),
                ],
            ]);

            throw new InvalidCredentialsException(
                message: 'Malformed Basic Auth header.',
                context: [
                    'request' => [
                        'method' => $request->getMethod(),
                        'uri' => $request->getRequestUri(),
                    ],
                ],
            );
        }

        return $decoded;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitDecodedCredentials(string $decoded): array
    {
        return explode(':', $decoded, 2);
    }

    private function assertCredentialsAreComplete(Request $request, string $username, string $password): void
    {
        if ($username === '' || $password === '') {
            $this->logger->info('Incomplete WebDAV Basic Auth credentials.', [
                'auth' => [
                    'username' => $username,
                ],
                'request' => [
                    'method' => $request->getMethod(),
                    'uri' => $request->getRequestUri(),
                ],
            ]);

            throw new InvalidCredentialsException(
                message: 'Incomplete Basic Auth credentials.',
                context: [
                    'request' => [
                        'method' => $request->getMethod(),
                        'uri' => $request->getRequestUri(),
                    ],
                ],
            );
        }
    }
}
