<?php

namespace DotenvVault;

interface BrowserInterface
{
    public function open(string $url): void;
}
