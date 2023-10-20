<?php

namespace DotenvVault\Services;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Vars;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class WhoamiService
{
    /** @var SymfonyStyle */
    private $io;
    /** @var bool */
    private $dotEnvMe;

    public function __construct(SymfonyStyle $io, string $dotEnvMe = null)
    {
        $this->io = $io;
        $this->dotEnvMe = $dotEnvMe;
    }

    /** @throws DotEnvVaultError */
    public function run(): void
    {
        if (Vars::isMissingEnvMe($this->dotEnvMe)) {
            throw new DotEnvVaultError('Missing .env.me (DOTENV_ME).', 'MISSING_DOTENV_ME', [
                'Run, <options=bold>' . Vars::getCli() . ' login</>',
            ]);
        }
        if (Vars::emptyEnvMe($this->dotEnvMe)) {
            throw new DotEnvVaultError('Empty .env.me (DOTENV_ME).', 'EMPTY_DOTENV_ME', [
                'Run, <options=bold>' . Vars::getCli() . ' login</>',
            ]);
        }

        $this->whoami();
    }

    /** @throws DotEnvVaultError */
    public function whoami(): void
    {
        try {
            $resp = (new Client)->post($this->getUrl(), ['json' => [
                'DOTENV_ME' => $this->getMeUid(),
                'DOTENV_VAULT' => Vars::getVaultValue(),
            ]]);
            $data = json_decode($resp->getBody())->data;
            $this->io->writeln($data->email);
        } catch (BadResponseException $e) {
            throw DotEnvVaultError::fromApiResponse($e->getResponse()->getBody(), 'WHOAMI_ERROR');
        } catch (Throwable $e) {
            throw new DotEnvVaultError($e->getMessage(), 'WHOAMI_ERROR', [], $e->getCode(), $e);
        }
    }

    private function getUrl(): string
    {
        return Vars::getApiUrl() . '/whoami';
    }

    private function getMeUid(): string
    {
        return $this->dotEnvMe ?: Vars::getMeValue();
    }
}