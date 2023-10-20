<?php

namespace DotenvVault\Services;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Vars;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class PullService
{
    /** @var SymfonyStyle */
    private $io;
    /** @var string|null */
    private $dotEnvMe;
    /** @var string|null */
    private $environment;
    /** @var string|null */
    private $filename;

    public function __construct(SymfonyStyle $io, string $dotEnvMe = null, string $environment = null, string $filename = null)
    {
        $this->io = $io;
        $this->dotEnvMe = $dotEnvMe;
        $this->environment = $environment;
        $this->filename = $filename;
    }

    /** @throws DotEnvVaultError */
    public function run(): void
    {
        (new AppendToIgnoreService)->run();

        // special case for pulling example - no auth needed
        if ($this->environment === 'example') {
            $this->pull();
            return;
        }

        $this->io->writeln('Securely pulling' . ($this->environment ? " {$this->environment}" : ''));

        $this->pull();
    }

    /** @throws DotEnvVaultError */
    public function pull(): void
    {
        try {
            $resp = (new Client)->post($this->getUrl(), ['json' => [
                'environment' => $this->environment,
                'projectUid' => Vars::getVaultValue(),
                'meUid' => $this->getMeUid(),
            ]]);
            $data = json_decode($resp->getBody())->data;

            $outputFilename = $this->getDisplayFilename($data->envName);
            $outputFilepath = getcwd() . "/{$outputFilename}";
            // backup current file to .previous
            if (file_exists($outputFilepath)) {
                rename($outputFilepath, "{$outputFilepath}.previous");
            }

            // write to new current file
            file_put_contents($outputFilepath, $data->dotenv);
            $this->io->writeln("Securely pulled {$data->environment} ({$outputFilename})");
            // write .env.vault file
            if ($data->dotenvVault ?? null) {
                $vault = preg_replace('/(?<=DOTENV_CLI=).*/', '"' . Vars::getCli() . '"', $data->dotenvVault);
                file_put_contents(getcwd() . '/.env.vault', $vault);
                $this->io->writeln('Securely built vault (.env.vault)');
            }
        } catch (BadResponseException $e) {
            DotEnvVaultError::fromApiResponse($e->getResponse()->getBody(), 'PULL_ERROR');
        } catch (Throwable $e) {
            throw new DotEnvVaultError($e->getMessage(), 'PULL_ERROR', [], $e->getCode(), $e);
        }
    }

    private function getUrl(): string
    {
        return Vars::getApiUrl() . '/pull';
    }

    private function getSmartEnvironment()
    {
        // 1. if user has set an environment for input then use that
        if ($this->environment) {
            return $this->environment;
        }

        return null; // otherwise, do not pass environment. dotenv-vault's api will smartly choose the main environment for the project (in most cases development)
    }

    private function getMeUid(): string
    {
        return $this->dotEnvMe ?: Vars::getMeValue();
    }

    private function getDisplayFilename(string $envName): string
    {
        // if user has set a filename for output then use that else use envName
        if ($this->filename) {
            return $this->filename;
        }

        return $envName;
    }
}