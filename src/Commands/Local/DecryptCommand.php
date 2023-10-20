<?php

namespace DotenvVault\Commands\Local;

use DotenvVault\Commands\Command;
use DotenvVault\DotEnvVaultError;
use DotenvVault\Services\Local\DecryptService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DecryptCommand extends Command
{
    protected static $defaultName = 'local:decrypt';
    protected static $defaultDescription = 'Decrypt .env.vault from local only';

    protected function configure()
    {
        $this->addArgument('key', InputArgument::REQUIRED, 'Decryption key');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            (new DecryptService($input, $output, $input->getArgument('key')))->run();
            return self::SUCCESS;
        } catch (DotEnvVaultError $e) {
            $this->handleVaultError($e);
            return self::FAILURE;
        }
    }
}