<?php

namespace DotenvVault\Services;

class AppendToGitIgnoreService extends AppendToIgnoreServiceBase
{
    public function getIgnore(): string
    {
        return '.gitignore';
    }

    public function createIfMissing(): bool
    {
        return true;
    }
}