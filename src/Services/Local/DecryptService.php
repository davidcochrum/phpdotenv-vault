<?php

namespace DotenvVault\Services\Local;

class DecryptService extends \DotenvVault\Services\DecryptService
{
    public function getCommand(): string
    {
        return 'local:build';
    }
}