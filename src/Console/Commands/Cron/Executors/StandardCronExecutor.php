<?php

namespace App\Console\Commands\Cron\Executors;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class StandardCronExecutor implements CronExecutorInterface
{
    public function supports(object $instance): bool
    {
        return $instance instanceof \Henger_Plugin_Cron_CronInterface;
    }

    public function execute(
        InputInterface $input,
        OutputInterface $output,
        object $instance,
        Command $command
    ): int {
        $locked = false;

        if ($instance->isLocked()) {
            $output->writeln("<comment>Aviso: A rotina possui um travamento (lock) ativo.</comment>");

            $force = $input->getOption('force');
            if (!$force) {
                $helper = $command->getHelper('question');
                $question = new ConfirmationQuestion(
                    'Deseja ignorar o travamento e forçar a execução? [y/N]: ',
                    false
                );

                if (!$helper->ask($input, $output, $question)) {
                    $output->writeln("<error>Execução abortada pelo usuário devido ao travamento ativo.</error>");
                    return Command::SUCCESS;
                }
            }

            $output->writeln("<comment>Forçando execução: removendo trava ativa...</comment>");
            $instance->unlock();
        }

        // Ativa o lock para esta execução
        $locked = $instance->tryLock();
        if (!$locked) {
            $output->writeln("<error>Erro: Não foi possível obter a trava (lock) para executar a rotina.</error>");
            return Command::FAILURE;
        }

        try {
            // Configura o horário de início antes de rodar (assim como no serviço original)
            $instance->setHorarioInicioExecucao(new \Zend_Date());
            $instance->run();
        } finally {
            if ($locked) {
                $instance->unlock();
            }
        }

        return Command::SUCCESS;
    }
}
