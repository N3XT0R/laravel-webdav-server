<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Filesystem;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use InvalidArgumentException;

final class RecordingFilesystemManager implements FilesystemFactory
{
    /** @var list<string> */
    public array $requestedDisks = [];

    public function __construct(
        private readonly Filesystem $filesystem,
    ) {}

    public function disk($name = null)
    {
        $this->requestedDisks[] = (string) $name;

        return $this->filesystem;
    }

    public function cloud()
    {
        return $this->disk('cloud');
    }

    public function build($config)
    {
        throw new InvalidArgumentException('Not implemented for tests.');
    }
}
