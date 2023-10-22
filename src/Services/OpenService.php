<?php

namespace DotenvVault\Services;

use DotenvVault\BrowserInterface;
use DotenvVault\Vars;
use Loilo\NativeOpen\NativeOpen;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class OpenService
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
    /** @var string */
    private $environment;
    /** @var bool */
    private $yes;

    public function __construct(InputInterface $input, OutputInterface $output, SymfonyStyle $io, QuestionHelper $helper, BrowserInterface $browser, string $environment, bool $yes)
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = $io;
        $this->helper = $helper;
        $this->browser = $browser;
        $this->environment = $environment;
        $this->yes = $yes;
    }

    public function run(): void
    {
        $openUrl = Vars::buildApiActionUrl('open', ['environment' => $this->environment]);
        if (!$this->yes) {
            $this->io->writeln("Project URL: {$openUrl}");
            $question = new ConfirmationQuestion("Press y (or any key) to open up the browser to view your project or q to exit: ", false, '/^q/i');
            if ($this->helper->ask($this->input, $this->output, $question)) {
                $this->io->error('Aborted');
                return;
            }
        }
        $this->io->writeln('Opening project page...');
        $this->io->writeln("Opening browser to {$openUrl}");
        $this->browser->open($openUrl);
        $command = Vars::missingEnv() ? 'pull' : 'push';
        $this->io->writeln([
            '',
            'Next run <options=bold>' . Vars::getCli() . " {$command}</> to {$command} your .env file",
        ]);
    }
}
