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
     * @return array{0:string,1:string}
     */
    public function extract(Request $request): array
    {
        $username = $request->getUser();
        $password = $request->getPassword();

        if (is_string($username) && is_string($password)) {
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

            return [$username, $password];
        }

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

        [$username, $password] = explode(':', $decoded, 2);

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

        return [$username, $password];
    }
}
