<?php
/**
 * File containing the Kernel class.
 *
 * PHP version 7.4
 *
 * @category Console
 * @package  App\Console
 * @author   lucaspicotti <lucaspicotti@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/lucaspicotti/zf1-artisan
 */

namespace App\Console;

use Symfony\Component\Console\Application;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use ReflectionClass;

/**
 * Class Kernel
 *
 *
 * @category Console
 * @package  App\Console
 * @author   lucaspicotti <lucaspicotti@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/lucaspicotti/zf1-artisan
 */
class Kernel
{
    /**
     * @var Application Instância do Console do Symfony
     */
    protected Application $application;

    /**
     * Kernel constructor.
     */
    public function __construct()
    {
        $this->bootstrapEnvironment();
        $this->application = new Application($_ENV['APPLICATION_NAME'] ?? 'Artisan ZF1', '1.0.0');
        $this->bootstrapCommands();
    }

    /**
     * Configura o ambiente (caminhos padrão).
     *
     * @return void
     */
    protected function bootstrapEnvironment(): void
    {
        $appPath = $_ENV['APPLICATION_PATH'] ?? $_SERVER['APPLICATION_PATH'] ?? getenv('APPLICATION_PATH');
        
        if (empty($appPath)) {
            $parentAppPath = dirname(__DIR__, 5) . '/application/';
            if (is_dir($parentAppPath)) {
                $_ENV['APPLICATION_PATH'] = $parentAppPath;
                $_SERVER['APPLICATION_PATH'] = $parentAppPath;
                putenv("APPLICATION_PATH={$parentAppPath}");
            }
        }
    }

    /**
     * Registra automaticamente todos os comandos encontrados no diretório Commands.
     *
     * @return void
     */
    protected function bootstrapCommands(): void
    {
        $commandsPath = __DIR__ . '/Commands';
        if (!is_dir($commandsPath)) {
            return;
        }

        $directory = new RecursiveDirectoryIterator($commandsPath);
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator, '/Command\.php$/');

        foreach ($regex as $file) {
            $realPath = $file->getRealPath();
            if ($realPath === false) {
                continue;
            }

            $relativePath = str_replace($commandsPath, '', $realPath);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
            $relativePath = substr($relativePath, 0, -4);

            $className = 'App\\Console\\Commands' . $relativePath;

            if (class_exists($className)) {
                try {
                    $reflection = new ReflectionClass($className);
                    if ($reflection->isInstantiable() && $reflection->isSubclassOf(\Symfony\Component\Console\Command\Command::class)) {
                        $this->application->add($reflection->newInstance());
                    }
                } catch (\ReflectionException $e) {
                    // Ignora classes que falham na reflexão
                }
            }
        }
    }

    /**
     * Executa a aplicação de console.
     *
     * @return int
     * @throws \Exception
     */
    public function handle(): int
    {
        return $this->application->run();
    }
}
