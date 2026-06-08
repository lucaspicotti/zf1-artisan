<?php
/**
 * File containing the ZendCommand class.
 *
 * PHP version 7.4
 *
 * @category Console
 * @package  App\Console\Commands
 * @author   lucaspicotti <lucaspicotti@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/lucaspicotti/zf1-artisan
 */

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ZendCommand
 *
 * Classe base abstrata para todos os comandos que exigem a inicialização e o bootstrap do Zend Framework 1.
 *
 *
 * @category Console
 * @package  App\Console\Commands
 * @author   lucaspicotti <lucaspicotti@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/lucaspicotti/zf1-artisan
 */
abstract class ZendCommand extends Command
{
    /**
     * @var \Zend_Application Instância do Zend_Application inicializado.
     */
    protected $zendApplication;

    /**
     * Configura o comando adicionando a opção padrão de caminho da aplicação.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'Caminho alternativo para APPLICATION_PATH');
    }

    /**
     * Inicializa e executa o bootstrap do Zend Framework 1.
     *
     * @param  InputInterface $input Entrada do console para resolver opções.
     * @return void
     * @throws \RuntimeException Se APPLICATION_PATH não puder ser resolvido.
     */
    protected function bootstrapZend(InputInterface $input): void
    {
        $basePath = $input->getOption('path') ?? $_ENV['APPLICATION_PATH'] ?? null;

        if (!$basePath) {
            throw new \RuntimeException(
                "A variável de ambiente APPLICATION_PATH não foi definida nem via opção --path."
            );
        }

        $basePath = rtrim($basePath, '/');
        $rootDir = dirname($basePath) . '/';

        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', $rootDir);
        }

        if (!defined('APP_PATH')) {
            define('APP_PATH', $basePath . '/');
        }

        chdir(ROOT_DIR);

        set_include_path(
            $rootDir . 'library' . PATH_SEPARATOR .
            $basePath . '/controllers/' . PATH_SEPARATOR .
            $basePath . '/models/' . PATH_SEPARATOR .
            $basePath . '/forms/' . PATH_SEPARATOR .
            $basePath . '/cron/' . PATH_SEPARATOR .
            get_include_path()
        );

        if (!class_exists('Zend_Application')) {
            $zendPath = stream_resolve_include_path('Zend/Application.php');
            if (!$zendPath) {
                throw new \RuntimeException(
                    "Zend Framework 1 (Zend/Application.php) não foi encontrado no include_path."
                );
            }
            include_once 'Zend/Application.php';
        }

        $this->zendApplication = new \Zend_Application(
            'Cron',
            [
                'bootstrap' => [
                    'class' => 'BootstrapCron',
                    'path' => APP_PATH . 'BootstrapCron.php'
                ],
                'config' => APP_PATH . 'config.ini',
                'phpSettings' => [
                    'display_errors' => true,
                    'display_startup_errors' => true,
                ]
            ]
        );

        $this->zendApplication->bootstrap();
    }

    /**
     * Extrai o primeiro nome de classe definido em um arquivo PHP usando regex.
     *
     * @param  string $filePath
     * @return string|null
     */
    protected function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if (preg_match('/^\s*(?:abstract\s+|final\s+)?class\s+([a-zA-Z0-9_]+)/im', $content, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
