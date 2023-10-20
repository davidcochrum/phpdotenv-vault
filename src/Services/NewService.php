<?php

namespace DotenvVault\Services;

use DotenvVault\DotEnvVaultError;
use DotenvVault\Vars;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Loilo\NativeOpen\NativeOpen;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class NewService
{
    /** @var InputInterface */
    private $input;
    /** @var OutputInterface */
    private $output;
    /** @var SymfonyStyle */
    private $io;
    /** @var QuestionHelper */
    private $helper;
    /** @var string */
    private $dotenvVault;
    /** @var bool */
    private $yes;
    /** @var string */
    private $requestUid;

    public function __construct(InputInterface $input, OutputInterface $output, SymfonyStyle $io, QuestionHelper $helper, string $dotenvVault, bool $yes)
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = $io;
        $this->helper = $helper;
        $this->dotenvVault = $dotenvVault;
        $this->yes = $yes;
        $this->requestUid = Vars::generateRequestUid();
    }

    /** @throws DotEnvVaultError */
    public function run(): void
    {
        (new AppendToIgnoreService)->run();

        // Step 1
        if (Vars::isMissingEnvVault()) {
            file_put_contents(Vars::getVaultFilepath(), $this->getVaultFileContent(" # Generate vault identifiers at {$this->getUrl()}"));
        }

        // Step 2 B
        $vaultFilename = Vars::getVaultFilename();
        $vaultKey = Vars::getVaultKey();
        $vaultFilenameAndKey = "{$vaultFilename} ({$vaultKey})";
        if ($this->dotenvVault) {
            if (Vars::invalidVaultValue($this->dotenvVault)) {
                throw new DotEnvVaultError(
                    "Invalid {$vaultFilenameAndKey}",
                    '', ['Run <options=bold>' . Vars::getCli() . ' new</>'],
                    0
                );
            }

            $this->io->writeln("Adding {$vaultFilenameAndKey}");
            file_put_contents(Vars::getVaultFilepath(), $this->getVaultFileContent($this->dotenvVault));
            $this->io->writeln([
                "Added to {$vaultFilename} ({$vaultKey}=" . substr($this->dotenvVault, 0, 9) . "...)",
                "",
                "Next run <options=bold>" . Vars::getCli() . " {$this->getPushOrPullCommand()}</>", // assumes dev started from UI (because prompted to enter existing dotenvVault uid) so recommend push or pull command instead of login
            ]);

            return;
        }

        if (Vars::hasExistingVaultValue()) {
            throw new DotEnvVaultError("Existing {$vaultFilenameAndKey}.", '', [
                "Delete {$vaultFilename} and then run, <options=bold>" . Vars::getCli() . " new</>",
            ], 0);
        }


        $newUrl = $this->getUrlWithProjectName();
        if (!$this->yes) {
            $this->io->writeln("Project URL: {$newUrl}");
            $question = new ConfirmationQuestion("Press y (or any key) to open up the browser to create a new project vault (.env.vault) or q to exit: ", false, '/^q/i');
            if ($this->helper->ask($this->input, $this->output, $question)) {
                $this->io->error('Aborted');
                return;
            }
        }

        $this->io->writeln("Opening browser to {$newUrl}");
        NativeOpen::open($newUrl);
        $this->io->writeln('Waiting for project vault (.env.vault) to be created');

        $this->check();
    }

    /** @throws DotEnvVaultError */
    private function check(): void
    {
        $client = new Client();
        $checkCount = 0;
        $this->io->progressStart(100);
        $vaultUid = null;
        do {
            $checkCount++;
            $this->io->progressAdvance();
            try {
                $response = $client->post($this->checkUrl(), ['json' => ['requestUid' => $this->requestUid]]);
                $data = json_decode($response->getBody());
                $vaultUid = $data->data->vaultUid;
            } catch (GuzzleException $e) {
                // check every 2 seconds
                sleep(1);
                $this->io->progressAdvance();
                sleep(1);
            }
        } while (!$vaultUid && $checkCount <= 50);

        $this->io->progressFinish();
        if ($checkCount >= 50) {
            throw new DotEnvVaultError('Things were taking too long... gave up. Please try again.');
        }

        // Step 3
        file_put_contents(Vars::getVaultFilepath(), $this->getVaultFileContent($vaultUid));
        $this->io->writeln([
            "Added to " . Vars::getVaultFilename() . " (" . Vars::getVaultKey() . "=" . substr($vaultUid, 0, 9) . "...)",
            "",
            "Next run <options=bold>" . Vars::getCli() . " login</>",
        ]);
    }

    private function getVaultFileContent(string $value): string
    {
        $header = Vars::getVaultFileHeaderComment();
        $cli = Vars::getCli();
        $key = Vars::getVaultKey();
        $api = Vars::getApiUrl();
        return <<<ENV
{$header}

#
# Hello 👋,
#
# Your environment variables will be encrypted and
# safely stored in this .env.vault file, after you
# run the login, push, and build commands.
#
# Next run:
#
# $ {$cli} login
#
#
# You can safely commit this file to code.
#
# Enjoy. 🌴
#

#/----------------settings/metadata-----------------/
{$key}="${value}"
DOTENV_API_URL="{$api}"
DOTENV_CLI="{$cli}"
ENV;
    }

    private function getUrl(): string
    {
        return Vars::getApiUrl() . '/new';
    }

    private function checkUrl(): string
    {
        return Vars::getApiUrl() . '/vault';
    }

    private function getPushOrPullCommand(): string
    {
        // tell dev to push if they already have a local .env file
        if (file_exists(getcwd() . '/.env')) {
            return 'push';
        }

        // otherwise tell them to pull
        return 'pull';
    }

    private function getUrlWithProjectName(): string
    {
        $composer = json_decode(file_get_contents(getcwd() . '/composer.json'));
        $nameParts = explode('/', $composer->name);
        $name = $nameParts[1] ?? $nameParts[0];

        return $this->getUrl() . "?" . http_build_query(['project_name' => $name, 'request_uid' => $this->requestUid]);
    }
}