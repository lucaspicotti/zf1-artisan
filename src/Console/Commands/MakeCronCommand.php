<?php

namespace App\Console\Commands;

/**
 * Class MakeCronCommand
 *
 * Comando console responsável por automatizar a criação de novos Cron.
 */
class MakeCronCommand extends GeneratorCommand
{
    /**
    * @var string O nome e a assinatura padrão do comando CLI.
    */
    protected static $defaultName = 'make:cron';

    /**
    * Configura as opções do console e a descrição de ajuda para o comando.
    *
    * @return void
    */
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setDescription('Gera um novo Cron')
            ->setHelp('Este comando cria um cron');
    }

    /**
     * Retorna o tipo de recurso para a resolução dinâmica de diretórios.
     *
     * @return string
     */
    protected function getResourceType(): string
    {
        return 'cron';
    }

    /**
     * Retorna o caminho absoluto do template stub de um Cron.
     *
     * @return string
     */
    protected function getStubPath(): string
    {
        return __DIR__ . '/../../../stubs/cron.stub';
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
            return ucfirst(strtolower($module)) . '_' . $classSuffix;
        }

        return 'Henger_Plugin_Cron_' . $classSuffix;
    }

    /**
     * Preenche as chaves de variáveis do stub de Cron.
     *
     * @param string $stubContent
     * @param string $className
     * @param string $name
     * @param string|null $module
     * @return string
     */
    protected function populateStub(string $stubContent, string $className, string $name, ?string $module): string
    {
        $stubContent = parent::populateStub($stubContent, $className, $name, $module);

        $baseClass = isset($module) ? "Operador_CronTaskAbstract" : "Henger_Plugin_Cron_CronAbstract";

        return str_replace(['{{ base_class }}', '{{base_class}}'], $baseClass, $stubContent);
    }
}
