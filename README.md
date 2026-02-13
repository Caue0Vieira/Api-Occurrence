
# 🚒 API de Gerenciamento de Ocorrências — Corpo de Bombeiros

Sistema robusto de gerenciamento de ocorrências operacionais fundamentado em **DDD (Domain-Driven Design)** e **Arquitetura Hexagonal (Ports & Adapters)**. O projeto prioriza o processamento assíncrono e garantias rigorosas de consistência de dados.

## 🏗️ Arquitetura do Ecossistema

O sistema é dividido em três componentes principais:

* **API (Laravel):** Porta de entrada que expõe endpoints, valida integrações e registra intenções de comando.
* **Worker (Laravel):** Núcleo de processamento que consome filas e aplica as regras de negócio complexas.
* **Frontend (React):** Interface administrativa para monitoramento, consulta e detalhamento de ocorrências.

---

## 📋 Índice

* [Visão Geral](#-visão-geral)
* [Como Rodar](#-como-rodar-api--worker--frontend)
* [Desenho de Arquitetura](#-desenho-de-arquitetura)
* [Estratégia de Integração](#-estratégia-de-integração-externa)
* [Garantias do Sistema (Idempotência, Concorrência e Auditoria)](#-idempotência-obrigatório)
* [Testes Automatizados](#-testes-automatizados-obrigatório)
* [Observabilidade e Falhas](#-pontos-de-falha-e-recuperação)

---

## ✅ Visão Geral

### Requisitos Atendidos
* **Processamento Assíncrono:** Separação clara entre recebimento (API) e processamento (Worker).
* **Idempotência:** Proteção nativa contra duplicidade via `Idempotency-Key`.
* **Concorrência:** Tratamento de colisões de estado e transições inválidas.
* **Auditoria Total:** Log rastreável de todas as mudanças de status.
* **Frontend Operacional:** Dashboard para gestão de despachos e histórico.

### Stack Tecnológica
* **Linguagens/Frameworks:** Laravel 12 (PHP 8.2+), React + Vite.
* **Persistência:** PostgreSQL 16.
* **Mensageria & Cache:** RabbitMQ 3 e Redis 7.
* **Infraestrutura:** Docker & Docker Compose.

---

## 🚀 Como Rodar (API + Worker + Frontend)

### 1) Infraestrutura (Postgres/Redis/RabbitMQ)
```bash
cd infra
docker compose up -d

```

* **RabbitMQ UI:** `http://localhost:15672` (user: `occurrence_user`, pass: `occurrence_pass`)

### 2) API (Laravel)

```bash
cd api
cp .env.example .env
composer install
php artisan migrate --seed

```

### 3) Worker (Laravel)

```bash
cd worker
cp .env.example .env
composer install

```

### 4) Frontend (React)

```bash
cd front
npm install
npm run dev

```

---

## 🏛️ Desenho de Arquitetura

### Fluxo de Dados (Visão Macro)

1. **External System** envia um `POST` com `Idempotency-Key`.
2. **API** valida a request, registra no **Command Inbox** (Status: `PENDING`) e publica no **RabbitMQ**.
3. **Worker** consome a fila, aplica **Locks** de banco, executa a **State Machine** de domínio e atualiza o **PostgreSQL**.
4. **Audit Log** é gerado na mesma transação da mudança de estado.

### Organização de Código (DDD/Hexagonal)

* `Domain/`: Entidades, Value Objects e regras de negócio puras.
* `Application/`: Use Cases, Handlers e Portas (Interfaces).
* `Infrastructure/`: Adaptadores de banco, fila e cache.
* `app/Http/`: Controllers e Resources (exclusivo da API).

---

## 🔄 Resiliência e Segurança

### Idempotência

A chave de idempotência é composta por `idempotency_key + type + externalId`.

* **Mesmo Payload:** Retorna o resultado já processado ou o status do processamento.
* **Payload Diferente:** Retorna `409 Conflict` para evitar inconsistência de dados.
* **Armazenamento:** Tabela `command_inbox` com TTL de 24h.

### Concorrência

* **Lock Pessimista:** Uso de `lockForUpdate()` no banco de dados para serializar transições de status.
* **State Machine:** Validação rigorosa (ex: não é permitido "Resolver" uma ocorrência que já consta como "Cancelada").

### Auditoria

O sistema registra automaticamente:

* Entidade afetada, Status anterior/atual, Origem da mudança e `correlation_id`.
* **Garantia Atomica:** A auditoria reside no mesmo commit transacional da alteração de estado.

---

## 🧪 Testes Automatizados

Para garantir a integridade das regras:

```bash
# Na pasta /api ou /worker
php artisan test

```

**Cenários cobertos:**

* Duplicidade de chaves de integração.
* Simulação de múltiplas requisições paralelas (Race Conditions).
* Validação de fluxos de status permitidos e proibidos.

---

## 📈 Evolução Futura

* [ ] **Outbox Pattern:** Para garantir que a publicação na fila nunca falhe se o banco gravar.
* [ ] **CQRS:** Modelos de leitura otimizados em Redis.
* [ ] **Observabilidade:** Implementação de OpenTelemetry para tracing distribuído.

---

**Desenvolvido por Cauê — Software Developer**
