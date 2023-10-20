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

class LogoutService
{
    /** @var InputInterface */
    private $input;
    /** @var OutputInterface */
    private $output;
    /** @var SymfonyStyle */
    private $io;
    /** @var QuestionHelper */
    private $helper;
    /** @var bool */
    private $yes;
    /** @var string */
    private $requestUid;

    public function __construct(InputInterface $input, OutputInterface $output, SymfonyStyle $io, QuestionHelper $helper, bool $yes)
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = $io;
        $this->helper = $helper;
        $this->yes = $yes;
        $this->requestUid = Vars::generateRequestUid();
    }

    /**
     * @throws DotEnvVaultError
     */
    public function run(): void
    {
        $this->logout();
    }

    /**
     * Performs logout.
     * @param bool $tip Whether to present user with tip for next step.
     * @throws DotEnvVaultError When unable to confirm login.
     */
    public function logout(bool $tip = true): void
    {
        $logoutUrl = Vars::buildApiActionUrl('logout', ['requestUid' => $this->requestUid]);
        if (!$this->yes) {
            $this->io->writeln("Logout URL: {$logoutUrl}");
            $question = new ConfirmationQuestion("Press y (or any key) to open up the browser to logout (.env.me) and revoke credential or q to exit: ", false, '/^q/i');
            if ($this->helper->ask($this->input, $this->output, $question)) {
                $this->io->error('Aborted');
                return;
            }
        }

        $this->io->writeln("Opening browser to {$logoutUrl}");
        NativeOpen::open($logoutUrl);
        $this->io->writeln('Waiting for logout and credential (.env.me) to be revoked');
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
        $this->io->success('Revoked .env.me (DOTENV_ME=' . substr($meUid, 0, 9) . '...)');
        if ($tip) {
            $this->io->writeln([
                '',
                'Next run <options=bold>' . Vars::getCli() . ' login</> to generate a new credential (.env.me)',
            ]);
        }
    }
}