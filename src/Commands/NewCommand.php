<?php

namespace DotenvVault\Commands;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Services\NewService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewCommand extends Command
{
    protected static $defaultName = 'new';
    protected static $defaultDescription = 'Create your project';

    protected function configure()
    {
        return $this->addArgument('DOTENV_VAULT', InputArgument::OPTIONAL, 'Set .env.vault identifier. Defaults to generated value.', '')
            ->addYesOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            (new NewService(
                $input,
                $output,
                $this->io,
                $this->getHelper('question'),
                $this->browser,
                $this->httpClient,
                $this->fileClient,
                $input->getArgument('DOTENV_VAULT'),
                $this->getYesOption()
            ))->run();

            return self::SUCCESS;
        } catch (DotEnvVaultError $e) {
            $this->handleVaultError($e);
            return self::FAILURE;
        }
    }
}
