<?php

namespace DotenvVault\Services;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Vars;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
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
    /** @var LoginService */
    private $loginSvc;
    /** @var string|null */
    private $environment;
    /** @var bool */
    private $yes;
    /** @var string|null */
    private $dotEnvMe;

    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        QuestionHelper $questionHelper,
        string $environment,
        bool $yes,
        string $dotEnvMe = null
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->io = $io;
        $this->questionHelper = $questionHelper;
        $this->environment = $environment;
        $this->yes = $yes;
        $this->dotEnvMe = $dotEnvMe;
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
            $resp = (new Client)->post($this->getUrl(), ['json' => [
                'DOTENV_VAULT' => Vars::getVaultValue(),
                'DOTENV_ME' => $this->getMeUid(),
                'environment' => $this->environment,
            ]]);
            $data = json_decode($resp->getBody())->data;
            $this->io->writeln([
                $data->DOTENV_KEY,
                '',
                '1. Update DOTENV_KEY - comma-append the new value',
                '2. Rebuild (' . Vars::getCli() . ' build)',
                '3. Deploy (git push)',
                '4. Update DOTENV_KEY - remove the old value',
                '',
                'Example:',
                "DOTENV_KEY=\"{$data->PREVIOUS_DOTENV_KEY},{$data->DOTENV_KEY}\"",
            ]);
        } catch (BadResponseException $e) {
            throw DotEnvVaultError::fromApiResponse($e->getResponse()->getBody(), 'ROTATEKEY_ERROR');
        } catch (Throwable $e) {
            throw new DotEnvVaultError($e->getMessage(), 'ROTATEKEY_ERROR', [], $e->getCode(), $e);
        }
    }

    private function getUrl(): string
    {
        return Vars::getApiUrl() . '/rotatekey';
    }

    private function getMeUid(): string
    {
        return $this->dotEnvMe ?: Vars::getMeValue();
    }
}