# Stack Técnica

> Sistema de Gerenciamento de Manutenção de Elevadores (SaaS)  
> Versão 2.0 | Fevereiro 2026

---

## Visão Geral

| Camada | Tecnologia | Justificativa |
|--------|------------|---------------|
| **Backend** | Laravel 11 (PHP 8.3+) | Familiaridade, ecossistema maduro, Queues/Scheduler nativos |
| **Frontend** | Vue 3 + Composition API | Reatividade, familiaridade, PWA via Vite plugin |
| **UI / Estilo** | PrimeVue + TailwindCSS | Componentes prontos + utilidades CSS |
| **Estado Global** | Pinia | Substituto oficial do Vuex, mais simples |
| **Banco de Dados** | PostgreSQL 16 + RLS | Isolamento nativo, JSON, performance |
| **Multi-tenancy** | stancl/tenancy (Laravel) | Biblioteca madura, RLS e schema por tenant |
| **Filas / Async** | Laravel Queue + Redis | Jobs de importação, notificações, webhooks |
| **WebSocket** | Laravel Reverb + Echo | Nativo no Laravel 11, sem dependência externa |
| **API Pública** | REST JSON + API Keys | Integração com qualquer sistema externo |
| **Documentação API** | Scramble (Dedoc) | Geração automática de OpenAPI a partir do código |
| **NFS-e** | Nuvem Fiscal API | Bem documentada, suporte multi-município |
| **Hospedagem** | Coolify (self-hosted) | PaaS open-source; controle total; custo baixo |
| **CI/CD** | GitHub Actions | Pipeline SAST + testes + deploy automatizado |
| **SAST** | Enlightn + Psalm + Semgrep | Cobertura Laravel + PHP + OWASP desde commit 1 |
| **Validação Forms** | VeeValidate + Zod | Schema-first, integração nativa com Vue |

---

## Backend — Laravel 11

### Packages Essenciais

| Package | Uso |
|---------|-----|
| `stancl/tenancy` | Multi-tenancy com suporte a RLS e schema |
| `laravel/sanctum` | Autenticação SPA via cookie (usuários humanos) |
| `laravel/reverb` | WebSocket server nativo |
| `laravel/horizon` | Dashboard de monitoramento de filas |
| `spatie/laravel-permission` | RBAC (roles e permissões) |
| `spatie/laravel-activitylog` | Audit log de atividades |
| `maatwebsite/excel` | Importação/exportação Excel/CSV |
| `dedoc/scramble` | Geração automática de documentação OpenAPI/Swagger |
| `enlightn/enlightn` | Análise de segurança e performance |
| `vimeo/psalm` | Análise estática de tipos PHP |

> **Removido da v1.0:** `openai-php/laravel` (IA/LLM vai para o sistema de atendimento).

