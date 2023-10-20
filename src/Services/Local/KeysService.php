<?php

namespace DotenvVault\Services\Local;

use DotenvVault\Services\AppendToIgnoreService;
use DotenvVault\Vars;
use Symfony\Component\Console\Style\SymfonyStyle;

class KeysService
{
    /** @var SymfonyStyle */
    private $io;
    /** @var string|null */
    private $environment;

    public function __construct(SymfonyStyle $io, string $environment = null)
    {
        $this->io = $io;
        $this->environment = $environment;
    }

    public function run(): void
    {
        (new AppendToIgnoreService)->run();

        $this->io->section('Listing .env.vault decryption keys');

        $parsed = Vars::parseEnvKeys();
        $rows = [];
        $environmentKey = '';
        foreach ($parsed as $name => $value) {
            $environment = strtolower(str_replace('DOTENV_KEY_', '', $name));
            $rows[] = [$environment, $value];
            if ($this->environment && $this->environment === $environment) {
                $environmentKey = $value;
            }
        }

        if ($this->environment) {
            $this->io->writeln($environmentKey);
            return;
        }

        $this->io->table(['environment', 'DOTENV_KEY'], $rows);
    }
}