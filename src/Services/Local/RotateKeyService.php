<?php

namespace DotenvVault\Services\Local;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Vars;
use Exception;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class RotateKeyService
{
    /** @var InputInterface */
    private $input;
    /** @var OutputInterface */
    private $output;
    /** @var SymfonyStyle */
    private $io;
    /** @var QuestionHelper */
    private $questionHelper;
    /** @var string|null */
    private $environment;
    /** @var bool */
    private $yes;

    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        QuestionHelper $questionHelper,
        string $environment,
        bool $yes
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->io = $io;
        $this->questionHelper = $questionHelper;
        $this->environment = $environment;
        $this->yes = $yes;
    }

    /** @throws DotEnvVaultError */
    public function run(): void
    {
        if (!$this->yes) {
            $question = new ConfirmationQuestion("Are you sure you want to rotate your {$this->environment} DOTENV_KEY? Type yes to continue: ", false, '/^yes/i');
            if (!$this->questionHelper->ask($this->input, $this->output, $question)) {
                $this->io->error('Aborted');
                return;
            }
        }

        $this->io->writeln('Rotating decryption key');
        $this->rotateKey();
    }

    /** @throws DotEnvVaultError */
    public function rotateKey(): void
    {
        try {
            $environmentKeyName = "DOTENV_KEY_" . strtoupper($this->environment);
            if (!($previousKey = Vars::parseEnvKeys()[$environmentKeyName] ?? null)) {
                throw new DotEnvVaultError("Key does not exist for environment: {$this->environment}", 'ROTATEKEY_ERROR');
            }
            $newKey = Vars::generateDotenvKey($this->environment);
            $vault = file_get_contents(Vars::getVaultFilepath());
            $vault = preg_replace("/^(?<={$environmentKeyName}=).*$/", "\"{$newKey}\"", $vault);
            file_put_contents(Vars::getVaultFilepath(), $vault);

            $this->io->writeln([
                $newKey,
                '',
                '1. Update DOTENV_KEY - comma-append the new value',
                '2. Rebuild (' . Vars::getCli() . ' local:build)',
                '3. Deploy (git push)',
                '4. Update DOTENV_KEY - remove the old value',
                '',
                'Example:',
                "DOTENV_KEY=\"{$previousKey},{$newKey}\"",
            ]);
        } catch (DotEnvVaultError $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new DotEnvVaultError($e->getMessage(), 'ROTATEKEY_ERROR', [], $e->getCode(), $e);
        }
    }
}