### Estrutura de Diretórios (sugerida)

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                        # Rotas internas (Sanctum)
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── ServiceOrderController.php
│   │   │   │   ├── ElevatorController.php
│   │   │   │   ├── CondominiumController.php
│   │   │   │   ├── TechnicianController.php
│   │   │   │   ├── ImportController.php
│   │   │   │   └── ApiKeyController.php
│   │   │   └── Public/                     # API Pública v1 (API Key)
│   │   │       ├── V1/
│   │   │       │   ├── OrderController.php
│   │   │       │   ├── ElevatorController.php
│   │   │       │   ├── CondominiumController.php
│   │   │       │   ├── TechnicianController.php
│   │   │       │   └── WebhookController.php
│   │   │       └── AuthMeController.php
│   │   └── Middleware/
│   │       ├── EnsureTenant.php            # Injeta tenant para usuários Sanctum
│   │       ├── AuthenticateApiKey.php      # Valida API Key + injeta tenant
│   │       ├── CheckApiScope.php           # Verifica scopes da API Key
│   │       ├── ApiRateLimiter.php          # Rate limit por API Key
│   │       ├── IdempotencyCheck.php        # Verifica Idempotency-Key
│   │       └── CheckRole.php              # Verifica role RBAC
│   ├── Models/
│   │   ├── Tenant.php
│   │   ├── User.php
│   │   ├── ServiceOrder.php
│   │   ├── Elevator.php
│   │   ├── Condominium.php
│   │   ├── Technician.php
│   │   ├── ApiKey.php                     # Modelo de API Keys
│   │   ├── Webhook.php                    # Modelo de Webhooks registrados
│   │   └── WebhookDelivery.php            # Log de entregas de webhook
│   ├── Services/
│   │   ├── ApiKey/
│   │   │   ├── ApiKeyService.php          # Gerar, revogar, validar keys
│   │   │   └── ApiKeyScopeService.php     # Verificar permissões de scope
│   │   ├── Webhook/
│   │   │   ├── WebhookDispatcher.php      # Disparar webhooks
│   │   │   ├── WebhookSigner.php          # Assinatura HMAC-SHA256
│   │   │   └── WebhookRetryService.php    # Lógica de retry
│   │   ├── Order/
│   │   │   ├── OrderService.php           # Lógica de negócio de chamados
│   │   │   └── OrderStateMachine.php      # Transições de status
│   │   ├── Dispatch/
│   │   │   └── DispatchService.php        # Lógica de despacho de mecânicos
│   │   └── Invoice/
│   │       └── NuvemFiscalService.php     # Emissão de NFS-e (Fase 2)
│   ├── Jobs/
│   │   ├── ProcessImport.php              # Importação assíncrona CSV/Excel
│   │   ├── DispatchWebhook.php            # Envio de webhook (assíncrono)
│   │   ├── RetryWebhook.php              # Retry de webhook falhado
│   │   └── SendNotification.php           # Notificações push/email
│   └── Events/
│       ├── ServiceOrderCreated.php
│       ├── ServiceOrderUpdated.php
│       ├── ServiceOrderStatusChanged.php
│       ├── TechnicianAssigned.php
│       └── SlaViolated.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   ├── api.php                            # Rotas internas (Sanctum)
│   ├── api_v1.php                         # Rotas da API pública v1
│   ├── channels.php                       # Canais WebSocket
│   └── web.php
├── tests/
│   ├── Feature/
│   │   ├── Api/                           # Testes de rotas internas
│   │   └── PublicApi/                     # Testes de API pública
│   └── Unit/
└── composer.json
```

---

## Frontend — Vue 3

### Packages Essenciais

| Package | Uso |
|---------|-----|
| `vue@3` | Framework reativo |
| `vue-router@4` | Roteamento SPA |
| `pinia` | Estado global |
| `primevue` | Biblioteca de componentes UI |
| `tailwindcss` | Utilidades CSS |
| `laravel-echo` | Cliente WebSocket |
| `vee-validate` | Validação de formulários |
| `zod` | Schema de validação |
| `axios` | HTTP client |
| `vite-plugin-pwa` | Service worker e manifest PWA |

### Estrutura de Diretórios (sugerida)

```
frontend/
├── src/
│   ├── assets/
│   ├── components/
│   │   ├── layout/
│   │   │   ├── AppSidebar.vue
│   │   │   ├── AppHeader.vue
│   │   │   └── AppLayout.vue
│   │   ├── orders/
│   │   │   ├── OrderList.vue
│   │   │   ├── OrderDetail.vue
│   │   │   ├── OrderForm.vue
│   │   │   └── OrderTimeline.vue
│   │   ├── alerts/
│   │   │   └── EmergencyAlert.vue
│   │   ├── api-keys/
│   │   │   ├── ApiKeyList.vue
│   │   │   ├── ApiKeyCreate.vue
│   │   │   └── ApiKeyRevoke.vue
│   │   ├── webhooks/
│   │   │   ├── WebhookList.vue
│   │   │   ├── WebhookCreate.vue
│   │   │   └── WebhookDeliveryLog.vue
│   │   └── shared/
│   │       ├── DataTable.vue
│   │       ├── FileUpload.vue
│   │       └── ImportProgress.vue
│   ├── composables/
│   │   ├── useAuth.ts
│   │   ├── useWebSocket.ts
│   │   └── useImport.ts
│   ├── pages/
│   │   ├── Dashboard.vue
│   │   ├── Login.vue
│   │   ├── orders/
│   │   ├── condominiums/
│   │   ├── elevators/
│   │   ├── technicians/
│   │   ├── settings/
│   │   │   ├── ApiKeys.vue              # Gerenciamento de API Keys
│   │   │   ├── Webhooks.vue             # Gerenciamento de Webhooks
│   │   │   └── TenantSettings.vue       # Configurações do tenant
│   │   └── mechanic/                    # PWA do mecânico
│   │       ├── MechanicHome.vue
│   │       ├── MechanicChecklist.vue
│   │       └── MechanicSignature.vue
│   ├── stores/
│   │   ├── auth.ts
│   │   ├── orders.ts
│   │   └── notifications.ts
│   ├── router/
│   │   └── index.ts
│   ├── services/
│   │   └── api.ts
│   ├── App.vue
│   └── main.ts
├── public/
│   └── manifest.json
├── index.html
├── vite.config.ts
├── tailwind.config.js
└── package.json
```

---

## Banco de Dados — PostgreSQL 16

### Modelo de Dados Simplificado

```
tenants
├── id (UUID)
├── name
├── slug
├── plan (starter | pro | business | enterprise)
├── settings (JSONB)
└── created_at

