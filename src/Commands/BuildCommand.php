<?php

namespace DotenvVault\Commands;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Services\BuildService;
use DotenvVault\Services\LoginService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends Command
{
    use RequiresEnvVault;

    protected static $defaultName = 'build';
    protected static $defaultDescription = 'Build .env.vault';

    protected function configure()
    {
        $this->addDotEnvMeOption()
            ->addYesOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->checkEnvVault();
            $dotEnvMe = $this->getDotEnvMeOption();
            $loginSvc = new LoginService($this->input, $this->output, $this->io, $this->getHelper('question'), $this->httpClient, $dotEnvMe, $this->getYesOption());
            (new BuildService($this->io, $dotEnvMe))->run();
            return self::SUCCESS;
        } catch (DotEnvVaultError $e) {
            $this->handleVaultError($e);
            return self::FAILURE;
        }
    }
}
