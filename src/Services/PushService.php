<?php

namespace DotenvVault\Services;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Vars;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class PushService
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

        $filename = $this->getSmartFilename();
        if (Vars::missingEnv($filename)) {
            throw new Exception("Missing {$filename}");
        }
        if (Vars::emptyEnv($filename)) {
            throw new Exception("Empty {$filename}");
        }

        if ($environment = $this->getSmartEnvironment()) {
            $this->io->writeln("Securely pushing {$environment} ({$filename})");
        } else {
            $this->io->writeln("Securely pushing ({$filename})");
        }
        $this->push();
    }

    /** @throws DotEnvVaultError */
    public function push(): void
    {
        try {
            $resp = (new Client)->post($this->getUrl(), ['json' => [
                'environment' => $this->getSmartEnvironment(),
                'projectUid' => Vars::getVaultValue(),
                'meUid' => $this->getMeUid(),
                'dotenv' => $this->getEnvContent(),
            ]]);
            $data = json_decode($resp->getBody())->data;
            $this->io->writeln("Securely pushed {$data->environment} ({$this->getDisplayFilename($data->envName)})");
            // write .env.vault file
            if ($data->dotenvVault) {
                $vault = preg_replace('/(?<=DOTENV_CLI=).*/', '"' . Vars::getCli() . '"', $data->dotenvVault);
                file_put_contents(getcwd() . '/.env.vault', $vault);
                $this->io->writeln('Securely built vault (.env.vault)');
            }
            $this->io->writeln([
                '',
                'Run <options=bold>' . Vars::getCli() . ' open</> to view in the ui',
            ]);
        } catch (BadResponseException $e) {
            throw DotEnvVaultError::fromApiResponse($e->getResponse()->getBody(), 'PUSH_ERROR');
        } catch (Throwable $e) {
            throw new DotEnvVaultError($e->getMessage(), 'PUSH_ERROR', [], $e->getCode(), $e);
        }
    }

    private function getUrl(): string
    {
        return Vars::getApiUrl() . '/push';
    }

    public function getEnvContent(): string
    {
        return file_get_contents(getcwd() . "/{$this->getSmartFilename()}");
    }

    private function getSmartEnvironment()
    {
        // 1. if user has set an environment for input then use that
        if ($this->environment) {
            return $this->environment;
        }

        return null; // otherwise, do not pass environment. dotenv-vault's api will smartly choose the main environment for the project (in most cases development)
    }

    private function getSmartFilename(): string
    {
        // if user has set a filename for input then use that
        if ($this->filename) {
            return $this->filename;
        }

        if ($this->getSmartEnvironment()) {
            // in case of development being passed and .env.development file does not exist, then return .env. this covers use cases of custom environments like local (main), development, and production
            if ($this->getSmartEnvironment() === 'development' && !file_exists(getcwd() . '/.env.development')) {
                return '.env';
            }

            return ".env.{$this->getSmartEnvironment()}";
        }

        return '.env';
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