<?php
/**
 * File containing the MakeApiCommand class.
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
 * Class MakeApiCommand
 *
 * Comando console responsável por automatizar a criação de novos arquivos de API.
 *
 *
 * @category Console
 * @package  App\Console\Commands\Make
 * @author   lucaspicotti <lucaspicotti@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/lucaspicotti/zf1-artisan
 */
class MakeApiCommand extends GeneratorCommand
{
    /**
     * @var string O nome e a assinatura padrão do comando CLI.
     */
    protected static $defaultName = 'make:api';

    /**
     * Configura as opções do console e a descrição de ajuda para o comando.
     *
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setDescription('Gera uma nova classe de recurso ou controller de API')
            ->setHelp('Este comando cria uma nova classe de API REST no diretório correspondente.');
    }

    /**
     * Pré-processa o argumento 'name' para normalizar prefixos de API redundantes
     * e executa o fluxo padrão de geração de arquivos.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface   $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return int Status code de retorno do console.
     */
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
    {
        $rawName = $input->getArgument('name');
        if (preg_match('/^api\//i', $rawName)) {
            $input->setArgument('name', preg_replace('/^api\//i', '', $rawName));
        }

        return parent::execute($input, $output);
    }

    /**
     * Retorna o tipo de recurso para a resolução dinâmica de diretórios.
     *
     * @return string
     */
    protected function getResourceType(): string
    {
        return 'api';
    }

    /**
     * Retorna o caminho absoluto do template stub de uma API.
     *
     * @return string
     */
    protected function getStubPath(): string
    {
        return $this->resolveStubPath('api.stub');
    }

    /**
     * Resolve o nome de classe completo de acordo com o padrão ZF1 para arquivos de API.
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
            return 'Api_' . ucfirst(strtolower($module)) . '_' . $classSuffix;
        }

        return 'Api_' . $classSuffix;
    }

    /**
     * Preenche as variáveis do stub de API com base na classe e herança apropriada.
     *
     * Se a classe gerada for um Controller (ex: TerminalController), ela estenderá a classe
     * correspondente a Api_<Versao>_AbstractRestController. Caso contrário, não estende nada.
     *
     * @param  string      $stubContent
     * @param  string      $className
     * @param  string      $name
     * @param  string|null $module
     * @return string
     */
    protected function populateStub(string $stubContent, string $className, string $name, ?string $module): string
    {
        $stubContent = parent::populateStub($stubContent, $className, $name, $module);

        $isController = str_ends_with($className, 'Controller');
        if ($isController) {
            $version = $module ? ucfirst(strtolower($module)) : 'V1';
            $baseClass = "extends Api_{$version}_AbstractRestController";
        } else {
            $baseClass = "";
        }

        if ($baseClass !== "") {
            $baseClass = " " . $baseClass;
        }

        return str_replace(['{{ base_class }}', '{{base_class}}'], $baseClass, $stubContent);
    }
}
