<?php

require __DIR__ . '/../vendor/autoload.php';

$app = new Symfony\Component\Console\Application();

$app->addCommands([
    new DotenvVault\Commands\BuildCommand(),
    new DotenvVault\Commands\DecryptCommand(),
    new DotenvVault\Commands\KeysCommand(),
    new DotenvVault\Commands\Local\BuildCommand(),
    new DotenvVault\Commands\Local\DecryptCommand(),
    new DotenvVault\Commands\Local\KeysCommand(),
    new DotenvVault\Commands\Local\RotateKeyCommand(),
    new DotenvVault\Commands\LoginCommand(),
    new DotenvVault\Commands\LogoutCommand(),
    new DotenvVault\Commands\NewCommand(),
    new DotenvVault\Commands\OpenCommand(),
    new DotenvVault\Commands\PullCommand(),
    new DotenvVault\Commands\PushCommand(),
    new DotenvVault\Commands\RotateKeyCommand(),
    new DotenvVault\Commands\StatusCommand(),
    new DotenvVault\Commands\VersionsCommand(),
    new DotenvVault\Commands\WhoamiCommand(),
]);

return $app;
