<?php
/**
 * File containing the InteractiveMenu class.
 *
 * PHP version 7.4
 *
 * @category Console
 * @package  App\Console\Support
 * @author   lucaspicotti <lucaspicotti@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/lucaspicotti/zf1-artisan
 */

namespace App\Console\Support;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Class InteractiveMenu
 *
 * Provê um menu interativo de seleção de opções via terminal com navegação
 * por setas direcionais, destaque colorido e paginação (viewport deslizante)
 * para evitar estouro de tela.
 *
 *
 * @category Console
 * @package  App\Console\Support
 * @author   lucaspicotti <lucaspicotti@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/lucaspicotti/zf1-artisan
 */
class InteractiveMenu
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var array
     */
    private $options;

    /**
     * @var string
     */
    private $prompt;

    /**
     * @var int
     */
    private $currentIndex = 0;

    /**
     * @var int Limite máximo de opções visíveis por vez na tela.
     */
    private $maxVisible = 10;

    /**
     * @var int Quantidade de linhas escritas na última renderização (para limpeza precisa).
     */
    private $lastRenderedLineCount = 0;

    /**
     * @var int Índice onde a janela de exibição começa.
     */
    private $startIndex = 0;

    /**
     * InteractiveMenu constructor.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array           $options
     * @param string          $prompt
     */
    public function __construct(InputInterface $input, OutputInterface $output, array $options, string $prompt = 'Selecione uma opção:')
    {
        $this->input = $input;
        $this->output = $output;
        $this->options = array_values($options);
        $this->prompt = $prompt;
    }

    /**
     * Executa o menu e retorna o índice selecionado.
     *
     * @return int
     */
    public function run(): int
    {
        if (!$this->isTtySupported()) {
            return $this->runFallback();
        }

        if (!function_exists('shell_exec')) {
            return $this->runFallback();
        }

        // Configura o terminal para desativar buffer de linha e eco de teclado
        $sttyMode = @shell_exec('stty -g');
        if ($sttyMode === null || $sttyMode === false) {
            return $this->runFallback();
        }
        @shell_exec('stty -icanon -echo');

        // Oculta o cursor piscante para uma renderização mais profissional
        $this->output->write("\e[?25l");

        $this->output->writeln("<info>{$this->prompt}</info>");
        $this->renderOptions();

        $stdin = fopen('php://stdin', 'r');
        $totalOptions = count($this->options);

        try {
            while (true) {
                $char = fread($stdin, 1);

                if ($char === "\e") {
                    // Sequência de escape (setas direcionais)
                    $char .= fread($stdin, 2);
                    if ($char === "\e[A") { // Cima
                        $this->currentIndex = ($this->currentIndex - 1 + $totalOptions) % $totalOptions;
                        $this->clearLines();
                        $this->renderOptions();
                    } elseif ($char === "\e[B") { // Baixo
                        $this->currentIndex = ($this->currentIndex + 1) % $totalOptions;
                        $this->clearLines();
                        $this->renderOptions();
                    }
                } elseif ($char === "\n") { // Enter
                    break;
                }
            }
        } finally {
            // Garante o retorno das configurações de console
            $this->output->write("\e[?25h");
            if ($sttyMode) {
                @shell_exec(sprintf('stty %s', trim($sttyMode)));
            }
            if (is_resource($stdin)) {
                fclose($stdin);
            }
        }

        return $this->currentIndex;
    }

    /**
     * Detecta suporte a TTY e presença do comando stty.
     *
     * @return bool
     */
    private function isTtySupported(): bool
    {
        if (function_exists('posix_isatty') && !posix_isatty(STDIN)) {
            return false;
        }

        $sttyCheck = shell_exec('which stty 2>/dev/null');
        return !empty($sttyCheck);
    }

    /**
     * Fallback usando ChoiceQuestion tradicional se o terminal não for interativo.
     *
     * @return int
     */
    private function runFallback(): int
    {
        $helper = new QuestionHelper();
        $question = new ChoiceQuestion($this->prompt, $this->options, count($this->options) - 1);
        $question->setErrorMessage('Opção %s é inválida.');

        $selectedName = $helper->ask($this->input, $this->output, $question);
        return array_search($selectedName, $this->options);
    }

    /**
     * Remove do console as opções renderizadas na última iteração.
     *
     * @return void
     */
    private function clearLines(): void
    {
        for ($i = 0; $i < $this->lastRenderedLineCount; $i++) {
            $this->output->write("\e[1A\e[2K");
        }
    }

    /**
     * Renderiza a lista de opções com destaque na selecionada e paginação.
     *
     * @return void
     */
    private function renderOptions(): void
    {
        $totalOptions = count($this->options);
        $linesWritten = 0;

        if ($totalOptions <= $this->maxVisible) {
            // Renderização sem paginação se os itens couberem na janela
            foreach ($this->options as $index => $option) {
                if ($index === $this->currentIndex) {
                    $this->output->writeln(" <fg=cyan;options=bold>➔ {$option}</>");
                } else {
                    $this->output->writeln("   {$option}");
                }
                $linesWritten++;
            }
        } else {
            // Ajuste dinâmico da janela deslizante (viewport)
            if ($this->currentIndex < $this->startIndex) {
                $this->startIndex = $this->currentIndex;
            } elseif ($this->currentIndex >= $this->startIndex + $this->maxVisible) {
                $this->startIndex = $this->currentIndex - $this->maxVisible + 1;
            }

            // Tratamento especial para transição em loops (circular)
            if ($this->currentIndex === $totalOptions - 1) {
                $this->startIndex = $totalOptions - $this->maxVisible;
            } elseif ($this->currentIndex === 0) {
                $this->startIndex = 0;
            }

            // Indicador de itens ocultos acima
            if ($this->startIndex > 0) {
                $this->output->writeln("   <comment>▲ ({$this->startIndex} itens acima)</comment>");
            } else {
                $this->output->writeln(""); // Mantém a altura da tela consistente
            }
            $linesWritten++;

            // Exibe a fatia correspondente ao viewport
            for ($i = 0; $i < $this->maxVisible; $i++) {
                $index = $this->startIndex + $i;
                if ($index >= $totalOptions) {
                    break;
                }
                $option = $this->options[$index];

                if ($index === $this->currentIndex) {
                    $this->output->writeln(" <fg=cyan;options=bold>➔ {$option}</>");
                } else {
                    $this->output->writeln("   {$option}");
                }
                $linesWritten++;
            }

            // Indicador de itens ocultos abaixo
            $remaining = $totalOptions - ($this->startIndex + $this->maxVisible);
            if ($remaining > 0) {
                $this->output->writeln("   <comment>▼ ({$remaining} itens abaixo)</comment>");
            } else {
                $this->output->writeln(""); // Mantém a altura da tela consistente
            }
            $linesWritten++;
        }

        $this->lastRenderedLineCount = $linesWritten;
    }
}
