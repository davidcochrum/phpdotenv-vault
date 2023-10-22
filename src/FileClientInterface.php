<?php

namespace DotenvVault;

/** TODO descriptions */
interface FileClientInterface
{
    public function root(): string;

    public function exists(string $filename): bool;

    public function path(string $filename): string;

    public function read(string $filename): string;

    public function write(string $filename, string $contents): void;
}
