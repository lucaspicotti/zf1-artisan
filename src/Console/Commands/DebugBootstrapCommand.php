<?php
/**
 * File containing the DebugBootstrapCommand class.
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

use App\Console\Commands\ZendCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DebugBootstrapCommand
 *
 * Comando responsável por testar, validar e depurar cada etapa do bootstrap do Zend Framework 1.
 *
 *
 * @category Console
 * @package  App\Console\Commands
 * @author   lucaspicotti <lucaspicotti@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/lucaspicotti/zf1-artisan
 */
class DebugBootstrapCommand extends ZendCommand
{
    /**
     * @var string O nome do comando CLI.
     */
    protected static $defaultName = 'debug:bootstrap';

    /**
     * Configura a descrição do comando.
     */
    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Valida e depura o bootstrap do Zend Framework 1 e seus recursos');
    }

    /**
     * Executa os passos de validação do bootstrap do ZF1.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("<info>==================================================</info>");
        $output->writeln("<info>   Validando o Bootstrap do Zend Framework 1      </info>");
        $output->writeln("<info>==================================================</info>\n");

        $basePath = $input->getOption('path') ?? $_ENV['APPLICATION_PATH'] ?? null;

        // 1. Validar caminho base
        $output->write("1. Verificando APPLICATION_PATH... ");
        if (!$basePath) {
            $output->writeln("<fg=red>FALHA</fg=red>");
            $output->writeln("<comment>A variável de ambiente APPLICATION_PATH não está definida nem via opção --path.</comment>");
            return self::FAILURE;
        }
        $output->writeln("<info>OK</info> ({$basePath})");

        $basePath = rtrim($basePath, '/');
        $rootDir = dirname($basePath) . '/';

        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', $rootDir);
        }
        if (!defined('APP_PATH')) {
            define('APP_PATH', $basePath . '/');
        }

        chdir(ROOT_DIR);

        $bootstrapCronPath = $basePath . '/BootstrapCron.php';
        $bootstrapPath = $basePath . '/Bootstrap.php';
        $configIniPath = $basePath . '/config.ini';

        // 2. Validar arquivos requeridos
        $output->write("2. Verificando BootstrapCron.php... ");
        if (!file_exists($bootstrapCronPath)) {
            $output->writeln("<fg=red>FALHA</fg=red> (Não encontrado em {$bootstrapCronPath})");
            return self::FAILURE;
        }
        $output->writeln("<info>OK</info>");

        $output->write("3. Verificando Bootstrap.php... ");
        if (!file_exists($bootstrapPath)) {
            $output->writeln("<fg=red>FALHA</fg=red> (Não encontrado em {$bootstrapPath})");
            return self::FAILURE;
        }
        $output->writeln("<info>OK</info>");

        $output->write("4. Verificando config.ini... ");
        if (!file_exists($configIniPath)) {
            $output->writeln("<fg=red>FALHA</fg=red> (Não encontrado em {$configIniPath})");
            return self::FAILURE;
        }
        $output->writeln("<info>OK</info>");

        // 3. Tentar resolver include_path do Zend
        $output->write("5. Localizando classe Zend_Application... ");
        $rootDir = dirname($basePath) . '/';
        $includePaths = [
            $rootDir . 'library',
            $basePath . '/controllers/',
            $basePath . '/models/',
            $basePath . '/forms/',
            $basePath . '/cron/',
        ];

        $originalIncludePath = get_include_path();
        set_include_path(
            implode(PATH_SEPARATOR, $includePaths) . PATH_SEPARATOR . $originalIncludePath
        );

        $zendPath = stream_resolve_include_path('Zend/Application.php');
        if (!$zendPath) {
            $output->writeln("<fg=red>FALHA</fg=red>");
            $output->writeln("<comment>Zend/Application.php não foi localizado no include_path.</comment>");
            $output->writeln("Include path atual: " . get_include_path());
            return self::FAILURE;
        }
        $output->writeln("<info>OK</info> (Encontrado em: {$zendPath})");

        // 4. Executar o bootstrap do Zend_Application
        $output->write("6. Inicializando Zend_Application e executando Bootstrap... ");
        try {
            include_once 'Zend/Application.php';

            $zendApplication = new \Zend_Application(
                'Cron',
                [
                    'bootstrap' => [
                        'class' => 'BootstrapCron',
                        'path' => $bootstrapCronPath
                    ],
                    'config' => $configIniPath,
                    'phpSettings' => [
                        'display_errors' => true,
                        'display_startup_errors' => true,
                    ]
                ]
            );

            $zendApplication->bootstrap();
            $output->writeln("<info>OK</info> (Bootstrap executado com sucesso)");
        } catch (\Throwable $e) {
            $output->writeln("<fg=red>FALHA</fg=red>");
            $output->writeln("<fg=red>Erro na inicialização: " . $e->getMessage() . "</fg=red>");
            $output->writeln("<comment>" . $e->getTraceAsString() . "</comment>");
            return self::FAILURE;
        }

        // 5. Validar conexão de Banco de Dados
        $output->write("7. Verificando adaptador de banco de dados (dbAdapter)... ");
        try {
            if (\Zend_Registry::isRegistered('dbAdapter')) {
                $db = \Zend_Registry::get('dbAdapter');
                $output->writeln("<info>OK</info>");

                $output->write("   Testando conexão com o Banco de dados... ");
                $db->query("SELECT 1");
                $output->writeln("<info>OK</info> (Conectado com sucesso!)");
            } else {
                $output->writeln("<comment>Nenhum dbAdapter registrado no contêiner.</comment>");
            }
        } catch (\Throwable $e) {
            $output->writeln("<fg=red>FALHA</fg=red>");
            $output->writeln("<fg=red>Erro na conexão com banco: " . $e->getMessage() . "</fg=red>");
        }

        // 6. Validar Cache
        $output->write("8. Verificando suporte a cache (Zend_Registry)... ");
        try {
            if (\Zend_Registry::isRegistered('cache')) {
                $cache = \Zend_Registry::get('cache');
                $output->writeln("<info>OK</info> (Adaptador: " . get_class($cache) . ")");
            } else {
                $output->writeln("<comment>Nenhum cache registrado no Zend_Registry.</comment>");
            }
        } catch (\Throwable $e) {
            $output->writeln("<fg=red>FALHA</fg=red>");
            $output->writeln("<fg=red>Erro ao verificar cache: " . $e->getMessage() . "</fg=red>");
        }

        // 7. Verificar Xdebug para debug CLI
        $output->write("9. Verificando se Xdebug está ativo para depuração... ");
        if (extension_loaded('xdebug')) {
            $output->writeln("<info>ATIVO</info> (Versão: " . phpversion('xdebug') . ")");
            $output->writeln("   Configurações Xdebug relevantes:");
            $output->writeln("   - xdebug.mode: " . ini_get('xdebug.mode'));
            $output->writeln("   - xdebug.client_host: " . ini_get('xdebug.client_host'));
            $output->writeln("   - xdebug.client_port: " . ini_get('xdebug.client_port'));
            $output->writeln("   - xdebug.start_with_request: " . ini_get('xdebug.start_with_request'));
        } else {
            $output->writeln("<comment>INATIVO</comment> (Xdebug não está instalado ou habilitado no php.ini do CLI)");
        }

        $output->writeln("\n<info>==================================================</info>");
        $output->writeln("<info>   Validação concluída com sucesso!               </info>");
        $output->writeln("<info>==================================================</info>");

        return self::SUCCESS;
    }
}
