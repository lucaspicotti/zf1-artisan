<?php

namespace App\Console\Commands\Cron;

use App\Console\Commands\ZendCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CronRunCommand extends ZendCommand
{
    protected static $defaultName = 'cron:run';

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setDescription('Executa uma rotina de cron específica no Zend Framework 1')
            ->addArgument(
                'rotina',
                InputArgument::REQUIRED,
                'O nome ou atalho da rotina a ser executada (ex: CleanTmp)'
            )
            ->addOption(
                'operator',
                'o',
                InputOption::VALUE_OPTIONAL,
                'ID ou subdomínio de um operador específico para tarefas de operador'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Força a execução para todos os operadores cadastrados'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Força a execução da rotina, ignorando travamentos (locks) ativos'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->bootstrapZend($input);
        } catch (\Throwable $e) {
            $output->writeln("<error>Erro no bootstrap do Zend Framework: " . $e->getMessage() . "</error>");
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln("<comment>" . $e->getTraceAsString() . "</comment>");
            }
            return self::FAILURE;
        }

        try {
            $instancia = $this->resolveInstance($input);
        } catch (\Throwable $e) {
            $output->writeln("<error>" . $e->getMessage() . "</error>");
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln("<comment>" . $e->getTraceAsString() . "</comment>");
            }
            return self::FAILURE;
        }

        return $this->executeInstance($input, $output, $instancia);
    }

    /**
     * Resolve o arquivo físico e retorna a instância correta da classe.
     */
    private function resolveInstance(InputInterface $input): object
    {
        $basePath = $input->getOption('path') ?? $_ENV['APPLICATION_PATH'] ?? null;
        if (!$basePath) {
            throw new \RuntimeException("Erro: A variável de ambiente APPLICATION_PATH não foi definida nem via opção --path.");
        }

        $cronDir = rtrim($basePath, '/') . '/cron';
        $rotina = $input->getArgument('rotina');
        
        $realCronDir = realpath($cronDir);
        $filePath = realpath($cronDir . '/' . $rotina . '.php');
        
        if (!$realCronDir || !$filePath || strpos($filePath, $realCronDir) !== 0) {
            throw new \RuntimeException("Erro: Caminho da rotina inválido ou arquivo não encontrado.");
        }

        $className = $this->getClassNameFromFile($filePath);
        if (!$className) {
            throw new \RuntimeException("Erro: Nenhuma classe correspondente encontrada no arquivo.");
        }

        // Tenta autoloading primeiro; se falhar, faz require_once como fallback
        if (!class_exists($className)) {
            require_once $filePath;
        }

        if (!class_exists($className)) {
            throw new \RuntimeException("Erro: Classe '{$className}' não encontrada no arquivo.");
        }

        $reflection = new \ReflectionClass($className);
        if ($reflection->isAbstract() || $reflection->isInterface()) {
            throw new \RuntimeException("Erro: A classe '{$className}' é abstrata ou interface e não pode ser executada.");
        }

        return new $className();
    }

    /**
     * Gerencia a execução da rotina utilizando a estratégia (Executor) compatível.
     */
    private function executeInstance(InputInterface $input, OutputInterface $output, object $instancia): int
    {
        $executors = [
            new Executors\StandardCronExecutor(),
            new Executors\OperatorCronExecutor(),
        ];

        $className = get_class($instancia);
        $output->writeln("<info>Iniciando execução da rotina: {$className}</info>");
        $startTime = microtime(true);

        foreach ($executors as $executor) {
            if ($executor->supports($instancia)) {
                try {
                    $statusCode = $executor->execute($input, $output, $instancia, $this);
                    
                    $elapsedTime = round(microtime(true) - $startTime, 2);
                    $output->writeln("<info>Rotina executada com sucesso em {$elapsedTime} segundos.</info>");
                    
                    return $statusCode;
                } catch (\Throwable $e) {
                    $output->writeln("<error>Erro durante a execução da rotina: " . $e->getMessage() . "</error>");
                    if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                        $output->writeln("<comment>" . $e->getTraceAsString() . "</comment>");
                    }
                    return self::FAILURE;
                }
            }
        }

        $output->writeln("<error>Erro: Tipo de classe de cron desconhecido ou não suportado.</error>");
        return self::FAILURE;
    }
}

