<?php

namespace DotenvVault\Services;

use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;
use DotenvVault\DotEnvVault;
use DotenvVault\DotEnvVaultError;
use DotenvVault\Vars;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DecryptService
{
    /** @var SymfonyStyle */
    private $io;
    /** @var string */
    private $dotenvKey;

    public function __construct(InputInterface $input, OutputInterface $output, string $dotenvKey)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->dotenvKey = $dotenvKey;
    }

    /** @throws DotEnvVaultError */
    public function run()
    {
        $arrayAdapter = ArrayAdapter::create()->get();
        $repository = RepositoryBuilder::createWithNoAdapters()
            ->addAdapter(EnvConstAdapter::class)
            ->addAdapter(PutenvAdapter::class)
            ->addAdapter($arrayAdapter)
            ->make();
        putenv("DOTENV_KEY={$this->dotenvKey}");
        try {
            DotEnvVault::create($repository, getcwd(), $this->getVaultPath())->load();
        } catch (DotEnvVaultError $error) {
            $error->addSuggestion("Run '" . Vars::getCli() . " {$this->getCommand()}' to include it.");
            throw $error;
        }

        $rows = [];
        foreach ($_ENV as $key => $value) {
            if ($arrayAdapter->read($key)->isDefined()) {
                $rows[] = [$key, $value];
            }
        }
        $this->io->table(['Key', 'Value'], $rows);
    }

    public function getVaultPath(): string
    {
        return '.env.vault';
    }

    public function getCommand(): string
    {
        return 'build';
    }
}