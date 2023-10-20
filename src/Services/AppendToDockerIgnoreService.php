<?php

namespace DotenvVault\Services;

class AppendToDockerIgnoreService extends AppendToIgnoreServiceBase
{
    public function getIgnore(): string
    {
        return '.dockerignore';
    }

    public function createIfMissing(): bool
    {
        return false;
    }
}