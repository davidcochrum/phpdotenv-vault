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

class LoginService
{
    /** @var InputInterface */
    private $input;
    /** @var OutputInterface */
    private $output;
    /** @var SymfonyStyle */
    private $io;
    /** @var QuestionHelper */
    private $helper;
    /** @var string|null */
    private $dotEnvMe;
    /** @var bool */
    private $yes;
    /** @var string */
    private $requestUid;

    public function __construct(InputInterface $input, OutputInterface $output, SymfonyStyle $io, QuestionHelper $helper, string $dotEnvMe = null, bool $yes = false)
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = $io;
        $this->helper = $helper;
        $this->dotEnvMe = $dotEnvMe;
        $this->yes = $yes;
        $this->requestUid = Vars::generateRequestUid();
    }

    /**
     * @throws DotEnvVaultError
     */
    public function run(): void
    {
        (new AppendToIgnoreService)->run();

        // Step 2 B
        if ($this->dotEnvMe) {
            $create = Vars::isMissingEnvMe();
            $this->io->writeln(($create ? 'Creating' : 'Updating') . ' .env.me (DOTENV_ME)');
            file_put_contents(Vars::getEnvMeFilepath(), $this->getMeFileContent($this->dotEnvMe));
            $this->io->writeln([
                ($create ? 'Created' : 'Updated') . ' .env.me (DOTENV_ME)',
                '',
                'Next run <options=bold>' . Vars::getCli() . '</> pull or <options=bold>' . Vars::getCli() . ' push</>',
            ]);

            return;
        }

        $this->login();
    }

    /**
     * Performs login.
     * @param bool $tip Whether to present user with tip for next step.
     * @throws DotEnvVaultError When unable to confirm login.
     */
    public function login(bool $tip = true): void
    {
        $loginUrl = Vars::buildApiActionUrl('login', ['requestUid' => $this->requestUid]);
        if (!$this->yes) {
            $this->io->writeln("Login URL: {$loginUrl}");
            $question = new ConfirmationQuestion("Press y (or any key) to open up the browser to login and generate credential (.env.me) or q to exit: ", false, '/^q/i');
            if ($this->helper->ask($this->input, $this->output, $question)) {
                $this->io->error('Aborted');
                return;
            }
        }

        $this->io->writeln("Opening browser to {$loginUrl}");
        NativeOpen::open($loginUrl);
        $this->io->writeln('Waiting for login and credential (.env.me) to be generated');
        $this->check($tip);
    }

    /**
     * @throws DotEnvVaultError When unable to confirm login.
     */
    private function check(bool $tip): void
    {
        $client = new Client();
        $url = Vars::buildApiActionUrl('check', ['requestUid' => $this->requestUid]);
        $options = [
            'json' => [
                'vaultUid' => Vars::getVaultValue(),
                'requestUid' => $this->requestUid,
            ],
        ];
        $checkCount = 0;
        $this->io->progressStart(100);
        $meUid = null;
        do {
            $checkCount++;
            $this->io->progressAdvance();
            try {
                $response = $client->post($url, $options);
                $data = json_decode($response->getBody());
                $meUid = $data->data->meUid;
            } catch (GuzzleException $e) {
                // check every 2 seconds
                sleep(1);
                $this->io->progressAdvance();
                sleep(1);
            }
        } while (!$meUid && $checkCount <= 50);

        $this->io->progressFinish();
        if ($checkCount >= 50) {
            throw new DotEnvVaultError('Things were taking too long... gave up. Please try again.');
        }

        // Step 3
        $create = Vars::isMissingEnvMe();
        file_put_contents(Vars::getEnvMeFilepath(), $this->getMeFileContent($meUid));
        $this->io->success(($create ? 'Created' : 'Updated') . ' .env.me (DOTENV_ME=' . substr($meUid, 0, 9) . '...)');
        if ($tip) {
            $this->io->writeln([
                '',
                'Next run <options=bold>' . Vars::getCli() . ' open</>',
            ]);
        }
    }

    private function getMeFileContent(string $value): string
    {
        return Vars::getMeFileHeaderComment() . <<<ENV

DOTENV_ME="{$value}"
ENV;
    }
}