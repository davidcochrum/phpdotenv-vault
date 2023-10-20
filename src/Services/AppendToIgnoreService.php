<?php

namespace DotenvVault\Services;

class AppendToIgnoreService
{
    public static function run()
    {
        (new AppendToDockerignoreService)->run();
        (new AppendToGitignoreService)->run();
        (new AppendToNpmignoreService)->run();
        (new AppendToVercelignoreService)->run();
    }
}