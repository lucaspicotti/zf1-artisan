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
                    $output->writeln("<fg=red>Execução abortada pelo usuário devido ao travamento ativo.</fg=red>");
                    return Command::SUCCESS;
                }
            }

            $output->writeln("<comment>Forçando execução: removendo trava ativa...</comment>");
            $instance->unlock();
        }

        // Ativa o lock para esta execução
        $locked = $instance->tryLock();
        if (!$locked) {
            $output->writeln("<fg=red>Erro: Não foi possível obter a trava (lock) para executar a rotina.</fg=red>");
            return Command::FAILURE;
        }

        try {
            // Configura o horário de início antes de rodar (assim como no serviço original)
            $instance->setHorarioInicioExecucao(new \Zend_Date());

            if (method_exists($instance, 'setForceRun')) {
                $instance->setForceRun($input->getOption('force'));
            }

            if (method_exists($instance, 'isActive') && !$instance->isActive()) {
                $output->writeln("<fg=red>Erro: A rotina não está ativa para execução no momento (isActive retornou falso).</fg=red>");
                return Command::FAILURE;
            }

            $instance->run();
        } finally {
            if ($locked) {
                $instance->unlock();
            }
        }

        return Command::SUCCESS;
    }
}
