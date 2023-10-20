<?php

namespace DotenvVault\Commands;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Services\PushService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PushCommand extends Command
{
    use RequiresEnvVault;

    protected static $defaultName = 'push';
    protected static $defaultDescription = 'Push .env securely';

    protected function configure()
    {
        return $this->addArgument('environment', InputArgument::OPTIONAL, 'Set environment to push from. Defaults to development')
            ->addArgument('filename', InputArgument::OPTIONAL, 'Set input filename. Defaults to .env for development and .env.{environment} for other environments')
            ->addDotEnvMeOption()
            ->addYesOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->checkEnvVault();
            $dotEnvMe = $this->checkEnvMe();

            (new PushService($this->io, $dotEnvMe, $input->getArgument('environment'), $input->getArgument('filename')))->run();

            return self::SUCCESS;
        } catch (DotEnvVaultError $e) {
            $this->handleVaultError($e);
            return self::FAILURE;
        }
    }
}