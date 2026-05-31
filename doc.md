# Documentação do Gerador de Código (zf1-artisan)

Este pacote automatiza a criação de novos arquivos (controllers, models, forms e crons) para aplicações estruturadas em Zend Framework 1 (ZF1).

O gerador suporta a criação de arquivos tanto na raiz de diretórios globais quanto organizados em subpastas ou agrupamentos lógicos (módulos de nomenclatura), sem forçar o uso da pasta `/modules/` na estrutura de arquivos física.

---

## 📌 Regras Gerais de Funcionamento

1. **Normalização de Extensão:** Se você digitar a extensão `.php` no comando por costume (ex: `make:cron teste/MinhaCron.php`), o gerador remove automaticamente o `.php` para evitar duplicidade de nomes.
2. **Organização por Pastas:** O primeiro segmento antes de uma barra `/` é interpretado como o "Módulo de Nomenclatura". O restante do caminho é tratado como o nome e subpastas do recurso.
3. **Resolução de Caminhos:** Todos os arquivos são gerados sob os diretórios globais correspondentes em `application/` (ex: `application/cron/`, `application/controllers/`, etc.), criando pastas intermediárias automaticamente.
4. **Nomes de Classes PHP:** As barras do caminho `/` são convertidas em underscores (`_`) para manter compatibilidade com o autoloader clássico do PSR-0 / ZF1.

---

## 🚀 Exemplos Práticos por Comando

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

---

### 2. Formulários (`make:form`)
*Caminho base de destino:* `application/forms/` (ou `application/Forms/`)

*   **Sem Módulo:**
    *   **Comando:** `php artisan make:form Contato`
    *   **Arquivo Gerado:** `application/forms/Contato.php`
    *   **Classe PHP:** `class Form_Contato extends ...`
*   **Com Módulo/Subpasta:**
    *   **Comando:** `php artisan make:form portal/auth/login`
    *   **Arquivo Gerado:** `application/forms/portal/Auth/Login.php`
    *   **Classe PHP:** `class Form_Portal_Auth_Login extends ...`

---

### 3. Models (`make:model`)
*Caminho base de destino:* `application/models/` (ou `application/Models/`)

*   **Sem Módulo:**
    *   **Comando:** `php artisan make:model Produto`
    *   **Arquivo Gerado:** `application/models/Produto.php`
    *   **Classe PHP:** `class Produto extends Zend_Db_Table`
---

### 4. Controllers (`make:controller`)
*Caminho base de destino:* `application/controllers/` (ou `application/Controllers/`)

*   **Sem Módulo:**
    *   **Comando:** `php artisan make:controller Index`
    *   **Arquivo Gerado:** `application/controllers/IndexController.php`
    *   **Classe PHP:** `class IndexController extends Zend_Controller_Action`
*   **Com Módulo/Subpasta:**
    *   **Comando:** `php artisan make:controller admin/relatorio/financeiro`
    *   **Arquivo Gerado:** `application/controllers/admin/Relatorio/FinanceiroController.php`
    *   **Classe PHP:** `class Admin_Relatorio_FinanceiroController extends ...`
