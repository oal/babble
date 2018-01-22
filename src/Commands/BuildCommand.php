<?php

namespace Babble\Commands;

use Babble\Content\StaticSiteGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Builds static HTML files for your website.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $walker = new StaticSiteGenerator($output);
        $walker->build();
    }
}
