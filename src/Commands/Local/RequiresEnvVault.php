<?php

namespace DotenvVault\Commands\Local;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Vars;

/**
 * Trait to apply to local management commands which require a populated `.env.vault` file.
 */
trait RequiresEnvVault
{
    /**
     * Checks whether `.env.vault` is available and not empty.
     * @throws DotEnvVaultError
     */
    public function checkEnvVault(): void
    {
        if (Vars::isMissingEnvVault()) {
            throw new DotEnvVaultError(
                'Missing ' . Vars::getVaultFilepath(),
                'MISSING_DOTENV_VAULT',
                ['Run, <options=bold>' . Vars::getCli() . ' local:build</>'],
                0
            );
        }
        if (!Vars::getVaultParsed()) {
            throw new DotEnvVaultError(
                'Empty ' . Vars::getVaultFilepath(),
                'EMPTY_DOTENV_VAULT',
                ['Run, <options=bold>' . Vars::getCli() . ' local:build</>'],
                0
            );
        }
    }
}