<?php


namespace Babble\Commands;


use Babble\API\OpenApiGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateOpenApiCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('openapi')
            ->setDescription('Generate OpenAPI / swagger JSON definition.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $generator = new OpenApiGenerator();

        $definition = $generator->json();

        file_put_contents('openapi.json', $definition);

    }
}