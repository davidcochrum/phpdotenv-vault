<?php

namespace DotenvVaultTest\Commands;

use DotenvVault\BrowserInterface;
use DotenvVault\Commands\Command;
use DotenvVault\FileClientInterface;
use DotenvVault\Vars;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

abstract class TestCase extends MockeryTestCase
{
    /** @var Application */
    protected $app;
    /** @var BrowserInterface|(BrowserInterface&LegacyMockInterface)|(BrowserInterface&MockInterface)|LegacyMockInterface|MockInterface */
    protected $browser;
    /** @var BufferedOutput */
    protected $output;
    /** @var FileClientInterface|(FileClientInterface&LegacyMockInterface)|(FileClientInterface&MockInterface)|LegacyMockInterface|MockInterface */
    protected $fileClient;
    /** @var Client */
    protected $httpClient;
    /** @var MockHandler */
    protected $httpHandler;
    /** @var array{{ request: Guzzle\Psr7\Request, response Guzzle\Psr7\Response }[] } */
    protected $httpHistory;

    protected function setUp()
    {
        parent::setUp();
        $this->app = require __DIR__ . '/../../src/app.php';
        $this->browser = Mockery::mock(BrowserInterface::class);

        $this->fileClient = Mockery::mock(FileClientInterface::class);
        $this->fileClient->allows(['exists' => false])->byDefault();
        $this->fileClient->allows('path')->andReturnUsing(function (string $filename) { return "/{$filename}"; })->byDefault();
        Vars::setFileClient($this->fileClient);

        $this->httpHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->httpHandler);
        $this->httpHistory = [];
        $handlerStack->push(Middleware::history($this->httpHistory));
        $this->httpClient = new Client(['handler' => $handlerStack]);

        $this->output = new BufferedOutput();
    }

    protected function makeInput(array $parameters): ArrayInput
    {
        return new ArrayInput($parameters);
    }

    protected function getOutput(): BufferedOutput
    {
        return $this->output;
    }

    protected function runCommand(string $commandName, array $parameters = []): int
    {
        $command = $this->app->get($commandName);
        if ($command instanceof Command) {
            $command->setBrowser($this->browser);
            $command->setFileClient($this->fileClient);
            $command->setHttpClient($this->httpClient);
        }
        return $command->run($this->makeInput($parameters), $this->getOutput());
    }
}
