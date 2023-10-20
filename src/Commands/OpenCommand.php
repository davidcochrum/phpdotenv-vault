<?php

namespace DotenvVault\Commands;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Services\OpenService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OpenCommand extends Command
{
    use RequiresEnvVault;

    protected static $defaultName = 'open';
    protected static $defaultDescription = 'Open project page';

    protected function configure()
    {
        return $this->addArgument('environment', InputArgument::OPTIONAL, 'Set environment to open to.', 'development')
            ->addYesOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->checkEnvVault();

            (new OpenService(
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