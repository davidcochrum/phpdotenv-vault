<?php

namespace DotenvVault\Services;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Vars;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class BuildService
{
    /** @var SymfonyStyle */
    private $io;
    /** @var string */
    private $dotEnvMe;

    public function __construct(SymfonyStyle $io, string $dotEnvMe)
    {
        $this->io = $io;
        $this->dotEnvMe = $dotEnvMe;
    }

    /** @throws DotEnvVaultError */
    public function run()
    {
        (new AppendToIgnoreService)->run();

        $this->io->writeln('Securely building .env.vault');

        $this->build();
    }

    /** @throws DotEnvVaultError */
    public function build(): void
    {
        try {
            $resp = (new Client)->post($this->getUrl(), ['json' => [
                'DOTENV_VAULT' => Vars::getVaultValue(),
                'DOTENV_ME' => $this->getMeUid(),
            ]]);
            $data = json_decode($resp->getBody())->data;

            // write to .env.vault
            $dotenv = preg_replace('/(?<=DOTENV_CLI=).*/', '"' . Vars::getCli() . '"', $data->dotenv);
            file_put_contents(getcwd() . "/{$data->envName}", $dotenv);
            $this->io->writeln([
                'Securely built .env.vault',
                '',
                'Next:',
                '1. Commit .env.vault to code',
                '2. Set DOTENV_KEY on server',
                '3. Deploy your code',
                '',
                '(run <options=bold>' . Vars::getCli() . ' keys</> to view DOTENV_KEYs)',
            ]);
        } catch (BadResponseException $e) {
            throw DotEnvVaultError::fromApiResponse($e->getResponse()->getBody(), 'BUILD_ERROR');
        } catch (Throwable $e) {
            throw new DotEnvVaultError($e->getMessage(), 'BUILD_ERROR', [], $e->getCode(), $e);
        }
    }

    private function getUrl(): string
    {
        return Vars::getApiUrl() . '/build';
    }

    private function getMeUid(): string
    {
        return $this->dotEnvMe ?: Vars::getMeValue();
    }
}