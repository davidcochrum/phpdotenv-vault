<?php

namespace DotenvVault\Commands;

use DotenvVault\Services\StatusService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends Command
{
    protected static $defaultName = 'status';
    protected static $defaultDescription = 'Check dotenv.org status';

    protected function configure()
    {
        return $this->addYesOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new StatusService($input, $output, $this->io, $this->getHelper('question'), $this->getYesOption()))->run();

        return self::SUCCESS;
    }
}