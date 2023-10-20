<?php

namespace DotenvVault\Commands\Local;

use DotenvVault\Commands\Command;
use DotenvVault\Services\Local\BuildService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends Command
{
    protected static $defaultName = 'local:build';
    protected static $defaultDescription = 'Build .env.vault from local only';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new BuildService($output))->run();
        return self::SUCCESS;
    }
}