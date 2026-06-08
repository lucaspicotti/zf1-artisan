<?php
/**
 * File containing the MakeControllerCommand class.
 *
 * PHP version 7.4
 *
 * @category Console
 * @package  App\Console\Commands\Make
 * @author   lucaspicotti <lucaspicotti@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/lucaspicotti/zf1-artisan
 */

namespace App\Console\Commands\Make;

use App\Console\Commands\GeneratorCommand;

/**
 * Class MakeControllerCommand
 *
 * Comando console responsável por automatizar a criação de novos Controllers.
 *
 *
 * @category Console
 * @package  App\Console\Commands\Make
 * @author   lucaspicotti <lucaspicotti@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/lucaspicotti/zf1-artisan
 */
class MakeControllerCommand extends GeneratorCommand
{
    /**
     * @var string O nome e a assinatura padrão do comando CLI.
     */
    protected static $defaultName = 'make:controller';

    /**
     * Configura as opções do console e a descrição de ajuda para o comando.
     *
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setDescription('Gera um novo controller de ação (Zend_Controller_Action)')
            ->setHelp('Este comando cria um controller de ação com suporte a estruturas modulares.');
    }

    /**
     * Retorna o tipo de recurso para a resolução dinâmica de diretórios.
     *
     * @return string
     */
    protected function getResourceType(): string
    {
        return 'controllers';
    }

    /**
     * Retorna o caminho absoluto do template stub de um Controller.
     *
     * @return string
     */
    protected function getStubPath(): string
    {
        return $this->resolveStubPath('controller.stub');
    }

    /**
     * Resolve o nome físico do arquivo do controller (garante sufixo "Controller").
     *
     * @param  string $name
     * @return string
     */
    protected function getFileName(string $name): string
    {
        $parts = explode('/', $name);
        $capitalizedParts = array_map('ucfirst', $parts);

        $lastNameIndex = count($capitalizedParts) - 1;
        if (!str_ends_with($capitalizedParts[$lastNameIndex], 'Controller')) {
            $capitalizedParts[$lastNameIndex] .= 'Controller';
        }

        return implode('/', $capitalizedParts);
    }

    /**
     * Resolve o nome de classe completo de acordo com o padrão ZF1 para controllers.
     *
     * @param  string      $name
     * @param  string|null $module
     * @return string
     */
    protected function getClassName(string $name, ?string $module): string
    {
        $fileName = $this->getFileName($name);
        $classSuffix = str_replace('/', '_', $fileName);

        if ($module) {
            return ucfirst(strtolower($module)) . '_' . $classSuffix;
        }

        return $classSuffix;
    }
}
