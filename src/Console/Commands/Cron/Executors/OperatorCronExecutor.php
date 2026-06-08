<?php

/**
 * File containing the OperatorCronExecutor class.
 *
 * PHP version 7.4
 *
 * @category Console
 * @package  App\Console\Commands\Cron\Executors
 * @author   lucaspicotti <lucaspicotti@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/lucaspicotti/zf1-artisan
 */

namespace App\Console\Commands\Cron\Executors;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class OperatorCronExecutor
 *
 * @category Console
 * @package  App\Console\Commands\Cron\Executors
 * @author   lucaspicotti <lucaspicotti@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/lucaspicotti/zf1-artisan
 */
class OperatorCronExecutor implements CronExecutorInterface
{
    /**
     * Verifica se este executor suporta a execução da rotina dada.
     *
     * @param object $instance A instância da cron.
     *
     * @return bool Verdadeiro se suportar, falso caso contrário.
     */
    public function supports(object $instance): bool
    {
        return $instance instanceof \Operador_CronTaskAbstract;
    }

    /**
     * Executa a rotina da cron para um ou todos os operadores.
     *
     * @param InputInterface  $input    A entrada de console.
     * @param OutputInterface $output   A saída de console.
     * @param object          $instance A instância da cron.
     * @param Command         $command  O comando pai executando a rotina.
     *
     * @return int O código de status da execução.
     * @throws \RuntimeException Se o operador especificado não for encontrado.
     */
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
            $select = $operadorModel->select();
            if (is_numeric($operatorId)) {
                $select->where('oid = ?', (int) $operatorId);
            } else {
                $select->where(
                    'usuario = ? OR nome = ? OR banco = ?',
                    $operatorId,
                    $operatorId,
                    $operatorId
                );
            }
            $operador = $operadorModel->fetchRow($select);
            if (!$operador) {
                throw new \RuntimeException(
                    "Operador '{$operatorId}' não encontrado."
                );
            }
            $operadores = [$operador];
        } else {
            $operadores = $operadorModel->getAllOperadores(true);
            $total = count($operadores);

            if ($input->isInteractive()) {
                $output->writeln(
                    "<comment>Atenção: nenhum --operator informado.</comment>"
                );
                $output->writeln(
                    "<comment>Isso executará para {$total} " .
                    "operadores ativos.</comment>"
                );

                $helper = $command->getHelper('question');
                $question = new ConfirmationQuestion(
                    'Continuar para TODOS os operadores? [y/N]: ',
                    false
                );

                if (!$helper->ask($input, $output, $question)) {
                    $output->writeln(
                        "<fg=red>Execução abortada pelo usuário.</fg=red>"
                    );
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
            $output->writeln(
                "<comment>[{$count}/{$total}] " .
                "Processando operador: {$operador->nome}</comment>"
            );

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
                $output->writeln(
                    "<fg=red>Erro no operador {$operador->nome}: " .
                    $e->getMessage() .
                    "</fg=red>"
                );
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln(
                        "<comment>" . $e->getTraceAsString() . "</comment>"
                    );
                }
            } finally {
                if ($dbOperador) {
                    $dbOperador->closeConnection();
                }

                if ($originalAdapter) {
                    \Zend_Db_Table::setDefaultAdapter($originalAdapter);
                }
            }
            $count++;
        }

        return Command::SUCCESS;
    }
}
