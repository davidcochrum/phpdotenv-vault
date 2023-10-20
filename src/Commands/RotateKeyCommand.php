<?php

namespace DotenvVault\Commands;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Services\LoginService;
use DotenvVault\Services\RotateKeyService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RotateKeyCommand extends Command
{
    use RequiresEnvVault;

    protected static $defaultName = 'rotatekey';
    protected static $defaultDescription = 'Rotate a DOTENV_KEY';

    protected function configure()
    {
        return $this->addArgument('environment', InputArgument::REQUIRED, 'Set environment to rotate')
            ->addDotEnvMeOption()
            ->addYesOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->checkEnvVault();

            $questionHelper = $this->getHelper('question');
            $dotEnvMe = $this->checkEnvMe();
            $yes = $this->getYesOption();
            (new RotateKeyService(
                $input,
                $output,
                $this->io,
                $this->getHelper('question'),
                $input->getArgument('environment'),
                $yes,
                $dotEnvMe
            ))->run();

            return self::SUCCESS;
        } catch (DotEnvVaultError $e) {
            $this->handleVaultError($e);
            return self::FAILURE;
        }
    }
}