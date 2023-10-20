<?php

namespace DotenvVault\Commands;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Services\LoginService;
use DotenvVault\Vars;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class Command extends \Symfony\Component\Console\Command\Command
{
    /** @var InputInterface */
    protected $input;
    /** @var OutputInterface */
    protected $output;
    /** @var SymfonyStyle */
    protected $io;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * Adds the input option for `DOTENV_ME` (overriding value from .env.me).
     */
    protected function addDotEnvMeOption(): self
    {
        return $this->addOption('DOTENV_ME', 'm', InputOption::VALUE_REQUIRED, 'Pass .env.me (DOTENV_ME) credential directly (rather than reading from .env.me file)', '');
    }

    /**
     * Retrieves the input value for `DOTENV_ME` (overriding value from .env.me).
     */
    protected function getDotEnvMeOption(): string
    {
        return $this->input->getOption('DOTENV_ME');
    }

    /**
     * Checks whether `.env.me` is available and not empty. If so, prompts user to login.
     * @return string DOTENV_ME value
     * @throws DotEnvVaultError When unable to confirm login.
     */
    public function checkEnvMe(): string
    {
        $dotEnvMe = $this->getDotEnvMeOption() ?: getenv('DOTENV_ME');
        $yes = $this->getYesOption();
        $loginService = new LoginService($this->input, $this->output, $this->io, $this->getHelper('question'), null, $yes);
        if (Vars::isMissingEnvMe($dotEnvMe)) {
            $loginService->login(false);
            return Vars::getMeValue();
        }
        if (Vars::emptyEnvMe($dotEnvMe)) {
            $loginService->login(false);
            return Vars::getMeValue();
        }

        return $dotEnvMe;
    }

    /**
     * Adds the input option for `yes` (skipping prompts).
     */
    protected function addYesOption(): self
    {
        return $this->addOption('yes', 'y', InputOption::VALUE_NONE, 'Automatic yes to prompts. Assume yes to all prompts and run non-interactively.');
    }

    /**
     * Retrieves the input value for `yes` (whether to skip prompts).
     */
    protected function getYesOption(): bool
    {
        return $this->input->getOption('yes');
    }

    protected function handleVaultError(DotEnvVaultError $error): void
    {
        $this->io->error($error->getMessage());
        $this->io->writeln($error->getSuggestions());
    }
}