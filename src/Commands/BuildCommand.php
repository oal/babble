<?php

namespace Babble\Commands;

use Babble\Content\StaticSiteGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('build')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, '')
            ->addOption('scheme', 's', InputOption::VALUE_OPTIONAL, '', 'http')
            ->setDescription('Builds static HTML files for your website.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $baseUrl = $input->getOption('scheme') . '://' . $input->getOption('host');
        $output->writeln("<info>$baseUrl</info>");
        $walker = new StaticSiteGenerator($output, $baseUrl);
        $walker->build();

        return 0;
    }
}
