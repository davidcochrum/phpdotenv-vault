<?php

namespace DotenvVault\Services;

abstract class AppendToIgnoreServiceBase
{
    abstract public function getIgnore(): string;

    abstract public function createIfMissing(): bool;

    public function getEnvFormat(): string
    {
        return '.env*'; // asterisk
    }

    public function getFlaskenvFormat(): string
    {
        return '.flaskenv*';
    }

    public function getEnvProjectFormat(): string
    {
        return '!.env.project';
    }

    public function getEnvVaultFormat(): string
    {
        return '!.env.vault';
    }

    public function missing(): bool
    {
        return !file_exists($this->getIgnore());
    }

    public function append(string $str): void
    {
        $resource = fopen($this->getIgnore(), 'a');
        fwrite($resource, $str);
        fclose($resource);
    }

    public function touch(): void
    {
        file_put_contents($this->getIgnore(), '');
    }

    public function read(): string
    {
        return file_get_contents($this->getIgnore());
    }

    public function run(): void
    {
        $envExists = false;
        $flaskenvExists = false;
        $envProjectExists = false;
        $envVaultExists = false;

        if ($this->missing()) {
            if ($this->createIfMissing()) {
                $this->touch();
            } else {
                return;
            }
        }

        // 2. iterate over dockerignore lines
        $lines = preg_split('/\r?\n/', $this->read());

        // 3. for each line check if ignore already exists
        foreach ($lines as $line) {
            $trimLine = trim($line);

            if ($trimLine === $this->getEnvFormat()) {
                $envExists = true;
            }

            if ($trimLine === $this->getFlaskenvFormat()) {
                $flaskenvExists = true;
            }

            if ($trimLine === $this->getEnvProjectFormat()) {
                $envProjectExists = true;
            }

            if ($trimLine === $this->getEnvVaultFormat()) {
                $envVaultExists = true;
            }
        }

        // 4. add ignore if it does not already exist
        if ($envExists === false) {
            $this->append("\n" . $this->getEnvFormat());
        }

        if ($flaskenvExists === false) {
            $this->append("\n" . $this->getFlaskenvFormat());
        }

        if ($envProjectExists === false) {
            $this->append("\n" . $this->getEnvProjectFormat());
        }

        if ($envVaultExists === false) {
            $this->append("\n" . $this->getEnvVaultFormat());
        }
    }
}