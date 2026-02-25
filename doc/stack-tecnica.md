# Stack Técnica

> Plataforma SaaS — Callcenter de Manutenção de Elevadores  
> Versão 1.0 | Fevereiro 2026

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
| **Filas / Async** | Laravel Queue + Redis | Jobs de IA, importação, notificações |
| **WebSocket** | Laravel Reverb + Echo | Nativo no Laravel 11, sem dependência externa |
| **LLM (triagem)** | GPT-4o-mini / Claude Haiku | Baixo custo por token; latência adequada |
| **WhatsApp (MVP)** | Evolution API (self-hosted) | Sem custo por mensagem; QR Code por tenant |
| **WhatsApp (Produção)** | Meta Cloud API | SLA garantido quando tiver volume |
| **VOIP** | Asterisk self-hosted / Twilio | Asterisk reduz custo; Twilio para velocidade |
| **STT (voz → texto)** | Whisper API / self-hosted | Self-hosted em GPU spot reduz custo em escala |
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
| `laravel/sanctum` | Autenticação SPA via cookie/token |
| `laravel/reverb` | WebSocket server nativo |
| `laravel/horizon` | Dashboard de monitoramento de filas |
| `spatie/laravel-permission` | RBAC (roles e permissões) |
| `spatie/laravel-activitylog` | Audit log de atividades |
| `maatwebsite/excel` | Importação/exportação Excel/CSV |
| `enlightn/enlightn` | Análise de segurança e performance |
| `vimeo/psalm` | Análise estática de tipos PHP |
| `openai-php/laravel` | SDK oficial OpenAI para Laravel |

### Estrutura de Diretórios (sugerida)

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── ServiceOrderController.php
│   │   │   │   ├── ElevatorController.php
│   │   │   │   ├── CondominiumController.php
│   │   │   │   ├── TechnicianController.php
│   │   │   │   └── ImportController.php
│   │   │   └── Webhook/
│   │   │       ├── WhatsAppController.php
│   │   │       └── VoipController.php
│   │   └── Middleware/
│   │       ├── EnsureTenant.php
│   │       └── CheckRole.php
│   ├── Models/
│   │   ├── Tenant.php
│   │   ├── User.php
│   │   ├── ServiceOrder.php
│   │   ├── Elevator.php
│   │   ├── Condominium.php
│   │   └── Technician.php
│   ├── Services/
│   │   ├── AI/
│   │   │   ├── EmergencyFilter.php      # Camada 0 — Regex
│   │   │   ├── TriageService.php        # Camada 1 — LLM
│   │   │   └── EscalationService.php    # Camada 2 — Regras
│   │   ├── WhatsApp/
│   │   │   └── EvolutionApiService.php
│   │   ├── Voip/
│   │   │   └── AsteriskService.php
│   │   └── Invoice/
│   │       └── NuvemFiscalService.php
│   ├── Jobs/
│   │   ├── ProcessImport.php
│   │   ├── SendEmergencyAlert.php
│   │   └── ProcessAiTriage.php
│   └── Events/
│       ├── ServiceOrderCreated.php
│       ├── ServiceOrderUpdated.php
│       └── EmergencyDetected.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   ├── api.php
│   ├── channels.php
│   └── web.php
├── tests/
│   ├── Feature/
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
│   │   └── mechanic/          # PWA do mecânico
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
├── role (admin | atendente | mecanico | visualizador)
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
├── opened_at, closed_at
├── source (whatsapp | voz | painel | importacao)
└── RLS: tenant_id

technicians
├── id
├── tenant_id (FK)
├── user_id (FK)
├── name, crea, phone, region
├── is_available (boolean)
└── RLS: tenant_id
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
│  ┌─────────────┐  ┌───────────────────┐ │
│  │ Evolution   │  │  Asterisk (VOIP)  │ │
│  │    API      │  │  (Fase 2)         │ │
│  └─────────────┘  └───────────────────┘ │
└─────────────────────────────────────────┘
```

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
