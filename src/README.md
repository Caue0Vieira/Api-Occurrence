# 🚒 API de Gerenciamento de Ocorrências - Corpo de Bombeiros

> Sistema de gerenciamento de ocorrências operacionais do Corpo de Bombeiros com arquitetura **DDD + Hexagonal**, garantindo escalabilidade, testabilidade e manutenibilidade.

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?logo=postgresql)
![RabbitMQ](https://img.shields.io/badge/RabbitMQ-3-FF6600?logo=rabbitmq)
![Redis](https://img.shields.io/badge/Redis-7-DC382D?logo=redis)

---

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Arquitetura](#arquitetura)
- [Funcionalidades](#funcionalidades)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [API Endpoints](#api-endpoints)
- [Testes](#testes)
- [Estrutura do Projeto](#estrutura-do-projeto)

---

## 🎯 Visão Geral

API HTTP responsável por:

- ✅ Receber ocorrências de sistemas externos
- ✅ Gerenciar ciclo de vida das ocorrências
- ✅ Despachar equipes/viaturas
- ✅ Garantir idempotência nas operações
- ✅ Registrar comandos e eventos de saída (Outbox) para processamento assíncrono
- ✅ Retornar respostas rápidas (202 Accepted)

---

## 🏛️ Arquitetura

### Domain-Driven Design (DDD)
- **Entidades**: Occurrence, Dispatch
- **Value Objects**: OccurrenceType, OccurrenceStatus, DispatchStatus, Uuid (v7)
- **Agregados**: Occurrence como raiz
- **Repositórios**: Abstrações para persistência
- **Eventos de Domínio**: OccurrenceCreated, OccurrenceStarted, OccurrenceResolved, DispatchCreated

### Arquitetura Hexagonal (Ports & Adapters)
```
┌─────────────────────────────────────────────────────────┐
│                    Presentation                          │
│   (HTTP Controllers, Middlewares, Requests, Resources)  │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│                    Application                           │
│       (DTOs, Ports e Support utilities)                 │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│                      Domain                              │
│  (Entities, Value Objects, Repositories, Events)        │
│              ⚠️ ZERO dependências externas               │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│                 Infrastructure                           │
│   (Eloquent, RabbitMQ, Redis, Services, Adapters)      │
└─────────────────────────────────────────────────────────┘
```

**Leia mais**: [ARCHITECTURE.md](app/ARCHITECTURE.md)

---

## ⚡ Funcionalidades

### 🔐 Autenticação
- **X-API-Key**: Todas as rotas exigem autenticação via header
- **Rate Limiting**: 100 requisições/minuto por API Key

### 🔄 Idempotência
- **Idempotency-Key**: Obrigatório em operações de escrita (POST/PUT/PATCH)
- **Command Inbox**: Registra comandos para evitar duplicação
- **TTL**: 24 horas para cache de idempotência

### 📤 Outbox Pattern (publicação assíncrona)
- **Outbox**: A API registra eventos de publicação na tabela `outbox`
- **Status inicial**: Eventos entram como `PENDING`
- **Desacoplamento**: A API não publica diretamente no broker
- **Worker publicador**: Processo dedicado consulta `outbox` e envia para fila

### 📊 Domínio

#### Occurrence (Ocorrência)
- `id` (UUID v7)
- `external_id` (ID do sistema externo)
- `type` (incendio_urbano, resgate_veicular, etc)
- `status` (reported, in_progress, resolved, cancelled)
- `description`
- `reported_at`

#### Dispatch (Despacho)
- `id` (UUID v7)
- `occurrence_id`
- `resource_code` (ex: ABT-12, UR-05)
- `status` (assigned, en_route, on_site, closed)

---

## 📦 Requisitos

- **PHP**: 8.2+
- **Composer**: 2.x
- **PostgreSQL**: 16+
- **Redis**: 7+
- **RabbitMQ**: 3.x
- **Docker** (opcional, mas recomendado)

---

## 🚀 Instalação

### Usando Docker (Recomendado)

```bash
# 1. Subir containers
cd docker
docker-compose up -d

# 2. Instalar dependências
docker exec -it api_occurrence bash
composer install

# 3. Configurar ambiente
cp .env.example .env
php artisan key:generate

# 4. Executar migrations
php artisan migrate

# 5. Testar
php artisan test
```

### Instalação Manual

```bash
# 1. Instalar dependências
composer install

# 2. Configurar .env
cp .env.example .env
# Edite o .env com suas configurações (ver ENV_VARIABLES.md)

# 3. Gerar chave da aplicação
php artisan key:generate

# 4. Executar migrations
php artisan migrate

# 5. Iniciar servidor
php artisan serve
```

---

## ⚙️ Configuração

### Variáveis de Ambiente Essenciais

```env
# API Keys
API_KEY_MAIN=your-main-api-key-here
API_KEY_EXTERNAL=external-system-key
API_KEY_INTERNAL=internal-frontend-key

# PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=occurrence_db
DB_USERNAME=occurrence_user
DB_PASSWORD=occurrence_pass

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# RabbitMQ
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=occurrence_user
RABBITMQ_PASSWORD=occurrence_pass
```

**Documentação completa**: [ENV_VARIABLES.md](ENV_VARIABLES.md)

---

## 📡 API Endpoints

### 🔹 Health Check

```http
GET /api/health
```

**Resposta**:
```json
{
  "status": "ok",
  "message": "API is running",
  "timestamp": "2026-02-11T10:30:00-03:00",
  "version": "1.0.0"
}
```

---

### 🔹 Integração Externa

#### Criar Ocorrência

```http
POST /api/integrations/occurrences
X-API-Key: {sua-api-key}
Idempotency-Key: {chave-unica}
Content-Type: application/json

{
  "externalId": "EXT-2026-000123",
  "type": "incendio_urbano",
  "description": "Incêndio em residência na Rua ABC, 123",
  "reportedAt": "2026-02-01T14:32:00-03:00"
}
```

**Resposta (202 Accepted)**:
```json
{
  "command_id": "01934b8f-...",
  "status": "RECEIVED"
}
```

Use o endpoint `GET /api/commands/{command_id}` para acompanhar a evolução
do comando (`RECEIVED`, `PROCESSING`, `SUCCEEDED`, `FAILED`).

---

### 🔹 API Interna

#### Listar Ocorrências

```http
GET /api/occurrences?status=in_progress&type=incendio_urbano&page=1&limit=50
X-API-Key: {sua-api-key}
```

**Resposta (200 OK)**:
```json
{
  "data": [
    {
      "id": "01934b8f-...",
      "external_id": "EXT-2026-000123",
      "type": "incendio_urbano",
      "status": "in_progress",
      "description": "Incêndio em residência",
      "reported_at": "2026-02-01T14:32:00-03:00",
      "created_at": "2026-02-01T14:32:05-03:00",
      "updated_at": "2026-02-01T14:35:00-03:00"
    }
  ],
  "meta": {
    "total": 15,
    "page": 1,
    "limit": 50,
    "pages": 1
  }
}
```

#### Detalhes da Ocorrência

```http
GET /api/occurrences/{id}
X-API-Key: {sua-api-key}
```

#### Iniciar Atendimento

```http
POST /api/occurrences/{id}/start
X-API-Key: {sua-api-key}
Idempotency-Key: {chave-unica}
```

#### Resolver Ocorrência

```http
POST /api/occurrences/{id}/resolve
X-API-Key: {sua-api-key}
Idempotency-Key: {chave-unica}
```

#### Criar Despacho

```http
POST /api/occurrences/{id}/dispatches
X-API-Key: {sua-api-key}
Idempotency-Key: {chave-unica}
Content-Type: application/json

{
  "resourceCode": "ABT-12"
}
```

---

## 🧪 Testes

```bash
# Executar todos os testes
php artisan test

# Testes com cobertura
php artisan test --coverage

# Testes específicos
php artisan test --filter OccurrenceTest

# Testes unitários apenas
php artisan test --testsuite Unit

# Testes de feature apenas
php artisan test --testsuite Feature
```

---

## 📁 Estrutura do Projeto

```
src/
├── app/
│   ├── Http/                        # Controllers, requests e recursos
│   └── Providers/                   # Bindings de dependências
├── Application/
│   ├── DTOs/                        # Objetos de transferência
│   ├── Ports/                       # Interfaces de integração
│   └── Support/                     # Utilitários de aplicação
├── Domain/
│   ├── Occurrence/
│   ├── Dispatch/
│   ├── Idempotency/
│   └── Shared/
├── Infrastructure/
│   ├── Persistence/                 # Repositories concretos
│   ├── Cache/
│   └── Support/
├── database/migrations/
├── routes/
└── tests/
```

---

## 🛡️ Segurança

- ✅ Autenticação via API Key
- ✅ Rate Limiting (100 req/min)
- ✅ Validação rigorosa de entrada
- ✅ Proteção contra duplicação (idempotência)
- ✅ Auditoria de ações

---

## 📚 Documentação Adicional

- [Arquitetura Detalhada](app/ARCHITECTURE.md)
- [Variáveis de Ambiente](ENV_VARIABLES.md)
- [Exemplos de Requisições](docs/api-examples.md) _(a criar)_

---

## 🤝 Contribuindo

Este projeto segue princípios de **Clean Code** e **SOLID**. Contribuições são bem-vindas!

### Padrões
- ✅ PSR-12 (Code Style)
- ✅ Type Hints obrigatórios
- ✅ Testes para novas funcionalidades
- ✅ Documentação atualizada

---

## 📄 Licença

MIT License

---

## 👥 Equipe de Desenvolvimento

**Desenvolvido com ❤️ para o Corpo de Bombeiros**

---

**Versão**: 1.0.0  
**Última atualização**: 2026-02-11
