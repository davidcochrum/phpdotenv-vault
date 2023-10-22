<?php

namespace DotenvVaultTest\Commands;

class OpenCommandTest extends TestCase
{
    public function testWhenEnvVaultMissing(): void
    {
        $actualExit = $this->runCommand('open');
        $actualOutput = $this->output->fetch();

        $this->assertSame(1, $actualExit);
        $this->assertStringContainsString('MISSING_DOTENV_VAULT', $actualOutput);
        $this->assertRegExp('/Missing .*\.env\.vault \(DOTENV_VAULT\)/', $actualOutput);
        $this->assertStringContainsString('Run, vendor/bin/dotenv-vault new', $actualOutput);
    }

    public function testWhenEnvVaultEmpty(): void
    {
        $this->fileClient->allows()->exists('.env.vault')->andReturns(true);
        $this->fileClient->allows()->read('.env.vault')->andReturns('');

        $actualExit = $this->runCommand('open');
        $actualOutput = $this->output->fetch();

        $this->assertSame(1, $actualExit);
        $this->assertStringContainsString('EMPTY_DOTENV_VAULT', $actualOutput);
        $this->assertRegExp('/Empty .*\.env\.vault \(DOTENV_VAULT\)/', $actualOutput);
        $this->assertStringContainsString('Run, vendor/bin/dotenv-vault new', $actualOutput);
    }

    /** @dataProvider getSuccessData */
    public function testSuccess($environment, string $expectUrl): void
    {
        $this->fileClient->allows()->exists('.env.vault')->andReturns(true);
        $this->fileClient->allows()->read('.env.vault')->andReturns("DOTENV_VAULT=vault_hash");
        $this->browser->expects()->open($expectUrl)->once();

        $actualExit = $this->runCommand('open', ['environment' => $environment, '-y' => true]);
        $actualOutput = $this->output->fetch();

        $this->assertStringContainsString("Opening browser to {$expectUrl}", $actualOutput);
        $this->assertStringContainsString("Next run vendor/bin/dotenv-vault pull to pull your .env file", $actualOutput);
        $this->assertSame(0, $actualExit);
    }

    public function getSuccessData(): array
    {
        return [
            'default environment' => [null, 'https://vault.dotenv.org/open?DOTENV_VAULT=vault_hash&environment=development'],
            'specific environment' => ['stage', 'https://vault.dotenv.org/open?DOTENV_VAULT=vault_hash&environment=stage'],
        ];
    }
}
