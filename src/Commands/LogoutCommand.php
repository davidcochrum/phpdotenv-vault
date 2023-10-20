<?php

namespace DotenvVault\Commands;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Services\LogoutService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LogoutCommand extends Command
{
    protected static $defaultName = 'logout';
    protected static $defaultDescription = 'Log out';

    protected function configure()
    {
        return $this->addYesOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            (new LogoutService(
                $input,
                $output,
                $this->io,
                $this->getHelper('question'),
                $this->getYesOption()
            ))->run();

            return self::SUCCESS;
        } catch (DotEnvVaultError $e) {
            $this->handleVaultError($e);
            return self::FAILURE;
        }
    }
}