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
            //->addArgument('live-reload', InputArgument::OPTIONAL)
            ->setDescription("Start Babble's development server.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = 'php -t public/ -S localhost:8000 public/index.php';
        $handle = popen($command, 'r');
        while (1) {
            $read = fread($handle, 1024);
            echo $read;
        }
    }
}