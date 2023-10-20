<?php

namespace DotenvVault\Commands;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Services\KeysService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KeysCommand extends Command
{
    use RequiresEnvVault;

    protected static $defaultName = 'keys';
    protected static $defaultDescription = 'List .env.vault decryption keys';

    protected function configure()
    {
        return $this->addArgument('environment', InputArgument::OPTIONAL, 'Set environment to fetch key(s) from. Defaults to all environments')
            ->addDotEnvMeOption()
            ->addYesOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->checkEnvVault();
            (new KeysService($this->io, $this->checkEnvMe(), $input->getArgument('environment')))->run();

            return self::SUCCESS;
        } catch (DotEnvVaultError $e) {
            $this->handleVaultError($e);
            return self::FAILURE;
        }
    }
}