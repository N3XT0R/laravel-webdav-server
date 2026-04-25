<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Request\Auth;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestCredentialsExtractorInterface;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\MissingCredentialsException;

final readonly class RequestBasicCredentialsExtractor implements RequestCredentialsExtractorInterface
{
    /**
     * Extract Basic Auth credentials from either PHP auth server values or the `Authorization` header.
     *
     * @param \Illuminate\Http\Request $request Incoming HTTP request targeting the WebDAV endpoint.
     *
     * @throws \N3XT0R\LaravelWebdavServer\Exception\Auth\MissingCredentialsException When no usable Basic Auth credentials are present.
     * @throws \N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException When the Basic Auth payload is malformed or incomplete.
     *
     * @return array{0:string,1:string}
     * Tuple of `[username, password]` extracted from the request.
     */
    public function extract(Request $request): array
    {
        $serverCredentials = $this->extractServerCredentials($request);

        if ($serverCredentials !== null) {
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

        return [$username, $password];
    }

    private function extractAuthorizationHeader(Request $request): string
    {
        $authorization = $request->headers->get('Authorization');

        if (! is_string($authorization) || ! str_starts_with($authorization, 'Basic ')) {
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
