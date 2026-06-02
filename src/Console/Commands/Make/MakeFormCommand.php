<?php

namespace App\Console\Commands\Make;

use App\Console\Commands\GeneratorCommand;

/**
 * Class MakeFormCommand
 *
 * Comando console responsável por automatizar a criação de novos Form.
 */
class MakeFormCommand extends GeneratorCommand
{
    /**
    * @var string O nome e a assinatura padrão do comando CLI.
    */
    protected static $defaultName = 'make:form';

    /**
    * Configura as opções do console e a descrição de ajuda para o comando.
    *
    * @return void
    */
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setDescription('Gera um novo formulário (Zend_Form)')
            ->setHelp('Este comando cria um formulário com suporte a estruturas modulares.');
    }

    /**
     * Retorna o tipo de recurso para a resolução dinâmica de diretórios.
     *
     * @return string
     */
    protected function getResourceType(): string
    {
        return 'forms';
    }

    /**
     * Retorna o caminho absoluto do template stub de um Form..
     *
     * @return string
     */
    protected function getStubPath(): string
    {
        return __DIR__ . '/../../../stubs/form.stub';
    }

    /**
     * Resolve o nome de classe completo
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
            return 'Form_' . ucfirst(strtolower($module)) . '_' . $classSuffix;
        }

        return 'Form_' . $classSuffix;
    }
}
