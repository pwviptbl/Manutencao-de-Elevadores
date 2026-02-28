# API Pública — Documentação

> Sistema de Gerenciamento de Manutenção de Elevadores (SaaS)  
> Versão 2.0 | Fevereiro 2026

---

## 1. Visão Geral

A API pública REST permite que qualquer sistema externo (atendimento, ERP, app próprio) interaja com o sistema de gerenciamento de manutenção.

### 1.1 Princípios

| Princípio | Detalhe |
|-----------|---------|
| **REST** | Endpoints seguem convenções REST |
| **JSON** | Request e response sempre em JSON |
| **Versionada** | Prefixo `/api/v1/` — nunca quebrar contrato sem versão nova |
| **Paginada** | Listagens paginadas por padrão (cursor-based) |
| **Idempotente** | POST com header `Idempotency-Key` para evitar duplicatas |
| **Documentada** | OpenAPI/Swagger gerado automaticamente |
| **Rate Limited** | Limites por plano, com headers informativos |
| **Multi-tenant** | Cada API Key vinculada a um tenant; RLS aplicado automaticamente |

### 1.2 Base URL

```
Produção:  https://api.seudominio.com.br/api/v1/
Staging:   https://staging.seudominio.com.br/api/v1/
```

---

## 2. Autenticação

### 2.1 API Keys (MVP)

Cada tenant gera API Keys no painel admin. A key é enviada em toda requisição via header:

```
Authorization: Bearer elev_pk_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
```

#### Formato da API Key

```
elev_pk_{32 caracteres aleatórios}
│    │
│    └── pk = production key (ou sk = sandbox key)
└── prefixo do produto
```

#### Gerenciamento de Keys

| Ação | Endpoint | Método |
|------|----------|--------|
| Criar key | Painel Admin → Configurações → API Keys | UI |
| Listar keys | Painel Admin → Configurações → API Keys | UI |
| Revogar key | Painel Admin → Configurações → API Keys | UI |
| Testar key | `GET /api/v1/auth/me` | API |

### 2.2 Scopes da API Key

Cada key possui scopes que limitam quais operações pode realizar:

| Scope | Descrição |
|-------|-----------|
| `orders:read` | Listar e visualizar chamados |
| `orders:write` | Criar, atualizar e fechar chamados |
| `elevators:read` | Listar e visualizar elevadores |
| `elevators:write` | Criar e atualizar elevadores |
| `condominiums:read` | Listar e visualizar condomínios |
| `condominiums:write` | Criar e atualizar condomínios |
| `technicians:read` | Listar técnicos e disponibilidade |
| `webhooks:manage` | Registrar e remover webhooks |

### 2.3 Verificação de Autenticação

```
GET /api/v1/auth/me
Authorization: Bearer {api_key}
```

**Resposta 200:**

```json
{
  "data": {
    "tenant_id": "uuid-do-tenant",
    "tenant_name": "Empresa XYZ Elevadores",
    "key_name": "Produção - Atendimento",
    "scopes": ["orders:write", "orders:read", "elevators:read"],
    "rate_limit": {
      "limit": 60,
      "remaining": 58,
      "reset_at": "2026-02-28T11:00:00-03:00"
    }
  }
}
```

---

## 3. Rate Limiting

### 3.1 Limites por Plano

| Plano | Requisições/minuto | Burst | Webhooks | API Keys |
|-------|-------------------|-------|----------|----------|
| Starter | ❌ Sem API | — | — | — |
| Pro | 60/min | 10 extra | 3 | 2 |
| Business | 300/min | 50 extra | 10 | 5 |
| Enterprise | Sem limite | — | Ilimitado | Ilimitado |

### 3.2 Headers de Rate Limit

