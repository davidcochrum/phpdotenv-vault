<?php

namespace DotenvVault\Commands;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Services\WhoamiService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WhoamiCommand extends Command
{
    use RequiresEnvVault;

    protected static $defaultName = 'whoami';
    protected static $defaultDescription = 'Display the current logged in user';

    protected function configure()
    {
        return $this->addDotEnvMeOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->checkEnvVault();

            (new WhoamiService($this->io, $this->httpClient, $this->getDotEnvMeOption()))->run();

            return self::SUCCESS;
        } catch (DotEnvVaultError $e) {
            $this->handleVaultError($e);
            return self::FAILURE;
        }
    }
}
