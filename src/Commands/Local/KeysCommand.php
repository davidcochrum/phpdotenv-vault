<?php

namespace DotenvVault\Commands\Local;

use DotenvVault\Services\Local\KeysService;
use DotenvVault\Vars;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class KeysCommand extends Command
{
    protected static $defaultName = 'local:keys';
    protected static $defaultDescription = 'List .env.vault local decryption keys from .env.keys file';

    protected function configure()
    {
        return $this->addArgument('environment', InputArgument::OPTIONAL, 'Set environment to fetch key(s) from. Defaults to all environments');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if (Vars::isMissingEnvVault()) {
            $io->error(Vars::getVaultFilepath() . ' file is missing');
            return Command::FAILURE;
        }
        if (Vars::isEmptyEnvKeys()) {
            $io->error(Vars::getEnvKeysFilepath() . ' file is empty');
            return Command::FAILURE;
        }
        if (Vars::isMissingEnvKeys()) {
            $io->error(Vars::getEnvKeysFilepath() . ' file is missing');
            return Command::FAILURE;
        }
        if (Vars::isEmptyEnvKeys()) {
            $io->error(Vars::getEnvKeysFilepath() . ' file is empty');
            return Command::FAILURE;
        }

        (new KeysService($io, $input->getArgument('environment')))->run();

        return Command::SUCCESS;
    }
}