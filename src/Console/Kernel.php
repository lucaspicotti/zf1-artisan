<?php

namespace App\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Dotenv\Dotenv;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use ReflectionClass;

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
     * Configura o ambiente (.env e caminhos).
     *
     * @return void
     */
    protected function bootstrapEnvironment(): void
    {
        $dotenv = new Dotenv();

        $localEnv = dirname(__DIR__, 2) . '/.env';
        if (file_exists($localEnv)) {
            $dotenv->load($localEnv);
        }

        $parentEnv = dirname(__DIR__, 5) . '/.env';
        if (file_exists($parentEnv)) {
            $dotenv->load($parentEnv);
        }

        if (empty($_ENV['APPLICATION_PATH'])) {
            $parentAppPath = dirname(__DIR__, 5) . '/application/';
            if (is_dir($parentAppPath)) {
                $_ENV['APPLICATION_PATH'] = $parentAppPath;
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
