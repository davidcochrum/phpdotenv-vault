<?php

namespace DotenvVault;

use Loilo\NativeOpen\NativeOpen;

class DefaultBrowser implements BrowserInterface
{
    public function open(string $url): void
    {
        NativeOpen::open($url);
    }
}
