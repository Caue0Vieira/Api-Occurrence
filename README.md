# 🚒 API de Gerenciamento de Ocorrências

API REST para gerenciamento de ocorrências operacionais do Corpo de Bombeiros, desenvolvida com **Laravel** seguindo **DDD (Domain-Driven Design)** e **Arquitetura Hexagonal**.

## 🚀 Como Rodar

### Pré-requisitos
- Docker e Docker Compose instalados

### Executando com Docker Compose

```bash
cd docker
docker-compose up -d
```

Isso irá subir:
- **API** na porta `8089`
- **PostgreSQL** na porta `5433`
- **Redis** na porta `6379`
- **RabbitMQ** na porta `5672` (Management UI: `15672`)

### Configuração Inicial

Após subir os containers, execute:

```bash
# Entrar no container da API
docker exec -it api_occurrence bash

# Instalar dependências
composer install

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Executar migrations
php artisan migrate --seed

# Gerar documentação Swagger
php artisan l5-swagger:generate
```

## 📚 Documentação Swagger

A documentação completa da API está disponível em:

```
http://localhost:8089/api/documentation
```

O Swagger contém todas as rotas disponíveis, schemas de requisição/resposta e exemplos de uso.

## 🔄 Como Funciona

### Processamento Assíncrono

O sistema utiliza processamento assíncrono para garantir alta performance e resiliência:

1. **API recebe a requisição** → Valida autenticação, payload e idempotência
2. **Registra o comando** → Salva no `command_inbox` com status `pending`
3. **Publica na fila** → Envia comando para RabbitMQ
4. **Retorna resposta** → API responde `202 Accepted` com `command_id`
5. **Worker processa** → Consome a fila e executa as regras de negócio
6. **Atualiza status** → Worker atualiza o comando para `success` ou `failed`

### Idempotência

Todas as operações de escrita exigem o header `Idempotency-Key` para evitar processamento duplicado. O sistema garante que requisições idênticas não sejam processadas mais de uma vez.

### Arquitetura

- **Domain Layer**: Entidades e regras de negócio puras
- **Application Layer**: Use Cases e handlers
- **Infrastructure Layer**: Adaptadores de banco, fila e cache
- **API Layer**: Controllers e validações HTTP

---