users
├── id
├── tenant_id (FK → tenants)
├── name, email, password
├── role (admin | gerente | mecanico | visualizador)
└── RLS: tenant_id = current_setting('app.tenant_id')

condominiums
├── id
├── tenant_id (FK)
├── name, cnpj, address, cep, city, state
├── phone, email, sla_hours
└── RLS: tenant_id

elevators
├── id
├── tenant_id (FK)
├── condominium_id (FK)
├── serial_number, manufacturer, model, floor
├── last_revision_date, photos (JSONB)
└── RLS: tenant_id

service_orders
├── id
├── tenant_id (FK)
├── elevator_id (FK)
├── technician_id (FK, nullable)
├── priority (P0 | P1 | P2 | P3)
├── status (aberto | atribuido | em_andamento | concluido | fechado)
├── type (corretiva | preventiva | emergencia)
├── description, resolution
├── source (painel | api | importacao)
├── external_ref (nullable — referência no sistema externo)
├── contact_name, contact_phone (nullable)
├── opened_at, closed_at
└── RLS: tenant_id

technicians
├── id
├── tenant_id (FK)
├── user_id (FK)
├── name, crea, phone, region
├── is_available (boolean)
└── RLS: tenant_id

api_keys
├── id (UUID)
├── tenant_id (FK)
├── name (ex: "Produção - Atendimento")
├── key_hash (SHA-256)
├── key_prefix (primeiros 8 chars)
├── scopes (JSONB)
├── rate_limit (int — req/min)
├── expires_at (nullable)
├── last_used_at
├── is_active (boolean)
├── created_by (FK → users)
├── created_at, revoked_at
└── RLS: tenant_id

webhooks
├── id (UUID)
├── tenant_id (FK)
├── api_key_id (FK — quem registrou)
├── url (HTTPS obrigatório)
├── events (JSONB — lista de eventos assinados)
├── secret (para HMAC)
├── is_active (boolean)
├── failure_count (int)
├── created_at
└── RLS: tenant_id

webhook_deliveries
├── id (UUID)
├── webhook_id (FK)
├── event
├── payload (JSONB)
├── status_code (int, nullable)
├── response_body (text, nullable — primeiros 500 chars)
├── attempts (int)
├── delivered_at (nullable)
├── next_retry_at (nullable)
├── created_at
└── (sem RLS — acessado internamente pelo sistema de retry)

