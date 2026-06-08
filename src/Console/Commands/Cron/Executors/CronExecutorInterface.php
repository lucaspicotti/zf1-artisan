<?php
/**
 * File containing the CronExecutorInterface interface.
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

/**
 * Interface CronExecutorInterface
 *
 * @category Console
 * @package  App\Console\Commands\Cron\Executors
 * @author   lucaspicotti <lucaspicotti@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/lucaspicotti/zf1-artisan
 */
interface CronExecutorInterface
{
    /**
     * Verifica se este executor suporta a execução da rotina dada.
     *
     * @param object $instance A instância da cron.
     *
     * @return bool Verdadeiro se suportar, falso caso contrário.
     */
    public function supports(object $instance): bool;

    /**
     * Executa a rotina da cron.
     *
     * @param InputInterface  $input    A entrada de console.
     * @param OutputInterface $output   A saída de console.
     * @param object          $instance A instância da cron.
     * @param Command         $command  O comando pai executando a rotina.
     *
     * @return int O código de status da execução.
     */
    public function execute(
        InputInterface $input,
        OutputInterface $output,
        object $instance,
        Command $command
    ): int;
}
