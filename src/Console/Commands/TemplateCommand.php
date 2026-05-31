<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class TemplateCommand extends Command
{
    protected static $defaultName = 'app:teste';

    protected function configure(): void
    {
        $this
        ->setDescription('<info>Exemplo de comando personalizado</info>')
        ->addArgument('name', InputArgument::OPTIONAL, 'O nome a ser exibido');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $output->writeln("Olá, $name! Este é um comando personalizado.");
        return Command::SUCCESS;
    }
}
