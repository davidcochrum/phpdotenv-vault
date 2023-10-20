<?php

namespace DotenvVault\Commands\Local;

use DotenvVault\Commands\Command;
use DotenvVault\DotEnvVaultError;
use DotenvVault\Services\Local\RotateKeyService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RotateKeyCommand extends Command
{
    use RequiresEnvVault;

    protected static $defaultName = 'local:rotatekey';
    protected static $defaultDescription = 'Rotate a locally managed DOTENV_KEY';

    protected function configure()
    {
        return $this->addArgument('environment', InputArgument::REQUIRED, 'Set environment to rotate')
            ->addYesOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->checkEnvVault();

            (new RotateKeyService(
                $input,
                $output,
                $this->io,
                $this->getHelper('question'),
                $input->getArgument('environment'),
                $this->getYesOption()
            ))->run();

            return self::SUCCESS;
        } catch (DotEnvVaultError $e) {
            $this->handleVaultError($e);
            return self::FAILURE;
        }
    }
}