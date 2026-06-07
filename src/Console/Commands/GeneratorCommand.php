<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use App\Console\Support\ResourceInputParser;

/**
 * Class GeneratorCommand
 *
 * Classe base abstrata para todos os comandos de geração de arquivos (stubs) no console.
 */
abstract class GeneratorCommand extends Command
{
    /**
     * @var Filesystem Instância do componente de sistema de arquivos do Symfony.
     */
    protected Filesystem $filesystem;

    /**
     * GeneratorCommand constructor.
     *
     * Inicializa a classe base de comando do Symfony e instancia o Filesystem do framework.
     */
    public function __construct()
    {
        parent::__construct();
        $this->filesystem = new Filesystem();
    }

    /**
     * Define a assinatura do comando com seus respectivos argumentos e opções padrões.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'O nome do recurso a ser gerado (ex: Nome ou modulo/Nome)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Força a sobrescrita do arquivo se ele já existir')
            ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'Caminho alternativo para APPLICATION_PATH');
    }

    /**
     * Orquestra todo o fluxo sequencial de validação, formatação e geração do código.
     *
     * @param InputInterface $input Entrada do console com argumentos e opções.
     * @param OutputInterface $output Saída do console para feedbacks visuais.
     * @return int Status code de retorno do console (0 para sucesso, 1 para falha).
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rawName = $input->getArgument('name');
        $force = $input->getOption('force');

        try {
            $parser = new ResourceInputParser();
            $resource = $parser->parse($rawName);
        } catch (\InvalidArgumentException $exception) {
            $output->writeln("<error>Erro de validação: {$exception->getMessage()}</error>");
            return Command::FAILURE;
        }

        $name = $resource->getName();
        $module = $resource->getModule();

        $basePath = $input->getOption('path') ?? $_ENV['APPLICATION_PATH'] ?? null;
        if (!$basePath) {
            $output->writeln(
                "<error>Erro: A variável APPLICATION_PATH não foi definida no ambiente nem via opção --path.</error>"
            );
            return Command::FAILURE;
        }

        $targetDetails = $this->resolveTargetDetails($basePath, $name, $module);
        $destinationPath = $targetDetails['path'];
        $className = $targetDetails['class_name'];

        if ($this->filesystem->exists($destinationPath) && !$force) {
            $output->writeln("<error>Erro: {$className} já existe em: {$destinationPath}</error>");
            $output->writeln("<comment>Use a opção --force (-f) para sobrescrever.</comment>");
            return Command::FAILURE;
        }

        $stubPath = $this->getStubPath();
        if (!file_exists($stubPath)) {
            $output->writeln("<error>Erro: Stub de template não encontrado em: {$stubPath}</error>");
            return Command::FAILURE;
        }

        try {
            $stubContent = file_get_contents($stubPath);
            $finalContent = $this->populateStub($stubContent, $className, $name, $module);

            $this->filesystem->dumpFile($destinationPath, $finalContent);

            $output->writeln("<info>{$className} criado com sucesso em: {$destinationPath}</info>");
            return Command::SUCCESS;
        } catch (IOExceptionInterface $exception) {
            $output->writeln("<error>Erro ao criar o arquivo: {$exception->getMessage()}</error>");
            return Command::FAILURE;
        }
    }

    /**
     * Retorna o tipo de recurso gerado (ex: 'controller', 'model', 'form').
     *
     * @return string
     */
    abstract protected function getResourceType(): string;

    /**
     * Retorna o caminho para o arquivo stub template.
     *
     * @return string Caminho do stub (ex: stubs/controller.stub).
     */
    abstract protected function getStubPath(): string;

    /**
     * Resolve o nome final da classe com base no recurso e se pertence a um módulo.
     *
     * @param string $name Nome base do recurso.
     * @param string|null $module Módulo informado.
     * @return string Nome completo da classe (ex: Admin_ProductController).
     */
    abstract protected function getClassName(string $name, ?string $module): string;

    /**
     * Resolve o nome físico do arquivo destino e o nome final de classe.
     *
     * @param string $basePath Caminho absoluto de APPLICATION_PATH.
     * @param string $name Nome sanitizado do recurso.
     * @param string|null $module Módulo correspondente opcional.
     * @return array contendo chaves 'class_name' e 'path'.
     */
    protected function resolveTargetDetails(string $basePath, string $name, ?string $module): array
    {
        $resourceType = $this->getResourceType();

        $resDir = rtrim($basePath, '/') . "/" . $resourceType;
        $resTitleDir = rtrim($basePath, '/') . "/" . ucfirst($resourceType);
        $actualResDir = (!is_dir($resDir) && is_dir($resTitleDir)) ? $resTitleDir : $resDir;

        if ($module) {
            $defaultDir = $actualResDir . "/" . strtolower($module);
            $titleDir = $actualResDir . "/" . ucfirst($module);

            $directory = (!is_dir($defaultDir) && is_dir($titleDir)) ? $titleDir : $defaultDir;
        } else {
            $directory = $actualResDir;
        }

        $fileName = $this->getFileName($name);
        $className = $this->getClassName($name, $module);
        $filePath = $directory . '/' . $fileName . '.php';

        return [
            'class_name' => $className,
            'path' => $filePath
        ];
    }

    /**
     * Resolve o nome físico do arquivo final gerado
     *
     * Pode ser estendido se o gerador final exigir sufixo/extensão específica.
     *
     * @param string $name
     * @return string
     */
    protected function getFileName(string $name): string
    {
        $parts = explode('/', $name);
        $capitalizedParts = array_map('ucfirst', $parts);

        return implode('/', $capitalizedParts);
    }

    /**
     * Preenche as chaves de variáveis delimitadas do stub.
     *
     * @param string $stubContent Conteúdo bruto do stub.
     * @param string $className Nome completo da classe.
     * @param string $name Nome original sanitizado do recurso.
     * @param string|null $module Módulo informado.
     * @return string Conteúdo final formatado para salvar.
     */
    protected function populateStub(string $stubContent, string $className, string $name, ?string $module): string
    {
        return str_replace(['{{ class }}', '{{class}}'], $className, $stubContent);
    }

    /**
     * Resolve o caminho absoluto de um stub interno do pacote de forma dinâmica e portável.
     *
     * @param string $stubName Nome do arquivo de stub (ex: 'cron.stub').
     * @return string Caminho absoluto completo.
     */
    protected function resolveStubPath(string $stubName): string
    {
        return dirname(__DIR__, 3) . '/stubs/' . $stubName;
    }
}
