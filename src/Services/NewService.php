<?php

namespace DotenvVault\Services;

use DotenvVault\BrowserInterface;
use DotenvVault\DotEnvVaultError;
use DotenvVault\FileClientInterface;
use DotenvVault\Vars;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
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
    /** @var BrowserInterface */
    private $browser;
    /** @var Client */
    private $httpClient;
    /** @var FileClientInterface */
    private $fileClient;
    /** @var string */
    private $dotenvVault;
    /** @var bool */
    private $yes;
    /** @var string */
    private $requestUid;

    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        QuestionHelper $helper,
        BrowserInterface $browser,
        Client $httpClient,
        FileClientInterface $fileClient,
        string $dotenvVault,
        bool $yes
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->io = $io;
        $this->helper = $helper;
        $this->dotenvVault = $dotenvVault;
        $this->yes = $yes;
        $this->requestUid = Vars::generateRequestUid();
        $this->httpClient = $httpClient;
        $this->browser = $browser;
        $this->fileClient = $fileClient;
    }

    /** @throws DotEnvVaultError */
    public function run(): void
    {
        (new AppendToIgnoreService)->run();

        // Step 1
        if (Vars::isMissingEnvVault()) {
            $this->fileClient->write(Vars::getVaultFilepath(), $this->getVaultFileContent(" # Generate vault identifiers at {$this->getUrl()}"));
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
            $this->fileClient->write(Vars::getVaultFilepath(), $this->getVaultFileContent($this->dotenvVault));
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
        $this->browser->open($newUrl);
        $this->io->writeln('Waiting for project vault (.env.vault) to be created');

        $this->check();
    }

    /** @throws DotEnvVaultError */
    private function check(): void
    {
        $checkCount = 0;
        $this->io->progressStart(100);
        $vaultUid = null;
        do {
            $checkCount++;
            $this->io->progressAdvance();
            try {
                $response = $this->httpClient->post($this->checkUrl(), ['json' => ['requestUid' => $this->requestUid]]);
                $data = json_decode($response->getBody());
                $vaultUid = $data->data->vaultUid;
            } catch (BadResponseException $e) {
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
        $this->fileClient->write(Vars::getVaultFilepath(), $this->getVaultFileContent($vaultUid));
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
        if ($this->fileClient->exists('.env')) {
            return 'push';
        }

        // otherwise tell them to pull
        return 'pull';
    }

    private function getUrlWithProjectName(): string
    {
        if ($this->fileClient->exists('composer.json')) {
            $composer = json_decode($this->fileClient->read('composer.json'));
            $nameParts = explode('/', $composer->name);
            $name = array_pop($nameParts);
        } else {
            $dirs = explode(DIRECTORY_SEPARATOR, $this->fileClient->root());
            $name = array_pop($dirs);
        }

        return $this->getUrl() . "?" . http_build_query(['project_name' => $name, 'request_uid' => $this->requestUid]);
    }
}
