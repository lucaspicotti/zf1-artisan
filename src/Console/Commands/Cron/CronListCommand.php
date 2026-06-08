<?php

namespace App\Console\Commands\Cron;

use App\Console\Commands\ZendCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CronListCommand extends ZendCommand
{
    /**
     * @var string O nome e a assinatura padrão do comando CLI.
     */
    protected static $defaultName = 'cron:list';

    /**
     * Configura as opções do console e a descrição de ajuda para o comando.
     *
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setDescription('Lista todas as rotinas de cron cadastradas no sistema')
            ->addOption(
                'interactive',
                'i',
                InputOption::VALUE_NONE,
                'Modo interativo para selecionar e obter detalhes de uma rotina'
            );
    }

    /**
     * Executa a listagem das crons carregando os arquivos e usando Reflection,
     * caindo de volta para análise estática de regex caso o Zend Framework não esteja instalado localmente.
     *
     * @param InputInterface $input Entrada do console.
     * @param OutputInterface $output Saída do console.
     * @return int Status code de retorno do console.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->bootstrapZend($input);
        } catch (\Throwable $e) {
            $output->writeln("<fg=red>Erro: Não foi possível inicializar o Zend Framework para listar as crons.</fg=red>");
            $output->writeln("<fg=red>Detalhes: " . $e->getMessage() . "</fg=red>");
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln("<comment>" . $e->getTraceAsString() . "</comment>\n");
            }
            return self::FAILURE;
        }

        $basePath = $input->getOption('path') ?? $_ENV['APPLICATION_PATH'] ?? null;
        if (!$basePath) {
            $output->writeln(
                "<fg=red>Erro: A variável de ambiente APPLICATION_PATH não foi definida nem via opção --path.</fg=red>"
            );
            return self::FAILURE;
        }

        $cronDir = rtrim($basePath, '/') . '/cron';
        if (!is_dir($cronDir)) {
            $output->writeln("<fg=red>Diretório de crons não encontrado em: {$cronDir}</fg=red>");
            return self::FAILURE;
        }

        $files = $this->scanDirectory($cronDir);
        $rows = [];

        foreach ($files as $filePath) {
            // Ignora arquivos temporários/desativados que começam com '__'
            if (strpos(basename($filePath), '__') === 0) {
                continue;
            }

            $className = $this->getClassNameFromFile($filePath);
            if (!$className) {
                continue;
            }

            $relativePath = str_replace($cronDir . '/', '', $filePath);
            $shortcut = str_replace('.php', '', $relativePath);

            try {
                $content = file_get_contents($filePath);

                // Ignora interfaces ou classes abstratas estaticamente
                if (preg_match('/^\s*(?:abstract\s+class|interface)\s+/im', $content)) {
                    continue;
                }

                $type = 'Outro';
                if (preg_match('/class\s+[a-zA-Z0-9_]+\s+extends\s+([a-zA-Z0-9_]+)/i', $content, $matches)) {
                    $parentClass = $matches[1];
                    switch ($parentClass) {
                        case 'Henger_Plugin_Cron_CronClientFind':
                        case 'CronClientFind':
                            $type = 'Loop de Clientes';
                            break;
                        case 'Henger_Plugin_Cron_CronOperatorFind':
                        case 'CronOperatorFind':
                            $type = 'Loop de Operadores';
                            break;
                        case 'Henger_Plugin_Cron_CronAbstract':
                        case 'CronAbstract':
                        case 'Cron_Abstract':
                            $type = 'Cron Padrão';
                            break;
                        case 'Operador_CronTaskAbstract':
                        case 'CronTaskAbstract':
                            $type = 'Tarefa de Operador';
                            break;
                    }
                }
            } catch (\Throwable $e) {
                $type = 'Erro de Leitura (' . $e->getMessage() . ')';
            }

            $rows[] = [
                $shortcut,
                $className,
                $type
            ];
        }

        if ($input->getOption('interactive')) {
            $this->runInteractiveMode($input, $output, $rows, $cronDir);
            return self::SUCCESS;
        }

        if (empty($rows)) {
            $output->writeln("<comment>Nenhuma rotina de cron ativa ou executável foi encontrada.</comment>");
            return self::SUCCESS;
        }

        // Ordena por atalho
        usort($rows, function ($a, $b) {
            return strcmp($a[0], $b[0]);
        });

        $table = new Table($output);
        $table
            ->setHeaders(['Nome / Atalho', 'Classe PHP', 'Tipo de Rotina'])
            ->setRows($rows);

        $table->render();

        return self::SUCCESS;
    }

    /**
     * Executa o modo interativo apresentando um menu de escolhas.
     */
    private function runInteractiveMode(InputInterface $input, OutputInterface $output, array $rows, string $cronDir): void
    {
        $choices = [];
        $mapping = [];

        foreach ($rows as $row) {
            $shortcut = $row[0];
            $className = $row[1];
            $type = $row[2];
            $choices[] = $shortcut;
            $mapping[$shortcut] = [
                'class' => $className,
                'type' => $type,
                'path' => $cronDir . '/' . $shortcut . '.php'
            ];
        }

        $choices[] = 'Sair';

        while (true) {
            $menu = new \App\Console\Support\InteractiveMenu(
                $input,
                $output,
                $choices,
                'Selecione uma rotina de cron para ver detalhes:'
            );

            $selectedIndex = $menu->run();
            $selection = $choices[$selectedIndex];

            if ($selection === 'Sair') {
                $output->writeln("Saindo do modo interativo.");
                break;
            }

            $details = $mapping[$selection];
            $output->writeln("\n<info>Detalhes da Rotina:</info>");
            $output->writeln("--------------------------------------------------");
            $output->writeln("Atalho/Nome: <comment>{$selection}</comment>");
            $output->writeln("Classe PHP:  <comment>{$details['class']}</comment>");
            $output->writeln("Tipo:        <comment>{$details['type']}</comment>");
            $output->writeln("Arquivo:     <comment>{$details['path']}</comment>");
            $output->writeln("--------------------------------------------------\n");

            // Menu de ações pós-seleção
            $actionMenu = new \App\Console\Support\InteractiveMenu(
                $input,
                $output,
                [
                    'Executar esta rotina (cron:run)',
                    'Selecionar outra rotina',
                    'Sair'
                ],
                'Escolha uma ação para esta rotina:'
            );

            $actionIndex = $actionMenu->run();

            if ($actionIndex === 0) { // Executar
                $command = $this->getApplication()->find('cron:run');
                $arguments = [
                    'command' => 'cron:run',
                    'rotina' => $selection
                ];

                $path = $input->getOption('path');
                if ($path) {
                    $arguments['--path'] = $path;
                }

                $runInput = new \Symfony\Component\Console\Input\ArrayInput($arguments);

                try {
                    $output->writeln("<comment>Iniciando execução da rotina: {$selection}...</comment>\n");
                    $returnCode = $command->run($runInput, $output);
                    $output->writeln("");
                } catch (\Throwable $e) {
                    $output->writeln("\n<fg=red>Erro na execução: " . $e->getMessage() . "</fg=red>\n");
                }

                $helper = $this->getHelper('question');
                $confirmQuestion = new ConfirmationQuestion('Deseja selecionar outra rotina? [Y/n]: ', true);
                if (!$helper->ask($input, $output, $confirmQuestion)) {
                    break;
                }
            } elseif ($actionIndex === 1) { // Selecionar outra
                continue;
            } else { // Sair
                $output->writeln("Saindo do modo interativo.");
                break;
            }
        }
    }

    /**
     * Varre um diretório recursivamente buscando arquivos PHP.
     *
     * @param string $dir
     * @return array
     */
    private function scanDirectory(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

}
