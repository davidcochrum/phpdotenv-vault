<?php

namespace DotenvVault\Services;

class AppendToVercelignoreService extends AppendToIgnoreServiceBase
{
    public function getIgnore(): string
    {
        return '.vercelignore';
    }

    public function createIfMissing(): bool
    {
        return false;
    }
}