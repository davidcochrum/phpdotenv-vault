<?php

namespace DotenvVault;

use Dotenv\Dotenv;

class Vars
{
    /** @var LocalFileClient|null */
    private static $fileClient = null;

    public static function getFileClient(): FileClientInterface
    {
        if (!self::$fileClient) {
            self::$fileClient = new LocalFileClient(getcwd());
        }
        return self::$fileClient;
    }

    public static function setFileClient(FileClientInterface $fileClient): void
    {
        self::$fileClient = $fileClient;
    }

    public static function getCli(): string
    {
        // read from process.env first, then .env.vault settings, then default
        return getenv('DOTENV_CLI') ?: self::getVaultParsed()['DOTENV_CLI'] ?? 'vendor/bin/dotenv-vault';
    }

    public static function getApiUrl(): string
    {
        // read from process.env first, then .env.vault settings, then default
        return getenv('DOTENV_API_URL') ?: self::getVaultParsed()['DOTENV_API_URL'] ?? 'https://vault.dotenv.org';
    }

    public static function buildApiActionUrl(string $action, array $params = []): string
    {
        $params = array_merge(['DOTENV_VAULT' => self::getVaultValue()], $params);
        return self::getApiUrl() . "/{$action}?" . http_build_query($params);
    }

    public static function generateRequestUid(): string
    {
        return 'req_' . bin2hex(random_bytes(32));
    }

    public static function getVaultFileHeaderComment(): string
    {
        return <<<STR
#/-------------------.env.vault---------------------/
#/         cloud-agnostic vaulting standard         /
#/   [how it works](https://dotenv.org/env-vault)   /
#/--------------------------------------------------/
STR;
    }

    public static function getMeFileHeaderComment(): string
    {
        return <<<STR
#/!!!!!!!!!!!!!!!!!!!!.env.me!!!!!!!!!!!!!!!!!!!!!!!/
#/ credential file. DO NOT commit to source control /
#/    [how it works](https://dotenv.org/env-me)     /
#/--------------------------------------------------/
STR;
    }

    public static function getKeysFileHeaderComment(): string
    {
        return <<<STR
#/!!!!!!!!!!!!!!!!!!!.env.keys!!!!!!!!!!!!!!!!!!!!!!/
#/   DOTENV_KEYs. DO NOT commit to source control   /
#/   [how it works](https://dotenv.org/env-keys)    /
#/--------------------------------------------------/
STR;
    }

    public static function getVaultFilename(): string
    {
        // if .env.project (old) file exists use it. otherwise use .env.vault
        if (self::getFileClient()->exists('.env.project')) {
            return '.env.project';
        }

        return '.env.vault';
    }

    public static function getVaultFilepath(): string
    {
        return self::getFileClient()->path(self::getVaultFilename());
    }

    public static function getVaultKey(): string
    {
        if (self::getVaultFilename() === '.env.project') {
            return 'DOTENV_PROJECT';
        }

        return 'DOTENV_VAULT';
    }

    public static function getVaultParsed(): array
    {
        if (!self::getFileClient()->exists(self::getVaultFilename())) {
            return [];
        }
        return Dotenv::parse(self::getFileClient()->read(self::getVaultFilename()));
    }

    public static function getVaultValue(): string
    {
        return self::getVaultParsed()[self::getVaultKey()] ?? '';
    }

    public static function hasExistingEnvVault(): bool
    {
        return self::getFileClient()->exists(self::getVaultFilename());
    }

    public static function isMissingEnvVault(): bool
    {
        return !self::hasExistingEnvVault();
    }

    public static function isEmptyEnvVault(): bool
    {
        return strlen(self::getVaultValue()) < 1;
    }

    public static function hasExistingVaultValue(): bool
    {
        return strlen(self::getVaultValue()) === 68;
    }

    public static function invalidVaultValue(string $identifier): bool
    {
        return strlen($identifier) !== 68;
    }

    public static function invalidMeValue(string $credential): bool
    {
        return strlen($credential) !== 67;
    }

    public static function getEnvMeFilename(): string
    {
        return '.env.me';
    }

    public static function getEnvMeFilepath(): string
    {
        return self::getFileClient()->path(self::getEnvMeFilename());
    }

    public static function isMissingEnvMe(string $dotenvMe = null): bool
    {
        if ($dotenvMe) { // it's not missing if dotenvMe is passed
            return false;
        }

        return !self::getFileClient()->exists(self::getEnvMeFilename());
    }

    public static function emptyEnvMe(string $dotenvMe): bool
    {
        if ($dotenvMe) {
            return false;
        }

        return strlen(self::getMeValue()) < 1;
    }

    public static function getMeValue(): string
    {
        if (self::isMissingEnvMe()) {
            return '';
        }
        $parsed = Dotenv::parse(self::getFileClient()->read(self::getEnvMeFilename()));
        return $parsed['DOTENV_ME'] ?? '';
    }

    public static function missingEnv(string $filename = '.env'): bool
    {
        return !self::getFileClient()->exists($filename);
    }

    public static function emptyEnv(string $filename = '.env'): bool
    {
        return self::isFileEmpty($filename);
    }

    public static function getEnvKeysFilename(): string
    {
        return '.env.keys';
    }

    public static function getEnvKeysFilepath(): string
    {
        return self::getFileClient()->path(self::getEnvKeysFilename());
    }

    public static function generateDotenvKey(string $environment): string
    {
        $rand = bin2hex(openssl_random_pseudo_bytes(32));
        return "dotenv://:key_{$rand}@dotenv.local/vault/.env.vault?environment={$environment}";
    }

    public static function isMissingEnvKeys(): bool
    {
        return !self::getFileClient()->exists(self::getEnvKeysFilename());
    }

    public static function isEmptyEnvKeys(): bool
    {
        return self::isFileEmpty(self::getEnvKeysFilename());
    }

    public static function parseEnvKeys(): array
    {
        if (self::isMissingEnvKeys()) {
            return [];
        }

        return Dotenv::parse(self::getFileClient()->read(self::getEnvKeysFilename()));
    }

    private static function isFileEmpty(string $filename): bool
    {
        if (!self::getFileClient()->exists($filename)) {
            return true;
        }

        $contents = self::getFileClient()->read($filename);
        return strlen($contents) < 1;
    }
}
