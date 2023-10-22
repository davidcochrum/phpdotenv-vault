<?php

namespace DotenvVault;

class LocalFileClient implements FileClientInterface
{
    /** @var string */
    private $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function root(): string
    {
        return $this->rootPath;
    }

    public function exists(string $filename): bool
    {
        return file_exists($this->path($filename));
    }

    /**
     * TODO
     */
    public function path(string $filename): string
    {
        return "{$this->rootPath}/{$filename}";
    }

    public function read(string $filename): string
    {
        return file_get_contents($this->path($filename));
    }

    public function write(string $filename, string $contents): void
    {
        file_put_contents($this->path($filename), $contents);
    }
}
