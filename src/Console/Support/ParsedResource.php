<?php
/**
 * File containing the ParsedResource class.
 *
 * PHP version 7.4
 *
 * @category Console
 * @package  App\Console\Support
 * @author   lucaspicotti <lucaspicotti@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/lucaspicotti/zf1-artisan
 */

namespace App\Console\Support;

/**
 * Class ParsedResource
 *
 * Representa os detalhes processados e higienizados de um recurso gerado.
 *
 *
 * @category Console
 * @package  App\Console\Support
 * @author   lucaspicotti <lucaspicotti@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/lucaspicotti/zf1-artisan
 */
class ParsedResource
{
    private string $name;
    private ?string $module;

    /**
     * ParsedResource constructor.
     *
     * @param string      $name   Nome do recurso (sem o prefixo do
     *                            módulo).
     * @param string|null $module Módulo correspondente em caixa baixa.
     */
    public function __construct(string $name, ?string $module = null)
    {
        $this->name = $name;
        $this->module = $module ? strtolower($module) : null;
    }

    /**
     * Retorna o nome do recurso.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retorna o módulo em caixa baixa ou nulo se não houver.
     *
     * @return string|null
     */
    public function getModule(): ?string
    {
        return $this->module;
    }

    /**
     * Verifica se o recurso pertence a um módulo.
     *
     * @return bool
     */
    public function hasModule(): bool
    {
        return !empty($this->module);
    }
}
