<?php

namespace App\Console\Commands\Make;

use App\Console\Commands\GeneratorCommand;

/**
 * Class MakeModelCommand
 *
 * Comando console responsável por automatizar a criação de novas Models.
 */
class MakeModelCommand extends GeneratorCommand
{
    /**
     * @var string O nome e a assinatura padrão do comando CLI.
     */
    protected static $defaultName = 'make:model';

    /**
     * Configura as opções do console e a descrição de ajuda para o comando.
     *
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setDescription('Gera uma nova model de tabela (Zend_Db_Table)')
            ->setHelp('Este comando cria uma model de tabela com suporte a estruturas modulares.');
    }

    /**
     * Retorna o tipo de recurso para a resolução dinâmica de diretórios.
     *
     * @return string
     */
    protected function getResourceType(): string
    {
        return 'models';
    }

    /**
     * Retorna o caminho absoluto do template stub de uma Model.
     *
     * @return string
     */
    protected function getStubPath(): string
    {
        return $this->resolveStubPath('model.stub');
    }

    /**
     * Resolve o nome de classe completo de acordo com o padrão ZF1 para models.
     *
     * @param string $name
     * @param string|null $module
     * @return string
     */
    protected function getClassName(string $name, ?string $module): string
    {
        $fileName = $this->getFileName($name);
        $classSuffix = str_replace('/', '_', $fileName);

        if ($module) {
            return ucfirst(strtolower($module)) . '_Model_' . $classSuffix;
        }

        return $classSuffix;
    }
}
