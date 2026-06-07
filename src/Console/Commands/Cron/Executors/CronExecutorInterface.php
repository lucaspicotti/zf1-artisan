<?php

namespace App\Console\Commands\Cron\Executors;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface CronExecutorInterface
{
    /**
     * Verifica se este executor suporta a execução da rotina dada.
     */
    public function supports(object $instance): bool;

    /**
     * Executa a rotina da cron.
     */
    public function execute(
        InputInterface $input,
        OutputInterface $output,
        object $instance,
        Command $command
    ): int;
}
