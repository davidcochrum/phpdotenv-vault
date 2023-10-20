<?php

namespace DotenvVault\Services;

class AppendToNpmignoreService extends AppendToIgnoreServiceBase
{
    public function getIgnore(): string
    {
        return '.npmignore';
    }

    public function createIfMissing(): bool
    {
        return false;
    }
}