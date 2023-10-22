<?php

namespace DotenvVaultTest\Commands;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class WhoamiCommandTest extends TestCase
{
    public function testWhenEnvVaultMissing(): void
    {
        $actualExit = $this->runCommand('whoami');
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

        $actualExit = $this->runCommand('whoami');
        $actualOutput = $this->output->fetch();

        $this->assertSame(1, $actualExit);
        $this->assertStringContainsString('EMPTY_DOTENV_VAULT', $actualOutput);
        $this->assertRegExp('/Empty .*\.env\.vault \(DOTENV_VAULT\)/', $actualOutput);
        $this->assertStringContainsString('Run, vendor/bin/dotenv-vault new', $actualOutput);
    }

    public function testWhenEnvMeMissing(): void
    {
        $this->fileClient->allows()->exists('.env.vault')->andReturns(true);
        $this->fileClient->allows()->read('.env.vault')->andReturns('DOTENV_VAULT=vault_hash');

        $actualExit = $this->runCommand('whoami');
        $actualOutput = $this->output->fetch();

        $this->assertSame(1, $actualExit);
        $this->assertStringContainsString('MISSING_DOTENV_ME', $actualOutput);
        $this->assertRegExp('/Missing .*\.env\.me \(DOTENV_ME\)/', $actualOutput);
        $this->assertStringContainsString('Run, vendor/bin/dotenv-vault login', $actualOutput);
    }

    public function testWhenEnvMeEmpty(): void
    {
        $this->fileClient->allows()->exists('.env.vault')->andReturns(true);
        $this->fileClient->allows()->read('.env.vault')->andReturns('DOTENV_VAULT=vault_hash');
        $this->fileClient->allows()->exists('.env.me')->andReturns(true);
        $this->fileClient->allows()->read('.env.me')->andReturns('');

        $actualExit = $this->runCommand('whoami');
        $actualOutput = $this->output->fetch();

        $this->assertSame(1, $actualExit);
        $this->assertStringContainsString('EMPTY_DOTENV_ME', $actualOutput);
        $this->assertRegExp('/Empty .*\.env\.me \(DOTENV_ME\)/', $actualOutput);
        $this->assertStringContainsString('Run, vendor/bin/dotenv-vault login', $actualOutput);
    }

    public function testWhenApiError(): void
    {
        $this->fileClient->allows()->exists('.env.vault')->andReturns(true);
        $this->fileClient->allows()->read('.env.vault')->andReturns('DOTENV_VAULT=vault_hash');
        $this->fileClient->allows()->exists('.env.me')->andReturns(true);
        $this->fileClient->allows()->read('.env.me')->andReturns('DOTENV_ME=me_hash');
        $this->httpHandler->append(new Response(400, [], json_encode(['errors' => [['message' => 'Login, plz', 'suggestions' => ['Run npx dotenv-vault@latest login']]]])));

        $actualExit = $this->runCommand('whoami');
        $actualOutput = $this->output->fetch();

        $this->assertSame(1, $actualExit);
        $this->assertStringContainsString('WHOAMI_ERROR', $actualOutput);
        $this->assertStringContainsString('Login, plz', $actualOutput);
        $this->assertStringContainsString('Suggestion: Run vendor/bin/dotenv-vault login', $actualOutput);
    }

    /** @dataProvider getSuccessData */
    public function testSuccess(array $parameters, bool $hasProjectFile, string $expectMe): void
    {
        $vaultHash = 'vault_hash';
        $expectVault = $vaultHash;
        if ($hasProjectFile) {
            $projectHash = 'project_hash';
            $expectVault = $projectHash;
            $this->fileClient->allows()->exists('.env.project')->andReturns(true);
            $this->fileClient->allows()->read('.env.project')->andReturns("DOTENV_PROJECT={$projectHash}");
        }
        $this->fileClient->allows()->exists('.env.vault')->andReturns(true);
        $this->fileClient->allows()->read('.env.vault')->andReturns("DOTENV_VAULT={$vaultHash}");
        $this->fileClient->allows()->exists('.env.me')->andReturns(true);
        $this->fileClient->allows()->read('.env.me')->andReturns('DOTENV_ME=me_hash');
        $this->httpHandler->append(new Response(200, [], json_encode(['data' => ['email' => 'doc@future.com']])));

        $actualExit = $this->runCommand('whoami', $parameters);
        $actualOutput = $this->output->fetch();

        $this->assertStringContainsString('doc@future.com', $actualOutput);
        $this->assertSame(0, $actualExit);
        $this->assertInstanceOf(Request::class, $actualRequest = $this->httpHistory[0]['request'] ?? null);
        /** @var Request $actualRequest */
        $actualRequestData = json_decode($actualRequest->getBody(), true);
        $this->assertSame(['DOTENV_ME' => $expectMe, 'DOTENV_VAULT' => $expectVault], $actualRequestData);
    }

    public function getSuccessData(): array
    {
        return [
            'me from file' => [[], false, 'me_hash'],
            'me override' => [['--DOTENV_ME' => 'me_override_hash'], false, 'me_override_hash'],
            'me override shorthand' => [['-m' => 'me_override_hash'], false, 'me_override_hash'],
            'vault from .env.project' => [[], true, 'me_hash'],
        ];
    }
}
