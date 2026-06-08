<?php

namespace App\Console\Commands\Cron\Executors;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class OperatorCronExecutor implements CronExecutorInterface
{
    public function supports(object $instance): bool
    {
        return $instance instanceof \Operador_CronTaskAbstract;
    }

    public function execute(
        InputInterface $input,
        OutputInterface $output,
        object $instance,
        Command $command
    ): int {
        $instance->setHorarioInicioExecucao(new \Zend_Date());
        
        $operadorModel = new \Operador();
        $operatorId = $input->getOption('operator');

        if ($operatorId) {
            $operador = $operadorModel->fetchRow($operadorModel->select()->where('id = ? OR subdominio = ?', $operatorId, $operatorId));
            if (!$operador) {
                throw new \RuntimeException("Operador '{$operatorId}' não encontrado.");
            }
            $operadores = [$operador];
        } else {
            // Comportamento padrão: rodar para todos os operadores ativos se não especificar --operator
            $operadores = $operadorModel->getAllOperadores(true);
            $total = count($operadores);

            // Aviso interativo apenas se o console for interativo (evita quebrar crontab em produção)
            if ($input->isInteractive()) {
                $output->writeln("<comment>Atenção: nenhum --operator informado.</comment>");
                $output->writeln("<comment>Isso executará para {$total} operadores ativos.</comment>");

                $helper = $command->getHelper('question');
                $question = new ConfirmationQuestion(
                    'Continuar para TODOS os operadores? [y/N]: ',
                    false
                );

                if (!$helper->ask($input, $output, $question)) {
                    $output->writeln("<fg=red>Execução abortada pelo usuário.</fg=red>");
                    return Command::SUCCESS;
                }
            }
        }

        $originalAdapter = null;
        try {
            $originalAdapter = \Zend_Db_Table::getDefaultAdapter();
        } catch (\Throwable $e) {
            // Nenhum adaptador padrão configurado anteriormente
        }

        $total = count($operadores);
        $count = 1;

        foreach ($operadores as $operador) {
            $output->writeln("<comment>[{$count}/{$total}] Processando operador: {$operador->nome}</comment>");
            
            $dbOperador = null;
            try {
                // Registra a conexão de banco de dados do operador dinamicamente
                $config = \Zend_Registry::get('config');
                $dbParams = $config->db->config->toArray();
                $dbParams['host'] = $operador->banco_server;
                $dbParams['dbname'] = $operador->banco;
                
                $dbOperador = \Zend_Db::factory($config->db->adapter, $dbParams);
                \Zend_Db_Table::setDefaultAdapter($dbOperador);

                $instance->setOperador($operador);

                if (method_exists($instance, 'setForceRun')) {
                    $instance->setForceRun($input->getOption('force'));
                }

                $instance->execute($dbOperador, $operador);
            } catch (\Throwable $e) {
                $output->writeln("<fg=red>Erro no operador {$operador->nome}: " . $e->getMessage() . "</fg=red>");
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln("<comment>" . $e->getTraceAsString() . "</comment>");
                }
            } finally {
                if ($dbOperador) {
                    $dbOperador->closeConnection();
                }
                // Restaura o adaptador padrão original para manter a consistência do framework
                if ($originalAdapter) {
                    \Zend_Db_Table::setDefaultAdapter($originalAdapter);
                }
            }
            $count++;
        }

        return Command::SUCCESS;
    }
}
