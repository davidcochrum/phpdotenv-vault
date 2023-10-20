<?php

namespace DotenvVault\Commands;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Services\LoginService;
use DotenvVault\Vars;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoginCommand extends Command
{
    use RequiresEnvVault;

    protected static $defaultName = 'login';
    protected static $defaultDescription = 'Log in';

    protected function configure()
    {
        return $this->addDotEnvMeOption()
            ->addYesOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->checkEnvVault();

            $dotEnvMe = $this->getDotEnvMeOption();
            if ($dotEnvMe && Vars::invalidMeValue($dotEnvMe)) {
                $this->io->error('Invalid .env.me (DOTENV_ME).');
                return self::FAILURE;
            }

            (new LoginService(
                $input,
                $output,
                $this->io,
                $this->getHelper('question'),
                $dotEnvMe,
                $this->getYesOption()
            ))->run();

            return self::SUCCESS;
        } catch (DotEnvVaultError $e) {
            $this->handleVaultError($e);
            return self::FAILURE;
        }
    }
}