<?php

namespace DotenvVault\Commands;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Vars;

/**
 * Trait to apply to remote management commands which require a populated `.env.vault` file.
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
                'Missing ' . Vars::getVaultFilepath() . ' (' . Vars::getVaultKey() . ')',
                'MISSING_DOTENV_VAULT',
                ['Run, <options=bold>' . Vars::getCli() . ' new</>'],
                0
            );
        }
        if (Vars::isEmptyEnvVault()) {
            throw new DotEnvVaultError(
                'Empty ' . Vars::getVaultFilepath() . ' (' . Vars::getVaultKey() . ')',
                'EMPTY_DOTENV_VAULT',
                ['Run, <options=bold>' . Vars::getCli() . ' new</>'],
                0
            );
        }
    }
}