Toda resposta inclui:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
X-RateLimit-Reset: 1709125200
```

**Quando exceder o limite (HTTP 429):**

```
Retry-After: 42
```

```json
{
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Limite de requisições excedido. Tente novamente em 42 segundos.",
    "retry_after": 42
  }
}
```

---

## 4. Formato de Resposta

### 4.1 Resposta de Sucesso (recurso único)

```json
{
  "data": {
    "id": "uuid-do-recurso",
    "type": "service_order",
    "attributes": {
      "priority": "P0",
      "status": "aberto",
      "description": "Elevador parado no 5º andar"
    },
    "relationships": {
      "elevator": { "id": "uuid-elevador", "serial_number": "ELV-001" },
      "condominium": { "id": "uuid-cond", "name": "Residencial Aurora" },
      "technician": null
    },
    "created_at": "2026-02-28T10:30:00-03:00",
    "updated_at": "2026-02-28T10:30:00-03:00"
  },
  "meta": {
    "request_id": "req_abc123"
  }
}
```

### 4.2 Resposta de Sucesso (listagem)

```json
{
  "data": [
    { "id": "uuid-1", "type": "service_order", "attributes": { "..." } },
    { "id": "uuid-2", "type": "service_order", "attributes": { "..." } }
  ],
  "meta": {
    "request_id": "req_abc123",
    "pagination": {
      "total": 150,
      "per_page": 20,
      "current_page": 1,
      "last_page": 8,
      "next_cursor": "eyJpZCI6MjB9",
      "prev_cursor": null
    }
  }
}
```

### 4.3 Resposta de Erro

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Dados inválidos",
    "details": [
      {
        "field": "priority",
        "message": "Valor inválido. Valores aceitos: P0, P1, P2, P3"
      },
      {
        "field": "elevator_id",
        "message": "Elevador não encontrado"
      }
    ]
  },
  "meta": {
    "request_id": "req_abc123"
  }
}
```

### 4.4 Códigos de Erro

| HTTP | Código | Descrição |
|------|--------|-----------|
| 400 | `VALIDATION_ERROR` | Dados inválidos no request |
| 401 | `UNAUTHORIZED` | API Key ausente ou inválida |
| 403 | `FORBIDDEN` | API Key não tem scope necessário |
| 404 | `NOT_FOUND` | Recurso não encontrado |
| 409 | `CONFLICT` | Operação conflitante (ex: status inválido para transição) |
| 422 | `UNPROCESSABLE_ENTITY` | Dados válidos mas operação não permitida |
| 429 | `RATE_LIMIT_EXCEEDED` | Limite de requisições excedido |
| 500 | `INTERNAL_ERROR` | Erro interno do servidor |

---

## 5. Idempotência

Para evitar chamados duplicados em caso de falha de rede, POSTs aceitam o header `Idempotency-Key`:

```
POST /api/v1/orders
Authorization: Bearer {api_key}
Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000
Content-Type: application/json

{ "priority": "P1", "elevator_id": "uuid", "description": "..." }
```

| Comportamento | Detalhe |
|---------------|---------|
| Primeira vez | Cria o recurso normalmente, armazena a key |
| Repetição (mesma key) | Retorna a mesma resposta da primeira vez, sem criar duplicata |
| Expiração | Keys armazenadas por 24 horas |

---

## 6. Endpoints — Chamados (Orders)

### 6.1 Criar Chamado

```
POST /api/v1/orders
Scope: orders:write
```

**Request:**

```json
{
  "priority": "P1",
  "type": "corretiva",
  "elevator_id": "uuid-do-elevador",
  "description": "Elevador fazendo barulho estranho ao fechar porta",
  "source": "api",
  "external_ref": "atendimento_conv_12345",
  "contact_name": "João Silva",
  "contact_phone": "11999998888",
  "notes": "Condomínio reportou problema às 10h"
}
```

| Campo | Obrigatório | Tipo | Descrição |
|-------|-------------|------|-----------|
| `priority` | Sim | Enum | `P0`, `P1`, `P2`, `P3` |
| `type` | Sim | Enum | `corretiva`, `preventiva`, `emergencia` |
| `elevator_id` | Sim | UUID | ID do elevador |
| `description` | Sim | String (max 2000) | Descrição do problema |
| `source` | Não | String | Identificação da origem (default: `api`) |
| `external_ref` | Não | String (max 255) | Referência no sistema externo |
| `contact_name` | Não | String | Nome do contato que reportou |
| `contact_phone` | Não | String | Telefone do contato |
| `notes` | Não | String | Observações adicionais |

**Resposta 201 Created:**

```json
{
  "data": {
    "id": "uuid-do-chamado",
    "type": "service_order",
    "attributes": {
      "priority": "P1",
      "status": "aberto",
      "type": "corretiva",
      "description": "Elevador fazendo barulho estranho ao fechar porta",
      "source": "api",
      "external_ref": "atendimento_conv_12345",
      "contact_name": "João Silva",
      "contact_phone": "11999998888",
      "notes": "Condomínio reportou problema às 10h",
      "sla_deadline": "2026-02-28T18:30:00-03:00"
    },
    "relationships": {
      "elevator": { "id": "uuid-elevador", "serial_number": "ELV-001" },
      "condominium": { "id": "uuid-cond", "name": "Residencial Aurora" },
      "technician": null
    },
    "created_at": "2026-02-28T10:30:00-03:00"
  }
}
```

