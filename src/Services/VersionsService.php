<?php

namespace DotenvVault\Services;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Vars;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class VersionsService
{
    /** @var SymfonyStyle */
    private $io;
    /** @var string|null */
    private $environment;
    /** @var bool */
    private $dotEnvMe;

    public function __construct(SymfonyStyle $io, string $environment = null, string $dotEnvMe = null)
    {
        $this->io = $io;
        $this->environment = $environment;
        $this->dotEnvMe = $dotEnvMe;
    }

    /** @throws DotEnvVaultError */
    public function run(): void
    {
        $environment = $this->getSmartEnvironment();
        $this->io->writeln("Listing" . ($environment ? " {$environment}" : "") . " versions");
        $this->versions();
    }

    /** @throws DotEnvVaultError */
    public function versions(): void
    {
        try {
            $resp = (new Client)->post($this->getUrl(), ['json' => [
                'environment' => $this->getSmartEnvironment(),
                'projectUid' => Vars::getVaultValue(),
                'meUid' => $this->getMeUid(),
            ]]);
            $data = json_decode($resp->getBody())->data;
            $this->io->table(
                ['Ver', 'Change', 'By', 'When'],
                array_map(
                    function ($row) {
                        return [$row->version, $row->change, $row->by, $row->when];
                    },
                    $data->versions ?? []
                )
            );
            $this->io->writeln([
                "",
                "Pull a version with <options=bold>" . Vars::getCli() . " pull {$data->environment}@" . ($data->versions[0]->version ?? "<version>") . "</>",
            ]);
        } catch (BadResponseException $e) {
            throw DotEnvVaultError::fromApiResponse($e->getResponse()->getBody(), 'VERSIONS_ERROR');
        } catch (Throwable $e) {
            throw new DotEnvVaultError($e->getMessage(), 'VERSIONS_ERROR', [], $e->getCode(), $e);
        }
    }

    private function getUrl(): string
    {
        return Vars::getApiUrl() . '/versions';
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
}