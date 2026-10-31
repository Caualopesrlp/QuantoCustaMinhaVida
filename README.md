# QuantoCustaMinhaVida

Aplicação web fullstack de **controle financeiro pessoal e planejamento orçamentário**, construída com **Laravel 12 (PHP 8.3+)**, **Blade + Tailwind CSS v4 + Alpine.js (Laravel Breeze)**, **PostgreSQL 16** e **Docker Compose**.

Projetada sob a premissa de **Custo Zero** e arquitetura limpa (**MVC + Service Layer + Repository Pattern + SOLID/GoF**), a plataforma substitui planilhas manuais por um fluxo automatizado, multiusuário (com isolamento estrito de dados por perfil) e responsivo para celular e desktop, com suporte nativo a **Modo Claro e Modo Escuro (Dark Mode)**.

---

## Sumário

1. [Visão Geral & Diferenciais](#-visão-geral--diferenciais)
2. [Funcionalidades Principais](#-funcionalidades-principais)
3. [Arquitetura & Padrões de Projeto](#-arquitetura--padrões-de-projeto)
4. [Stack Tecnológica](#-stack-tecnológica)
5. [Estrutura do Projeto](#-estrutura-do-projeto)
6. [Modelagem do Banco de Dados](#-modelagem-do-banco-de-dados)
7. [Como Executar o Projeto](#-como-executar-o-projeto)
8. [Importação de Planilha Excel (.xlsx)](#-importação-de-planilha-excel-xlsx)
9. [Configuração Gratuita de E-mail (Recuperação de Senha)](#-configuração-gratuita-de-e-mail-recuperação-de-senha)
10. [Mapa de Rotas](#-mapa-de-rotas)
11. [Testes Automatizados](#-testes-automatizados)

---

## Visão Geral & Diferenciais

- **Custo Zero (`R$ 0,00`):** Toda a stack de desenvolvimento, banco de dados, leitura de planilhas `.xlsx` e containerização utiliza exclusivamente ferramentas gratuitas e open-source, sem dependência de bibliotecas comerciais ou APIs pagas.
- **Precisão Financeira em Centavos (`bigint`):** Nenhum valor monetário utiliza ponto flutuante (`float`) no banco de dados. Todos os valores são convertidos, armazenados e calculados como inteiros em centavos (`amount_cents`) através do utilitário [`App\Support\Money`](app/Support/Money.php), eliminando erros de arredondamento.
- **Isolamento Estrito Multiusuário:** Cada usuário possui suas próprias categorias, tipos, molde mensal, metas orçamentárias e transações. Qualquer alteração de categoria, tipo ou lançamento afeta única e exclusivamente o perfil autenticado (`user_id`).
- **Interface Moderna (Emerald & Slate + Dark Mode):** Paleta visual financeira em Verde-Esmeralda (`emerald`), Azul-Petróleo (`teal`) e Ardósia (`slate`), com alternância instantânea entre tema claro e escuro persistida via `localStorage`.

---

## Funcionalidades Principais

### 1. Controle Mensal (`/dashboard` e `/financas/{year?}/{month?}`)
- **5 Indicadores de Resumo no Topo:**
  - `Receita Total` (100% da base mensal)
  - `(-) Despesas Fixas` (com percentual sobre a renda)
  - `(-) Despesas Variáveis` (com percentual sobre a renda)
  - `(-) Investimentos` (com percentual sobre a renda)
  - `(=) Saldo do Mês` com badge automático de status (`Sobrando`, `No vermelho` ou `Zero a zero`) e indicador de **Saldo Acumulado Geral** até o mês selecionado.
- **Blocos Dinâmicos por Categoria Principal:**
  - Exibe cada Categoria Principal (as 4 padrão + qualquer categoria personalizada criada pelo usuário) com subtotal consolidado, lista de tipos, resumo de observações e expansão rápida (`▼`) para visualizar, adicionar (`+`), editar inline ou excluir lançamentos individuais.

### 2. Automação do Molde Mensal (`/modelo-fixo`)
- Cadastro único de receitas e despesas recorrentes (ex: *Salário, Aluguel, Internet, Transporte Público*).
- **Injeção 100% Automática e Idempotente:** Ao abrir qualquer mês no Controle Mensal, o serviço [`MonthlyBudgetService::ensureMonthInitialized()`](app/Services/MonthlyBudgetService.php) verifica todos os itens ativos do Molde Mensal e cria automaticamente os lançamentos que ainda não existirem naquele mês — sem necessidade de botão manual e sem duplicar itens já lançados.
- Alterar o valor de um lançamento gerado em um mês específico **não altera** o histórico dos meses anteriores.

### 3. Gestão Dinâmica de Categorias Principais e Tipos (`/categorias`)
Estrutura flexível em **2 níveis** (exclusiva por perfil de usuário):
1. **Adicionar Novo Tipo em Categoria Existente:** Permite incluir rapidamente um novo tipo dentro de qualquer categoria já existente (ex: adicionar **Gasolina** dentro de **Despesas Fixas**, ou **Academia** dentro de **Despesas Variáveis**).
2. **Criar Nova Categoria Principal:** Permite criar categorias principais além das 4 padrão (ex: **Moradia**, **Veículo**, **Educação**, **Projetos**), definindo sua cor, sua natureza no cálculo do saldo (`Entrada`, `Saída Fixa`, `Saída Variável` ou `Investimento`) e seus tipos iniciais.
3. **Renomear, Editar Cor/Natureza e Remover/Arquivar:** Tipos sem lançamentos vinculados são excluídos; tipos que já possuem histórico financeiro são arquivados automaticamente para preservar a integridade dos relatórios passados.

### 4. Regra Orçamentária 50-30-20 Personalizável
- Comparativo em tempo real no Controle Mensal entre o **Valor Ideal** e o **Valor Realizado** em:
  - **Necessidades / Custos Fixos** (padrão: `50%`)
  - **Desejos / Custos Variáveis** (padrão: `30%`)
  - **Investimentos & Futuro** (padrão: `20%`)
- Os percentuais podem ser customizados a qualquer momento pelo usuário na tela `/categorias` (validando soma igual a `100%`).

### 5. Carteira de Investimentos Independente (`/investimentos/{year?}`)
- Tela exclusiva para acompanhamento de patrimônio aportado no ano:
  - **KPIs:** Total Investido no Ano, Média por Mês Aportado, Taxa de Investimento sobre a Receita Anual e Constância (`X / 12 meses`).
  - **Tabela de Evolução Mensal (Jan–Dez):** Aporte de cada mês, saldo acumulado no ano e resumo dos ativos/observações (ex: *CDB Nubank 100% CDI*).
  - **Listagem Detalhada de Aportes:** Todos os lançamentos de investimento do ano com data, tipo e valor.

### 6. Resumo Anual Independente (`/ano/{year?}`)
- Tela exclusiva de balanço anual consolidado:
  - **Cards Consolidados:** Receita Acumulada no Ano, Soma de Despesas Fixas + Variáveis, Total Investido e Saldo Livre do Ano.
  - **Balanço Mês a Mês (Jan–Dez):** Visão matricial completa de Receitas, Despesas Fixas, Despesas Variáveis, Investimentos e Saldo Líquido de cada mês, com link direto para o respectivo Controle Mensal.

### 7. Painel Admin & Gestão de Usuários (`/admin/users`)
- Protegido pelo middleware [`AdminMiddleware`](app/Http/Middleware/AdminMiddleware.php) (`role = 'admin'` / `is_admin = true`).
- Permite ao administrador:
  - Visualizar todos os usuários cadastrados.
  - Cadastrar novos usuários definindo nome, e-mail, perfil (`Usuário Padrão` ou `Administrador`) e senha inicial (que já recebem automaticamente as categorias padrão via evento de domínio no model [`User`](app/Models/User.php)).
  - **Resetar Senha em 1 Clique:** Redefine a senha de qualquer usuário que tenha esquecido suas credenciais para a senha temporária padrão **`12345678`**.

---

## Arquitetura & Padrões de Projeto

O projeto segue rigorosamente a separação de responsabilidades em camadas (**MVC + Service + Repository Pattern**), respeitando os princípios **SOLID** e padrões **GoF**:

```text
Requisição HTTP
   │
   ▼
Middleware (Auth / AdminMiddleware) & FormRequests (Validação de Entrada)
   │
   ▼
Thin Controllers (Apenas orquestram Request -> Service -> View/Redirect)
   │
   ▼
Service Layer (100% das Regras de Negócio, Cálculos, Isolamento e Transações)
   ├── MonthlyDashboardService
   ├── MonthlyBudgetService
   ├── CategoryService
   ├── TransactionService
   ├── RecurringTemplateService
   ├── AnnualInvestmentService
   └── AdminUserService
   │
   ▼
Repository Layer (Abstração de Persistência via Interfaces injetadas no IoC Container)
   ├── CategoryRepositoryInterface          ──► EloquentCategoryRepository
   ├── TransactionRepositoryInterface       ──► EloquentTransactionRepository
   └── RecurringTemplateRepositoryInterface ──► EloquentRecurringTemplateRepository
   │
   ▼
Models Eloquent & Banco de Dados (PostgreSQL 16 / SQLite)
```

### Padrões de Projeto Aplicados
- **Repository Pattern + Dependency Injection:** Todos os repositórios são injetados nas Services através de contratos (`app/Repositories/Contracts/*`) registrados no [`AppServiceProvider`](app/Providers/AppServiceProvider.php).
- **Strategy Pattern:** Cálculo da regra orçamentária abstraído pela interface [`BudgetRuleCalculatorInterface`](app/Strategies/BudgetRule/BudgetRuleCalculatorInterface.php) e implementado por [`PercentageBudgetRuleCalculator`](app/Strategies/BudgetRule/PercentageBudgetRuleCalculator.php).
- **Factory Pattern:** Construção padronizada de transações reais a partir de moldes recorrentes via [`RecurringTransactionFactory`](app/Factories/RecurringTransactionFactory.php).
- **Money Pattern (Inteiros em Centavos):** Conversão segura de entradas no formato brasileiro (`1.011,25` -> `101125`) e formatação (`101125` -> `R$ 1.011,25`) centralizadas em [`App\Support\Money`](app/Support/Money.php).

---

## Stack Tecnológica

| Camada | Tecnologia |
| :--- | :--- |
| **Linguagem & Runtime** | PHP 8.3+ (`declare(strict_types=1)`) |
| **Framework Backend** | Laravel 12.x |
| **Autenticação & Perfil** | Laravel Breeze |
| **Frontend & UI** | Blade Templates + Tailwind CSS v4 + Alpine.js v3 |
| **Banco de Dados Principal** | PostgreSQL 16 (via Docker) / compatível com SQLite |
| **Servidor Web / Container** | Docker & Docker Compose (`php:8.3-fpm-alpine` + `nginx:alpine` + `postgres:16-alpine`) |
| **Testes Automatizados** | PHPUnit 11.5 (Testes Unitários e de Integração/Feature) |

---

## Estrutura do Projeto

```text
QuantoCustaMinhaVida/
├── app/
│   ├── Console/Commands/
│   │   └── ImportFinanceSpreadsheetCommand.php   # CLI: php artisan finance:import
│   ├── Enums/
│   │   └── CategoryType.php                      # Enum: Income, FixedExpense, VariableExpense, Investment
│   ├── Factories/
│   │   └── RecurringTransactionFactory.php       # Factory de criação de transações do Molde Mensal
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/UserController.php          # Gestão de usuários e reset de senha (Admin)
│   │   │   ├── AnnualInvestmentController.php    # Tela independente de Investimentos (/investimentos)
│   │   │   ├── AnnualSummaryController.php       # Tela independente de Resumo Anual (/ano)
│   │   │   ├── CategoryController.php            # Gestão de Categorias, Tipos e Regra 50-30-20
│   │   │   ├── MonthlyDashboardController.php    # Controle Mensal (/dashboard, /financas/{year}/{month})
│   │   │   ├── RecurringTemplateController.php   # Gestão do Molde Mensal (/modelo-fixo)
│   │   │   └── TransactionController.php         # CRUD de lançamentos financeiros
│   │   ├── Middleware/
│   │   │   └── AdminMiddleware.php               # Proteção de rotas exclusivas de Administrador
│   │   └── Requests/                             # FormRequests de validação
│   ├── Models/
│   │   ├── Category.php
│   │   ├── ExpenseType.php
│   │   ├── RecurringTemplate.php
│   │   ├── Transaction.php
│   │   └── User.php
│   ├── Providers/
│   │   └── AppServiceProvider.php                # Bindings de Interfaces -> Repositories e Strategies
│   ├── Repositories/
│   │   ├── Contracts/                            # Interfaces da camada de Repositório
│   │   └── Eloquent/                             # Implementações Eloquent
│   ├── Services/                                 # Camada de Negócio (Services)
│   ├── Strategies/BudgetRule/                    # Strategy da Regra 50-30-20
│   └── Support/
│       ├──Money.php                             # Conversão e formatação em centavos
│       └── Spreadsheet/SimpleXlsxReader.php      # Leitor nativo de .xlsx sem pacotes externos
├── database/
│   ├── migrations/                               # Migrations do banco de dados
│   └── seeders/
│       ├── AdminUserSeeder.php                   # Garante permissão de Admin na conta principal
│       └── DatabaseSeeder.php
├── docker/
│   ├── nginx/default.conf                        # Configuração do Nginx
│   └── php/php.ini                               # Configuração customizada do PHP 8.3
├── resources/views/
│   ├── admin/users/index.blade.php               # Painel Admin
│   ├── annual/index.blade.php                    # Resumo Anual (/ano)
│   ├── auth/login.blade.php                      # Tela de Login customizada
│   ├── categories/index.blade.php                # Categorias Principais, Tipos e Regra 50-30-20
│   ├── dashboard/monthly.blade.php               # Controle Mensal
│   ├── investments/annual.blade.php              # Carteira de Investimentos (/investimentos)
│   ├── profile/                                  # Meu Perfil (Dark Mode)
│   └── recurring/index.blade.php                 # Molde Mensal (/modelo-fixo)
├── routes/
│   ├── auth.php                                  # Rotas de autenticação
│   └── web.php                                   # Rotas principais e administrativas
├── tests/
│   ├── Feature/
│   │   └── FinancialWorkflowAndIsolationTest.php # Testes E2E de fluxo financeiro, isolamento e Admin
│   └── Unit/
│       └── MoneyAndBudgetStrategyTest.php        # Testes unitários de centavos e regra 50-30-20
├── Dockerfile
└── docker-compose.yml
```

---

## Modelagem do Banco de Dados

| Tabela | Descrição Principal | Colunas Chave |
| :--- | :--- | :--- |
| **`users`** | Usuários do sistema, permissões e metas da regra 50-30-20 | `id`, `name`, `email`, `password`, `role`, `is_admin`, `rule_needs_pct`, `rule_wants_pct`, `rule_investments_pct` |
| **`categories`** | Categorias Principais (`group_name`) e Tipos (`name`) exclusivos de cada usuário | `id`, `user_id`, `group_name`, `name`, `type`, `color`, `icon`, `sort_order`, `is_active` |
| **`expense_types`** | Subtipos adicionais vinculados a uma categoria | `id`, `user_id`, `category_id`, `name`, `is_active` |
| **`recurring_templates`** | Itens do **Molde Mensal** injetados automaticamente a cada mês | `id`, `user_id`, `category_id`, `description`, `amount_cents`, `day_of_month`, `notes`, `is_active` |
| **`transactions`** | Lançamentos financeiros reais de cada mês/ano | `id`, `user_id`, `category_id`, `expense_type_id`, `recurring_template_id`, `year`, `month`, `occurred_on`, `description`, `amount_cents`, `notes` |

---

## Como Executar o Projeto

### Opção 1: Execução Completa via Docker Compose (Recomendado)

1. **Clone o repositório e configure o `.env`:**
   ```bash
   cp .env.example .env
   ```

2. **Suba os containers (`app`, `web` e `db`):**
   ```bash
   docker compose up -d --build
   ```

3. **Instale as dependências, gere a chave e execute as migrations + seeders:**
   ```bash
   docker compose exec app composer install
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate --seed
   docker compose exec app npm install
   docker compose exec app npm run build
   ```

4. **Acesse no navegador:**
   - URL: [http://localhost:8000](http://localhost:8000)

---

### Opção 2: Execução Híbrida (Banco PostgreSQL no Docker + PHP/Vite Local ou Cloud Shell)

Caso prefira rodar o servidor PHP diretamente na máquina local / Cloud Shell usando apenas o container do PostgreSQL:

1. **Suba apenas o banco PostgreSQL (porta `5433` no host):**
   ```bash
   docker compose up -d db
   ```

2. **Certifique-se de que a extensão `pdo_pgsql` está ativa no PHP local e configure no `.env`:**
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5433
   DB_DATABASE=qcmv
   DB_USERNAME=qcmv
   DB_PASSWORD=secret
   ```

3. **Rode as migrations, compile os assets e inicie o servidor:**
   ```bash
   php artisan migrate --seed
   npm run build
   php artisan serve --host=0.0.0.0 --port=8000
   ```

---


## Configuração Gratuita de E-mail (Recuperação de Senha)

O fluxo de recuperação de senha por e-mail (`/forgot-password`) já está implementado. Para habilitar o disparo real de e-mails com **custo zero**, basta configurar uma **Senha de App (16 dígitos)** de uma conta Gmail no arquivo `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seuemail@gmail.com
MAIL_PASSWORD="sua-senha-de-app-16-digitos"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=seuemail@gmail.com
MAIL_FROM_NAME="QuantoCustaMinhaVida"
```

*(Alternativamente, serviços gratuitos como **Brevo** — até 300 e-mails/dia grátis — ou **Resend** — até 3.000 e-mails/mês grátis — podem ser usados da mesma forma via SMTP).*

---

## Mapa de Rotas

| Método | URI | Nome da Rota | Descrição |
| :--- | :--- | :--- | :--- |
| `GET` | `/dashboard` | `dashboard` | Abre o Controle Mensal do mês/ano atual |
| `GET` | `/financas/{year?}/{month?}` | `dashboard.monthly` | Controle Mensal de um mês/ano específico (injeta o Molde Mensal automaticamente) |
| `POST` | `/lancamentos` | `transactions.store` | Cria um novo lançamento financeiro no mês |
| `PUT` | `/lancamentos/{transaction}` | `transactions.update` | Atualiza um lançamento existente |
| `DELETE` | `/lancamentos/{transaction}` | `transactions.destroy` | Remove um lançamento |
| `GET` | `/investimentos/{year?}` | `investments.annual` | **Tela Independente:** Carteira e evolução anual de Investimentos |
| `GET` | `/ano/{year?}` | `annual.summary` | **Tela Independente:** Resumo Anual consolidado (Jan–Dez) |
| `GET` | `/modelo-fixo` | `recurring-templates.index` | Gerenciamento do Molde Mensal recorrente |
| `POST` | `/modelo-fixo` | `recurring-templates.store` | Adiciona item ao Molde Mensal |
| `PUT` | `/modelo-fixo/{id}` | `recurring-templates.update` | Edita item do Molde Mensal |
| `DELETE` | `/modelo-fixo/{id}` | `recurring-templates.destroy` | Remove item do Molde Mensal |
| `GET` | `/categorias` | `categories.index` | Gestão de Categorias Principais, Tipos e Regra 50-30-20 |
| `POST` | `/categorias` | `categories.store` | Adiciona novo Tipo em categoria existente ou cria nova Categoria Principal |
| `PUT` | `/categorias/bloco` | `categories.groups.update` | Renomeia ou altera cor/natureza de uma Categoria Principal |
| `PUT` | `/categorias/{category}` | `categories.update` | Renomeia ou altera cor de um Tipo específico |
| `DELETE` | `/categorias/{category}` | `categories.destroy` | Remove ou arquiva um Tipo |
| `PATCH` | `/categorias/regra-orcamento` | `categories.budget-rule.update` | Atualiza os percentuais da Regra 50-30-20 do usuário |
| `GET` | `/admin/users` | `admin.users.index` | **Painel Admin:** Listagem e criação de usuários |
| `POST` | `/admin/users` | `admin.users.store` | **Painel Admin:** Cadastra um novo usuário |
| `POST` | `/admin/users/{user}/reset-password` | `admin.users.reset-password` | **Painel Admin:** Reseta a senha do usuário para `12345678` |
| `GET` | `/profile` | `profile.edit` | Configurações da conta e alteração da própria senha |

---

## Testes Automatizados

A suíte de testes automatizados valida tanto as regras matemáticas unitárias quanto os fluxos completos de ponta a ponta (incluindo isolamento entre usuários e segurança do Painel Admin):

```bash
php artisan test
```

### Cobertura da Suíte
- **[`Tests\Unit\MoneyAndBudgetStrategyTest`](tests/Unit/MoneyAndBudgetStrategyTest.php):**
  - Conversão exata de formatos monetários (`R$ 1.011,25`, `150,50`, `333`) para centavos (`int`) e formatação inversa.
  - Cálculo preciso da Regra 50-30-20 em centavos sem perda de arredondamento.
- **[`Tests\Feature\FinancialWorkflowAndIsolationTest`](tests/Feature/FinancialWorkflowAndIsolationTest.php):**
  - Criação automática das categorias padrão ao registrar um novo usuário.
  - Injeção automática do Molde Mensal ao abrir um mês (inclusive adicionando novos itens do molde em meses já iniciados sem duplicar lançamentos).
  - Adição de novos Tipos dentro de Categorias existentes (ex: `Gasolina Aditivada` em `Despesas Fixas`) e criação de novas Categorias Principais personalizadas.
  - Isolamento multiusuário estrito (`403 Forbidden` ao tentar manipular dados de outro usuário e invisibilidade total de categorias/transações alheias).
  - Controle de acesso ao Painel Admin (`/admin/users`) e redefinição de senha para `12345678`.
- **`Tests\Feature\Auth\*` & `Tests\Feature\ProfileTest`:**
  - Autenticação, registro, atualização de perfil, troca de senha e recuperação de acesso.