### 6.2 Listar Chamados

```
GET /api/v1/orders
Scope: orders:read
```

**Parâmetros de query:**

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `status` | String | Filtrar por status (`aberto`, `atribuido`, etc.) |
| `priority` | String | Filtrar por prioridade (`P0`, `P1`, etc.) |
| `elevator_id` | UUID | Filtrar por elevador |
| `condominium_id` | UUID | Filtrar por condomínio |
| `technician_id` | UUID | Filtrar por técnico |
| `source` | String | Filtrar por origem (`api`, `painel`, `importacao`) |
| `created_after` | ISO 8601 | Chamados criados após data |
| `created_before` | ISO 8601 | Chamados criados antes de data |
| `per_page` | Integer (1-100) | Itens por página (default: 20) |
| `cursor` | String | Cursor para paginação |
| `sort` | String | Ordenação: `created_at`, `-created_at`, `priority`, `-priority` |

**Exemplo:**

```
GET /api/v1/orders?status=aberto&priority=P0&sort=-created_at&per_page=10
```

### 6.3 Detalhe do Chamado

```
GET /api/v1/orders/{id}
Scope: orders:read
```

### 6.4 Atualizar Chamado

```
PATCH /api/v1/orders/{id}
Scope: orders:write
```

**Request:**

```json
{
  "description": "Descrição atualizada",
  "notes": "Informação adicional recebida"
}
```

### 6.5 Transição de Status

```
PATCH /api/v1/orders/{id}/status
Scope: orders:write
```

**Request:**

```json
{
  "status": "atribuido",
  "technician_id": "uuid-do-mecanico"
}
```

**Transições permitidas:**

```
aberto → atribuido (requer technician_id)
atribuido → em_andamento
em_andamento → concluido
concluido → fechado
qualquer → cancelado (com motivo obrigatório)
```

### 6.6 Cancelar Chamado

```
DELETE /api/v1/orders/{id}
Scope: orders:write
```

**Request:**

```json
{
  "reason": "Chamado duplicado"
}
```

---

## 7. Endpoints — Elevadores (Elevators)

### 7.1 Cadastrar Elevador

```
POST /api/v1/elevators
Scope: elevators:write
```

**Request:**

```json
{
  "serial_number": "ELV-2026-001",
  "manufacturer": "ThyssenKrupp",
  "model": "Synergy 200",
  "floor": 15,
  "condominium_id": "uuid-do-condominio",
  "last_revision_date": "2025-12-15",
  "notes": "Elevador social, andar térreo ao 15"
}
```

### 7.2 Listar Elevadores

```
GET /api/v1/elevators
Scope: elevators:read
```

**Parâmetros:** `condominium_id`, `manufacturer`, `per_page`, `cursor`, `sort`

### 7.3 Detalhe do Elevador

```
GET /api/v1/elevators/{id}
Scope: elevators:read
```

### 7.4 Chamados do Elevador

```
GET /api/v1/elevators/{id}/orders
Scope: elevators:read, orders:read
```

---

## 8. Endpoints — Condomínios (Condominiums)

### 8.1 Cadastrar Condomínio

```
POST /api/v1/condominiums
Scope: condominiums:write
```

**Request:**

```json
{
  "name": "Residencial Aurora",
  "cnpj": "12.345.678/0001-90",
  "address": "Rua das Flores, 123",
  "cep": "01001-000",
  "city": "São Paulo",
  "state": "SP",
  "phone": "1133334444",
  "email": "admin@residencialaurora.com.br",
  "sla_hours": 8
}
```

### 8.2 Listar Condomínios

```
GET /api/v1/condominiums
Scope: condominiums:read
```

### 8.3 Elevadores do Condomínio

```
GET /api/v1/condominiums/{id}/elevators
Scope: condominiums:read, elevators:read
```

---

## 9. Endpoints — Técnicos (Technicians)

### 9.1 Listar Técnicos

```
GET /api/v1/technicians
Scope: technicians:read
```

**Parâmetros:** `is_available`, `region`, `per_page`, `cursor`

### 9.2 Disponibilidade do Técnico

```
GET /api/v1/technicians/{id}
Scope: technicians:read
```

### 9.3 Chamados do Técnico

```
GET /api/v1/technicians/{id}/orders
Scope: technicians:read, orders:read
```

---

## 10. Endpoints — Webhooks

### 10.1 Registrar Webhook

```
POST /api/v1/webhooks
Scope: webhooks:manage
```

**Request:**

```json
{
  "url": "https://meu-sistema.com.br/webhook/elevadores",
  "events": ["order.created", "order.status_changed", "order.completed"],
  "secret": "minha_chave_secreta_para_hmac"
}
```

