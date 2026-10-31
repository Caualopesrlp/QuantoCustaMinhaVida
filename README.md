# 💸 QuantoCustaMinhaVida

Aplicação web de controle financeiro pessoal construída com **Laravel**, **Blade + Alpine.js + Tailwind CSS (Laravel Breeze)** e **Docker Compose**, projetada para substituir planilhas manuais por um fluxo rápido, automatizado e de **Custo Zero**.

---

## 🚀 Funcionalidades Principais

- **Controle Mensal Unificado:**
  - Cards de resumo automático (`Receita`, `(-) Fixas`, `(-) Variáveis`, `(-) Investimentos`, `= Saldo do Mês` com status `✅ Sobrando 😊`, `⚠️ No vermelho` ou `🟰 Zero a zero`).
  - Indicador de **Saldo Acumulado Geral** entre os meses.
  - Agrupamento por categoria com expansão rápida para visualizar, adicionar, editar ou excluir lançamentos individuais.
- **Modelo Fixo Mensal (Substituto da Aba `X26`):**
  - Cadastro único de receitas e despesas recorrentes (Salário, Celular, Corte de Cabelo, Transporte Público, etc.).
  - Geração em 1 clique no início do mês via `RecurringTransactionFactory` (idempotente e editável no mês sem afetar meses anteriores).
- **Regra 50-30-20 Dinâmica & Personalizável:**
  - Cálculo automático sobre o total de receitas do mês via `BudgetRuleCalculatorInterface` (Strategy Pattern).
  - Metas percentuais personalizáveis por usuário (ex: `50 / 30 / 20` ou `60 / 30 / 10`).
- **Histórico Anual de Investimentos (`Investimentos 📈`):**
  - Sincronizado automaticamente com os lançamentos de investimento de cada mês (sem redigitação), exibindo aportes de Janeiro a Dezembro, acumulado no ano e observações.
- **Precisão Financeira:**
  - Todos os valores monetários são armazenados e calculados em **centavos (`bigint`)**, eliminando erros de ponto flutuante.

---

## 🐳 Como Executar (Docker Compose)

1. Suba os containers:
   ```bash
   docker compose up -d --build
   ```
2. Execute as migrations:
   ```bash
   docker compose exec app php artisan migrate
   ```
3. (Opcional) Importe sua planilha local `.xlsx` (sem expor seus dados no Git):
   ```bash
   docker compose exec app php artisan finance:import "Finanças.xlsx" --email="caualopes.icm@gmail.com" --name="Cauã Lopes"
   ```
4. Acesse em: [http://localhost:8000](http://localhost:8000)

---

## 🧪 Testes Automatizados

```bash
docker compose exec app php artisan test
```
