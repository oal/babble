<?php
require 'vendor/autoload.php';

use Babble\Content\StaticSiteGenerator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Builds static HTML files for your website.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $walker = new StaticSiteGenerator();
        $walker->build();
    }
}

$application = new Application();


$application->add(new GenerateCommand());

$application->run();