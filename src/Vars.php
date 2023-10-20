<?php

namespace DotenvVault;

use Dotenv\Dotenv;

class Vars
{
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
        if (file_exists(getcwd() . '/.env.project')) {
            return '.env.project';
        }

        return '.env.vault';
    }

    public static function getVaultFilepath(): string
    {
        return getcwd() . '/' . self::getVaultFilename();
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
        if (!file_exists(self::getVaultFilepath())) {
            return [];
        }
        return Dotenv::parse(file_get_contents(self::getVaultFilepath()));
    }

    public static function getVaultValue(): string
    {
        return self::getVaultParsed()[self::getVaultKey()] ?? '';
    }

    public static function hasExistingEnvVault(): bool
    {
        return file_exists(self::getVaultFilepath());
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

    public static function getEnvMeFilepath(): string
    {
        return getcwd() . '/.env.me';
    }

    public static function isMissingEnvMe(string $dotenvMe = null): bool
    {
        if ($dotenvMe) { // it's not missing if dotenvMe is passed
            return false;
        }

        return !file_exists(self::getEnvMeFilepath());
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
        $parsed = Dotenv::parse(file_get_contents(self::getEnvMeFilepath()));
        return $parsed['DOTENV_ME'] ?? '';
    }

    public static function missingEnv(string $filename = '.env'): bool
    {
        return !file_exists(getcwd() . "/{$filename}");
    }

    public static function emptyEnv(string $filename = '.env'): bool
    {
        return self::isFileEmpty(getcwd() . "/{$filename}");
    }

    public static function getEnvKeysFilepath(): string
    {
        return getcwd() . '/.env.keys';
    }

    public static function generateDotenvKey(string $environment): string
    {
        $rand = bin2hex(openssl_random_pseudo_bytes(32));
        return "dotenv://:key_{$rand}@dotenv.local/vault/.env.vault?environment={$environment}";
    }

    public static function isMissingEnvKeys(): bool
    {
        return !file_exists(self::getEnvKeysFilepath());
    }

    public static function isEmptyEnvKeys(): bool
    {
        return self::isFileEmpty(self::getEnvKeysFilepath());
    }

    public static function parseEnvKeys(): array
    {
        if (self::isMissingEnvKeys()) {
            return [];
        }

        return Dotenv::parse(file_get_contents(self::getEnvKeysFilepath()));
    }

    private static function isFileEmpty(string $filepath): bool
    {
        if (!file_exists($filepath)) {
            return true;
        }

        $contents = file_get_contents($filepath);
        return $contents === false || strlen($contents) < 1;
    }
}