<?php

namespace Babble;

use Symfony\Component\Console\Application;
use Babble\Commands\BuildCommand;

class CLI
{
    public function __construct()
    {
        $app = new Application('babble', '0.0.2');
        $app->add(new BuildCommand());
        $app->run();

    }
}

