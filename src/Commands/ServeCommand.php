<?php

namespace Babble\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('serve')
            ->addOption('live-reload', 'l')
            ->setDescription("Start Babble's development server.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if($input->getOption('live-reload')) {
            echo "Live reload enabled.\n";
            putenv('BABBLE_LIVE_RELOAD=true');
        }

        $command = 'php -S localhost:8000 index.php';
        $handle = popen($command, 'r');
        while (1) {
            $read = fread($handle, 1024);
            echo $read;
        }
    }
}