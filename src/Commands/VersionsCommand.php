<?php

namespace DotenvVault\Commands;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Services\LoginService;
use DotenvVault\Services\VersionsService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VersionsCommand extends Command
{
    use RequiresEnvVault;

    protected static $defaultName = 'versions';
    protected static $defaultDescription = 'List version history';

    protected function configure()
    {
        return $this->addArgument('environment', InputArgument::OPTIONAL, 'Set environment to check versions against. Defaults to development')
            ->addDotEnvMeOption()
            ->addYesOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->checkEnvVault();

            (new VersionsService($this->io, $input->getArgument('environment'), $this->checkEnvMe()))->run();

            return self::SUCCESS;
        } catch (DotEnvVaultError $e) {
            $this->handleVaultError($e);
            return self::FAILURE;
        }
    }
}