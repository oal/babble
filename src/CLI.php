<?php

namespace Babble;

use Babble\Commands\GenerateOpenApiCommand;
use Babble\Commands\ServeCommand;
use Symfony\Component\Console\Application;
use Babble\Commands\BuildCommand;

class CLI
{
    public function __construct()
    {
        $app = new Application('babble', '0.0.2');

        $app->add(new BuildCommand());
        $app->add(new ServeCommand());
        $app->add(new GenerateOpenApiCommand());

        $app->run();
    }
}

