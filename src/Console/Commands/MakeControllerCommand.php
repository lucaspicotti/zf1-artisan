<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class MakeControllerCommand extends Command
{
    protected static $defaultName = 'make:controller';

    protected function configure(): void
    {
        $this
            ->setDescription('Gera um novo controller no padrão Zend Framework 1')
            ->addArgument('name', InputArgument::REQUIRED, 'O nome do controller (ex: Produto)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = ucfirst($input->getArgument('name'));
        // Evita duplicar o sufixo 'Controller' se o usuário já o informou
        $className = preg_match('/Controller$/', $name) ? $name : $name . 'Controller';

        $basePath = $_ENV['APPLICATION_PATH'] ?? null;

        if (!$basePath) {
            $output->writeln("<error>Erro: A variável APPLICATION_PATH não foi definida.</error>");
            return Command::FAILURE;
        }

        // Verifica se a pasta no destino usa 'Controllers' (com C maiúsculo) ou 'controllers' (minúsculo)
        $controllersPath = rtrim($basePath, '/') . '/controllers';
        if (!is_dir($controllersPath) && is_dir(rtrim($basePath, '/') . '/Controllers')) {
            $controllersPath = rtrim($basePath, '/') . '/Controllers';
        }

        $destinationPath = $controllersPath . "/{$className}.php";
        $stubPath = __DIR__ . '/../../../stubs/controller.stub';

        $filesystem = new Filesystem();

        if ($filesystem->exists($destinationPath)) {
            $output->writeln("<error>O controller {$className} já existe em: {$destinationPath}</error>");
            return Command::FAILURE;
        }

        try {
            $stubContent = file_get_contents($stubPath);
            $finalContent = str_replace(['{{ class }}', '{{class}}'], $className, $stubContent);

            $filesystem->dumpFile($destinationPath, $finalContent);

            $output->writeln("<info>Controller criada com sucesso em: {$destinationPath}</info>");
            return Command::SUCCESS;
        } catch (IOExceptionInterface $exception) {
            $output->writeln("<error>Erro ao criar o arquivo: {$exception->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
