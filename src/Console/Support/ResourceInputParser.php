<?php

namespace App\Console\Support;

use InvalidArgumentException;

/**
 * Class ResourceInputParser
 *
 * Responsável por analisar, sanitizar e validar os inputs de nome de recurso gerados pelo console.
 */
class ResourceInputParser
{
    /**
     * Analisa o input bruto do console e extrai as informações validadas do recurso.
     *
     * Suporta os formatos "modulo/NomeRecurso" e "NomeRecurso".
     *
     * @param string $rawInput Entrada bruta vinda do console.
     * @return ParsedResource
     * @throws InvalidArgumentException Se os valores forem inválidos após a sanitização.
     */
    public function parse(string $rawInput): ParsedResource
    {
        // Normaliza barras invertidas para barras comuns
        $normalized = str_replace('\\', '/', $rawInput);

        // Remove a extensão .php se estiver presente no final do input
        if (str_ends_with(strtolower($normalized), '.php')) {
            $normalized = substr($normalized, 0, -4);
        }

        $module = null;
        $name = $normalized;

        if (str_contains($normalized, '/')) {
            $parts = explode('/', $normalized);
            $module = array_shift($parts);
            $name = implode('/', $parts);
        }

        $sanitizedName = $this->sanitize($name);
        $sanitizedModule = $module ? $this->sanitize($module) : null;

        if (empty($sanitizedName)) {
            throw new InvalidArgumentException(
                "O nome do recurso não pode estar vazio ou conter apenas caracteres inválidos."
            );
        }

        if ($module !== null && empty($sanitizedModule)) {
            throw new InvalidArgumentException(
                "O módulo informado não pode estar vazio ou conter apenas caracteres inválidos."
            );
        }

        return new ParsedResource($sanitizedName, $sanitizedModule);
    }

    /**
     * Remove caracteres indesejados
     *
     * @param string $value
     * @return string
     */
    private function sanitize(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9_\/\\\]/', '', $value);
    }
}