### 10.2 Listar Webhooks

```
GET /api/v1/webhooks
Scope: webhooks:manage
```

### 10.3 Remover Webhook

```
DELETE /api/v1/webhooks/{id}
Scope: webhooks:manage
```

---

## 11. Webhooks — Eventos de Saída

### 11.1 Payload Padrão

```json
{
  "event": "order.created",
  "timestamp": "2026-02-28T10:30:00-03:00",
  "data": {
    "id": "uuid-do-chamado",
    "priority": "P0",
    "status": "aberto",
    "type": "emergencia",
    "elevator_id": "uuid-elevador",
    "condominium_id": "uuid-condominio",
    "description": "Pessoa presa no elevador",
    "source": "api",
    "external_ref": "conv_123"
  },
  "webhook_id": "uuid-webhook",
  "delivery_id": "uuid-delivery"
}
```

### 11.2 Verificação de Assinatura

Toda delivery inclui o header `X-Signature-256` com HMAC-SHA256 do body usando o `secret` do webhook:

```
X-Signature-256: sha256=5d7e2b3a4f1c9e8d7b6a5c4d3e2f1a0b9c8d7e6f5a4b3c2d1e0f9a8b7c6d5e4
```

**Verificação (exemplo PHP):**

```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_SIGNATURE_256'];
$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit('Assinatura inválida');
}
```

### 11.3 Eventos Disponíveis

| Evento | Dados Inclusos | Quando |
|--------|---------------|--------|
| `order.created` | Chamado completo | Chamado criado |
| `order.status_changed` | id, old_status, new_status | Transição de status |
| `order.assigned` | id, technician_id, technician_name | Mecânico atribuído |
| `order.completed` | id, resolution, completed_at | Concluído pelo mecânico |
| `order.closed` | id, closed_at | Fechado definitivamente |
| `order.sla_warning` | id, sla_deadline, minutes_remaining | 80% do SLA consumido |
| `order.sla_violated` | id, sla_deadline, violated_at | SLA violado |
| `technician.availability_changed` | technician_id, is_available | Mudança de disponibilidade |

### 11.4 Política de Retry

| Tentativa | Delay | Ação |
|-----------|-------|------|
| 1ª | Imediata | Envio normal |
| 2ª | 1 segundo | Retry |
| 3ª | 30 segundos | Retry |
| 4ª | 5 minutos | Retry |
| 5ª | 30 minutos | Último retry |
| Falha | — | Webhook marcado como `failing`, alerta no painel |

---

## 12. Exemplos de Integração

### 12.1 Sistema de Atendimento abrindo chamado

```bash
curl -X POST https://api.seudominio.com.br/api/v1/orders \
  -H "Authorization: Bearer elev_pk_abc123..." \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: $(uuidgen)" \
  -d '{
    "priority": "P0",
    "type": "emergencia",
    "elevator_id": "550e8400-e29b-41d4-a716-446655440000",
    "description": "Pessoa presa no elevador do bloco A",
    "source": "whatsapp",
    "external_ref": "conv_whatsapp_789",
    "contact_name": "Maria Souza",
    "contact_phone": "11988887777"
  }'
```

### 12.2 ERP consultando chamados do dia

```bash
curl -X GET "https://api.seudominio.com.br/api/v1/orders?created_after=2026-02-28T00:00:00-03:00&status=fechado&per_page=50" \
  -H "Authorization: Bearer elev_pk_xyz789..."
```

### 12.3 Recebendo webhook de chamado concluído

```php
// webhook-receiver.php (no sistema de atendimento)

$payload = json_decode(file_get_contents('php://input'), true);

if ($payload['event'] === 'order.completed') {
    $orderId = $payload['data']['id'];
    $externalRef = $payload['data']['external_ref'];
    
    // Notificar condomínio pelo WhatsApp
    // que o chamado foi resolvido
    notifyCondominium($externalRef, "Seu chamado foi concluído!");
}

http_response_code(200);
```

---

## 13. SDKs e Ferramentas (Futuro)

| Item | Prioridade | Descrição |
|------|-----------|-----------|
| Documentação interativa (Swagger UI) | MVP | Testar endpoints direto no browser |
| SDK PHP | Fase 2 | Facilitar integração para clientes Laravel |
| SDK JavaScript | Fase 2 | Para integrações Node.js / frontend |
| Sandbox | Fase 2 | Ambiente de teste com dados fictícios |
| Postman Collection | MVP | Coleção pronta para testar API |
