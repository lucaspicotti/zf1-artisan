# Zend Framework 1 Artisan (zf1-artisan)

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

Esse pacote ajuda desenvolvedores a acelerar o desenvolvimento do dia a dia automatizando a criação de arquivos boilerplate (controllers, models, formulários, crons, recursos de API) e fornecendo utilitários para listar, executar crons e diagnosticar a inicialização do framework.

---

## 🌟 Recursos Principais

- **Autodescoberta e Execução de Crons:** Lista todas as crons registradas no sistema de maneira visual e tabular, e permite executá-las manualmente (incluindo tarefas direcionadas a operadores específicos ou a todos de uma vez).
- **Geradores Boilerplate (`make:*`):** Criação rápida de classes do ZF1 utilizando templates prontos (stubs) e respeitando o padrão de convenção de nomes de classes do PSR-0 / ZF1 (usando underscores `_` no lugar de namespaces).
- **Diagnóstico e Debug (`debug:bootstrap`):** Ferramenta de análise passo a passo para certificar que caminhos, arquivos de bootstrap (`BootstrapCron.php`, `Bootstrap.php`), banco de dados, drivers de cache e include paths do PHP estejam configurados corretamente.

---

## 🚀 Instalação

Adicione o pacote ao seu projeto ZF1 via Composer:

```bash
composer require lucaspicotti/zf1-artisan
```

---

## 🛠️ Configuração Inicial

Para rodar os comandos a partir da raiz do seu projeto e definir as variáveis de ambiente necessárias (`APPLICATION_PATH` e `APPLICATION_NAME`) de forma segura, crie um arquivo executável chamado `artisan` na raiz do seu projeto com o seguinte conteúdo:

```php
#!/usr/bin/env php
<?php

// Define as variáveis de ambiente necessárias para o console de forma isolada
if (empty($_ENV['APPLICATION_NAME']) && empty($_SERVER['APPLICATION_NAME'])) {
    $_ENV['APPLICATION_NAME'] = 'Minha Aplicacao ZF1';
    $_SERVER['APPLICATION_NAME'] = 'Minha Aplicacao ZF1';
    putenv('APPLICATION_NAME=Minha Aplicacao ZF1');
}

if (empty($_ENV['APPLICATION_PATH']) && empty($_SERVER['APPLICATION_PATH'])) {
    $defaultPath = __DIR__ . '/application';
    $_ENV['APPLICATION_PATH'] = $defaultPath;
    $_SERVER['APPLICATION_PATH'] = $defaultPath;
    putenv("APPLICATION_PATH={$defaultPath}");
}

// Carrega o script original do pacote
require __DIR__ . '/vendor/lucaspicotti/zf1-artisan/artisan';
```

Dê permissão de execução ao script:
```bash
chmod +x artisan
```

---

## 📖 Como Usar

Para executar o artisan a partir do diretório raiz do seu projeto:

```bash
php artisan
# ou
./artisan
```

### Comandos Disponíveis

#### 🛠️ Geração de Código (`make:*`)
*   **Gerar Controller:** `php artisan make:controller NomeDoController`
*   **Gerar Model:** `php artisan make:model NomeDoModel`
*   **Gerar Form:** `php artisan make:form NomeDoForm`
*   **Gerar Cron:** `php artisan make:cron NomeDaCron`
*   **Gerar API:** `php artisan make:api v1/NomeDaApi`

#### 📅 Gerenciador de Cron (`cron:*`)
*   **Listar Crons Cadastradas:** `php artisan cron:list`
    *   *Opção interativa:* `php artisan cron:list -i` (permite selecionar e rodar diretamente da tabela)
*   **Executar Rotina Cron:** `php artisan cron:run NomeDaRotina`
    *   *Forçar execução:* `php artisan cron:run NomeDaRotina --force` (ignora travas/locks ativos)
    *   *Para operador específico:* `php artisan cron:run NomeDaRotina --operator=123`

#### 🔍 Diagnóstico (`debug:*`)
*   **Testar Bootstrap:** `php artisan debug:bootstrap`

---

## 📄 Documentação Detalhada

Para conferir exemplos práticos, regras gerais de funcionamento, convenções de pastas e resolução de nomes de classes do framework, veja a [Documentação Completa](doc.md).

## ⚖️ Licença

Este projeto é licenciado sob a licença MIT - consulte o arquivo [LICENSE](LICENSE) para obter detalhes.
