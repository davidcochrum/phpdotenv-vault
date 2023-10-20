<?php

namespace DotenvVault\Services;

use Loilo\NativeOpen\NativeOpen;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class StatusService
{
    /** @var InputInterface */
    private $input;
    /** @var OutputInterface */
    private $output;
    /** @var SymfonyStyle */
    private $io;
    /** @var QuestionHelper */
    private $questionHelper;
    /** @var bool */
    private $yes;

    public function __construct(InputInterface $input, OutputInterface $output, SymfonyStyle $io, QuestionHelper $questionHelper, bool $yes)
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = $io;
        $this->questionHelper = $questionHelper;
        $this->yes = $yes;
    }

    public function run(): void
    {
        if (!$this->yes) {
            $question = new ConfirmationQuestion("Press y (or any key) to open up the browser to view the dotenv-vault status page or q to exit: ", false, '/^q/i');
            if ($this->questionHelper->ask($this->input, $this->output, $question)) {
                $this->io->error('Aborted');
                return;
            }
        }

        $url = 'https://status.dotenv.org';
        $this->io->writeln("Opening browser to {$url}");
        NativeOpen::open($url);
    }
}