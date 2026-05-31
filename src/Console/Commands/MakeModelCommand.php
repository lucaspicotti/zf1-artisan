<?php

namespace App\Console\Commands;

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
            ->setDescription('Gera uma nova model')
            ->setHelp('Este comando cria uma model com suporte a estruturas modulares.');
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
        return __DIR__ . '/../../../stubs/model.stub';
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
        $cleanName = ucfirst($name);

        if ($module) {
            return ucfirst(strtolower($module)) . '_Model_' . $cleanName;
        }

        return $cleanName;
    }
}