idempotency_keys
├── key (string, PK)
├── tenant_id
├── endpoint
├── response_status (int)
├── response_body (JSONB)
├── created_at
└── expires_at (24h após criação)
```

### Exemplo de Política RLS

```sql
ALTER TABLE service_orders ENABLE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON service_orders
    USING (tenant_id = current_setting('app.tenant_id')::uuid);
```

---

## Infraestrutura — Coolify

```
┌─────────────────────────────────────────┐
│              COOLIFY (PaaS)             │
│                                         │
│  ┌─────────┐  ┌──────────┐  ┌────────┐ │
│  │ Laravel  │  │  Vue 3   │  │ Redis  │ │
│  │ (API)    │  │ (Nginx)  │  │        │ │
│  └─────────┘  └──────────┘  └────────┘ │
│  ┌─────────────┐  ┌───────────────────┐ │
│  │ PostgreSQL  │  │  Laravel Reverb   │ │
│  │    16       │  │  (WebSocket)      │ │
│  └─────────────┘  └───────────────────┘ │
└─────────────────────────────────────────┘
```

> **Removido da v1.0:** Evolution API (WhatsApp) e Asterisk (VOIP) — pertencem ao sistema de atendimento.

---

## CI/CD — GitHub Actions

```yaml
# .github/workflows/ci.yml (resumo)
on: [push, pull_request]

jobs:
  backend:
    - composer install
    - php artisan test
    - vendor/bin/psalm
    - vendor/bin/enlightn
    - composer audit

  frontend:
    - npm ci
    - npm run lint
    - npm run type-check
    - npm run test
    - npm audit

  sast:
    - semgrep scan

  deploy:
    - if: branch == main
    - deploy via Coolify webhook
```

---

## Rotas de API

### Rotas Internas (Sanctum — `/api/`)

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'ensure.tenant'])->group(function () {
    // Autenticação
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // CRUD interno (usado pelo frontend)
    Route::apiResource('service-orders', ServiceOrderController::class);
    Route::apiResource('elevators', ElevatorController::class);
    Route::apiResource('condominiums', CondominiumController::class);
    Route::apiResource('technicians', TechnicianController::class);
    Route::apiResource('imports', ImportController::class);
    
    // Gerenciamento de API Keys (admin only)
    Route::apiResource('api-keys', ApiKeyController::class)
        ->middleware('role:admin');
});
```

### Rotas Públicas (API Key — `/api/v1/`)

```php
// routes/api_v1.php
Route::prefix('v1')->middleware(['api.key', 'api.rate-limit'])->group(function () {
    // Verificação de auth
    Route::get('/auth/me', [AuthMeController::class, 'show']);
    
    // Chamados
    Route::apiResource('orders', V1\OrderController::class)
        ->middleware('api.scope:orders');
    Route::patch('orders/{id}/status', [V1\OrderController::class, 'updateStatus'])
        ->middleware('api.scope:orders:write');
    
    // Elevadores
    Route::apiResource('elevators', V1\ElevatorController::class)
        ->middleware('api.scope:elevators');
    Route::get('elevators/{id}/orders', [V1\ElevatorController::class, 'orders'])
        ->middleware('api.scope:elevators:read,orders:read');
    
    // Condomínios
    Route::apiResource('condominiums', V1\CondominiumController::class)
        ->middleware('api.scope:condominiums');
    Route::get('condominiums/{id}/elevators', [V1\CondominiumController::class, 'elevators'])
        ->middleware('api.scope:condominiums:read,elevators:read');
    
    // Técnicos
    Route::get('technicians', [V1\TechnicianController::class, 'index'])
        ->middleware('api.scope:technicians:read');
    Route::get('technicians/{id}', [V1\TechnicianController::class, 'show'])
        ->middleware('api.scope:technicians:read');
    
    // Webhooks
    Route::apiResource('webhooks', V1\WebhookController::class)
        ->middleware('api.scope:webhooks:manage');
});
```
