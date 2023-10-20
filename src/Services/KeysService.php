<?php

namespace DotenvVault\Services;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Vars;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class KeysService
{
    /** @var SymfonyStyle */
    private $io;
    /** @var string|null */
    private $environment;
    /** @var LoginService */
    private $dotenvMe;

    public function __construct(SymfonyStyle $io, string $dotenvMe = null, string $environment = null)
    {
        $this->io = $io;
        $this->dotenvMe = $dotenvMe;
        $this->environment = $environment;
    }

    /** @throws DotEnvVaultError */
    public function run(): void
    {
        (new AppendToIgnoreService)->run();

        $this->io->writeln('Listing .env.vault decryption keys');
        $this->keys();
    }

    /** @throws DotEnvVaultError */
    public function keys(): void
    {
        try {
            $resp = (new Client)->post(Vars::getApiUrl() . '/keys', ['json' => array_filter([
                'DOTENV_VAULT' => Vars::getVaultValue(),
                'DOTENV_ME' => $this->getMeUid(),
                'environment' => $this->environment,
            ])]);
            $data = json_decode($resp->getBody())->data;

            if ($this->environment && isset($data->keys[0])) {
                $this->io->writeln($data->keys[0]->key);
            } else {
                $this->io->table(
                    ['environment', 'DOTENV_KEY'],
                    array_map(
                        function ($key) {
                            return [$key->environment, $key->key];
                        },
                        $data->keys
                    )
                );
            }
            $this->io->writeln([
                '',
                'Set <options=bold>DOTENV_KEY</> on your server',
            ]);
        } catch (BadResponseException $e) {
            throw DotEnvVaultError::fromApiResponse($e->getResponse()->getBody(), 'KEYS_ERROR');
        } catch (Throwable $e) {
            throw new DotEnvVaultError($e->getMessage(), 'KEYS_ERROR', [], $e->getCode(), $e);
        }
    }

    private function getMeUid(): string
    {
        return $this->dotenvMe ?: Vars::getMeValue();
    }
}