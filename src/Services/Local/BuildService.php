<?php

namespace DotenvVault\Services\Local;

use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\ArrayAdapter;
use DotenvVault\DotEnvVault;
use DotenvVault\DotEnvVaultError;
use DotenvVault\Services\AppendToIgnoreService;
use DotenvVault\Vars;
use Symfony\Component\Console\Output\OutputInterface;

define("ALGORITHM_NAME", "aes-256-gcm");
define("ALGORITHM_NONCE_SIZE", 12);
define("ALGORITHM_TAG_SIZE", 16);
define("ALGORITHM_KEY_SIZE", 16);
define("PBKDF2_NAME", "sha256");
define("PBKDF2_SALT_SIZE", 16);
define("PBKDF2_ITERATIONS", 32767);

class BuildService
{
    /** @var OutputInterface */
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function run(): void
    {
        (new AppendToIgnoreService)->run();

        $this->output->writeln('Building .env.vault from files on your machine');

        $this->build();
    }

    public function build(): void
    {
        file_put_contents($this->getKeysName(), $this->getKeysData());
        file_put_contents($this->getVaultName(), $this->getVaultData());

        $this->output->writeln([
            "Built {$this->getVaultName()}",
            '',
            'Next:',
            "1. Commit {$this->getVaultName()} to code",
            '2. Set DOTENV_KEY on server',
            '3. Deploy your code',
            '',
            "(Find your DOTENV_KEY in the <options=bold>{$this->getKeysName()}</> file)",
        ]);
    }

    public function getVaultData(): string
    {
        $s = Vars::getVaultFileHeaderComment() . "\n\n";

        foreach ($this->getEnvLookups() as $file => $environment) {
            $environment = strtoupper($environment);
            $dotenvKey = $this->getKeys()["DOTENV_KEY_{$environment}"];

            $message = file_get_contents($file);
            $key = $this->parseEncryptionKeyFromDotenvKey($dotenvKey);
            $ciphertext = $this->encrypt($key, $message);

            $s .= "# {$environment}\n";
            $s .= "DOTENV_VAULT_{$environment}=\"{$ciphertext}\"\n\n";
        }

        return $s;
    }

    public function getKeys(): array
    {
        $keys = [];
        // grab current .env.keys
        DotEnv::createImmutable(getcwd(), $this->getKeysName())->safeLoad();

        foreach ($this->getEnvLookups() as $environment) {
            $key = 'DOTENV_KEY_' . strtoupper($environment);

            // prevent overwriting current .env.keys data
            if (!($value = $_ENV[$key] ?? null)) {
                $value = Vars::generateDotenvKey($environment);
            }

            $keys[$key] = $value;
        }

        return $keys;
    }

    public function getKeysData(): string
    {
        $keysData = Vars::getKeysFileHeaderComment() . "\n";

        foreach ($this->getKeys() as $key => $value) {
            $keysData .= "{$key}=\"{$value}\"\n";
        }

        return $keysData;
    }

    public function getVaultName(): string
    {
        return '.env.vault';
    }

    public function getKeysName(): string
    {
        return '.env.keys';
    }

    public function getEnvLookups(): array
    {
        $dir = './';
        $lookups = [];

        $files = scandir($dir) ?: [];
        foreach ($files as $filepath) {
            $file = pathinfo($filepath, PATHINFO_BASENAME);
            // must be a .env* file
            if (0 !== stripos($file, '.env')) {
                continue;
            }

            // must not be .env.vault.something, or .env.me.something, etc.
            if ($this->reservedEnvFilePath($file)) {
                continue;
            }

            // must not end with .previous
            if (0 === substr_compare($file, '.previous', -9)) {
                continue;
            }

            $environment = $this->determineLikelyEnvironment($file);

            $lookups[$file] = $environment;
        }

        return $lookups;
    }

    private function reservedEnvFilePath(string $file): bool
    {
        $reservedEnvFiles = ['.env.vault', '.env.keys', '.env.me'];

        foreach ($reservedEnvFiles as $reservedFile) {
            $base = pathinfo($file, PATHINFO_BASENAME);
            if (0 === stripos($base, $reservedFile)) {
                return true;
            }
        }

        return false;
    }

    private function determineLikelyEnvironment(string $file): string
    {
        $splitFile = explode('.', $file);
        $possibleEnvironment = $splitFile[2] ?? ''; // ['', 'env', environment']

        if (strlen($possibleEnvironment) < 1) {
            return 'development';
        }

        return $possibleEnvironment;
    }

    private function parseEncryptionKeyFromDotenvKey(string $dotenvKey): string
    {
        // Parse decrypt key from DOTENV_KEY. Format is a URI
        $key = parse_url($dotenvKey, PHP_URL_PASS);
        if (!$key) {
            throw new DotEnvVaultError('INVALID_DOTENV_KEY: Missing key part');
        }

        return substr($key, -64);
    }

    private function encrypt(string $key, string $message): string
    {
        $cipher = 'aes-256-gcm';
        // set up nonce
        $nonce = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));

        $ciphertext = openssl_encrypt($message, $cipher, hex2bin($key),OPENSSL_RAW_DATA, $nonce,$tag);
        if (!$ciphertext) {
            throw new DotEnvVaultError("Unable to encrypt");
        }

        // base64 encode output
        return base64_encode($nonce . $ciphertext . $tag);
    }
}
