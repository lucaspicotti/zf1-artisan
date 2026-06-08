# Documentação do Gerador de Código e Ferramentas CLI (zf1-artisan)

Este pacote automatiza a criação de novos arquivos (controllers, models, forms, crons e APIs) para aplicações estruturadas em Zend Framework 1 (ZF1), além de fornecer comandos para listar/executar tarefas agendadas (crons) e depurar a inicialização (bootstrap) do framework.

---

## 📌 Arquitetura & Kernel do Sistema

A partir da versão mais recente, o projeto introduziu o **`App\Console\Kernel`**, responsável pela inicialização centralizada:

1. **Carregamento Automático de Comandos:** O kernel varre recursivamente a pasta `src/Console/Commands/` e carrega dinamicamente qualquer arquivo terminado com `Command.php`.
2. **Inicialização do Ambiente:** Identifica e configura automaticamente a variável `APPLICATION_PATH` caso ela não esteja previamente definida no ambiente (via CLI, script proxy ou shell), apontando-a por padrão para a pasta pai `/application/`.
3. **Distribuição Global (Composer):** O comando `artisan` foi registrado no bloco `bin` do `composer.json`, permitindo seu uso simplificado via `vendor/bin/artisan` em projetos onde for instalado.

---

## 🚀 Comandos de Geração (`make:`)

Os comandos abaixo automatizam a criação de estruturas de código seguindo o padrão clássico do ZF1/PSR-0 (com underscores em vez de namespaces). O primeiro segmento antes de uma barra `/` é interpretado como o **Módulo de Nomenclatura**.

### 1. Crons (`make:cron`)
*Caminho base de destino:* `application/cron/`

*   **Sem Módulo:**
    *   **Comando:** `php artisan make:cron EnviarEmails`
    *   **Arquivo Gerado:** `application/cron/EnviarEmails.php`
    *   **Classe PHP:** `class Henger_Plugin_Cron_EnviarEmails extends Cron_Abstract`
*   **Com Módulo/Subpasta:**
    *   **Comando:** `php artisan make:cron teste/ProcessarFaturas`
    *   **Arquivo Gerado:** `application/cron/teste/ProcessarFaturas.php`
    *   **Classe PHP:** `class Teste_ProcessarFaturas extends Cron_Abstract`

### 2. Formulários (`make:form`)
*Caminho base de destino:* `application/forms/`

*   **Sem Módulo:**
    *   **Comando:** `php artisan make:form Contato`
    *   **Arquivo Gerado:** `application/forms/Contato.php`
    *   **Classe PHP:** `class Form_Contato extends Zend_Form`
*   **Com Módulo/Subpasta:**
    *   **Comando:** `php artisan make:form portal/auth/login`
    *   **Arquivo Gerado:** `application/forms/portal/Auth/Login.php`
    *   **Classe PHP:** `class Form_Portal_Auth_Login extends Zend_Form`

### 3. Models (`make:model`)
*Caminho base de destino:* `application/models/`

*   **Sem Módulo:**
    *   **Comando:** `php artisan make:model Produto`
    *   **Arquivo Gerado:** `application/models/Produto.php`
    *   **Classe PHP:** `class Produto extends Zend_Db_Table`
*   **Com Módulo/Subpasta:**
    *   **Comando:** `php artisan make:model portal/Cliente`
    *   **Arquivo Gerado:** `application/models/portal/Cliente.php`
    *   **Classe PHP:** `class Portal_Cliente extends Zend_Db_Table`

### 4. Controllers (`make:controller`)
*Caminho base de destino:* `application/controllers/`

*   **Sem Módulo:**
    *   **Comando:** `php artisan make:controller Index`
    *   **Arquivo Gerado:** `application/controllers/IndexController.php`
    *   **Classe PHP:** `class IndexController extends Zend_Controller_Action`
*   **Com Módulo/Subpasta:**
    *   **Comando:** `php artisan make:controller admin/relatorio/financeiro`
    *   **Arquivo Gerado:** `application/controllers/admin/Relatorio/FinanceiroController.php`
    *   **Classe PHP:** `class Admin_Relatorio_FinanceiroController extends Zend_Controller_Action`

### 5. Recursos de API (`make:api`)
*Caminho base de destino:* `application/api/`

*   **Comando:** `php artisan make:api v1/Terminal`
*   **Arquivo Gerado:** `application/api/v1/TerminalController.php` (ou similar)
*   **Classe PHP:** `class Api_V1_TerminalController extends Api_V1_AbstractRestController` (caso termine em `Controller`).

---

## 📅 Gerenciamento de Tarefas Cron (`cron:`)

O ecossistema disponibiliza comandos para listagem e execução de crons do ZF1 diretamente pelo terminal.

### 1. Listagem de Crons (`cron:list`)
Lista de forma tabular todas as crons encontradas em `application/cron/`.

*   **Comando:** `php artisan cron:list`
*   **Modo Interativo:** `php artisan cron:list --interactive` ou `cron:list -i` permite selecionar uma rotina da tabela para executá-la ou visualizar detalhes imediatamente.

### 2. Execução de Cron (`cron:run`)
Executa uma rotina específica de forma isolada, dando suporte a tarefas baseadas em operadores.

*   **Comando:** `php artisan cron:run NomeDaRotina`
*   **Forçar Execução:** `php artisan cron:run NomeDaRotina --force` ou `-f` (ignora travamentos de lock ativos e ativa o `setForceRun` na instância se disponível).
*   **Definir Operador:** `php artisan cron:run NomeDaRotina --operator=123` ou `-o 123` (executa apenas para o operador especificado).
*   **Executar para todos os Operadores:** `php artisan cron:run NomeDaRotina --all` ou `-a`.

---

## 🛠️ Ferramentas de Depuração (`debug:`)

### 1. Diagnóstico do Bootstrap (`debug:bootstrap`)
Valida passo a passo a integridade da inicialização do Zend Framework 1 na aplicação onde o artisan está instalado.

*   **Comando:** `php artisan debug:bootstrap`
*   **Etapas validadas:**
    1. Existência e caminho do `APPLICATION_PATH`.
    2. Presença dos arquivos requeridos (`BootstrapCron.php`, `Bootstrap.php` e `config.ini`).
    3. Resolução correta da biblioteca do Zend Framework (`Zend/Application.php`) no PHP `include_path`.
    4. Execução do bootstrap e tratamento de erros do container de serviços.
    5. Teste de conexão do adaptador padrão de banco de dados (`dbAdapter`).
    6. Verificação do driver e integridade do cache registrado.
    7. Detecção e status do Xdebug.

