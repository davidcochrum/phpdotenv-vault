<?php

namespace DotenvVault\Commands;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Services\PullService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PullCommand extends Command
{
    use RequiresEnvVault;

    protected static $defaultName = 'pull';
    protected static $defaultDescription = 'Pull .env securely';

    protected function configure()
    {
        return $this->addArgument('environment', InputArgument::OPTIONAL, 'Set environment to pull from. Defaults to development')
            ->addArgument('filename', InputArgument::OPTIONAL, 'Set output filename. Defaults to .env for development and .env.{environment} for other environments')
            ->addDotEnvMeOption()
            ->addYesOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->checkEnvVault();

            $dotEnvMe = null;
            $environment = $input->getArgument('environment');
            // special case for pulling example - no auth needed
            if ('example' !== $environment) {
                $dotEnvMe = $this->checkEnvMe();
            }

            (new PullService($this->io, $dotEnvMe, $environment, $input->getArgument('filename')))->run();

            return self::SUCCESS;
        } catch (DotEnvVaultError $e) {
            $this->handleVaultError($e);
            return self::FAILURE;
        }
    }
}