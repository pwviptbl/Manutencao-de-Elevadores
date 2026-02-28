# Arquitetura da Plataforma

> Sistema de Gerenciamento de Manutenção de Elevadores (SaaS)  
> Versão 2.0 | Fevereiro 2026

---

## 1. Visão Geral

Plataforma SaaS multi-tenant 100% web para **gerenciamento de manutenção de elevadores**. Um único deploy serve todos os clientes (empresas de manutenção) com **isolamento completo de dados**.

O sistema é **exclusivamente de gerenciamento** — não inclui canais de atendimento (WhatsApp, VoIP, IA). A integração com sistemas de atendimento, ERPs ou qualquer outro software externo ocorre **exclusivamente via API REST pública**.

### 1.1 Posicionamento

```
┌────────────────────────────────────────────────────────────────┐
│                    ECOSSISTEMA COMPLETO                         │
│                                                                │
│  ┌──────────────────────┐    ┌──────────────────────────────┐  │
│  │  SISTEMA DE           │    │  SISTEMA DE GERENCIAMENTO    │  │
│  │  ATENDIMENTO          │    │  (Este sistema)              │  │
│  │  (Produto separado)   │    │                              │  │
│  │                       │    │  • Chamados / OS             │  │
│  │  • WhatsApp           │───▶│  • Cadastros                 │  │
│  │  • VoIP               │API │  • Despacho                  │  │
│  │  • IA Triagem         │    │  • PWA Mecânico              │  │
│  │  • Painel Atendente   │◀───│  • Relatórios                │  │
│  │                       │WH  │  • API Pública               │  │
│  └──────────────────────┘    └──────────────────────────────┘  │
│                                         ▲                      │
│                                         │ API                  │
│                                ┌────────┴────────┐             │
│                                │ Qualquer sistema │             │
│                                │ externo (ERP,    │             │
│                                │ app próprio, etc)│             │
│                                └─────────────────┘             │
└────────────────────────────────────────────────────────────────┘
```

### 1.2 Princípios Arquiteturais

| Princípio | Descrição |
|-----------|-----------|
| **API-first** | Toda funcionalidade é exposta via API REST documentada |
| **Independência** | Funciona 100% sem o sistema de atendimento |
| **Multi-tenant** | Um deploy, múltiplos clientes com isolamento total |
| **Eventos** | Webhooks notificam sistemas externos sobre mudanças |

---

## 2. Modelo de Entrega

| Critério | Decisão | Justificativa |
|----------|---------|---------------|
| Tipo de aplicação | 100% Web (SaaS) | Um deploy serve todos; sem instalação em cliente |
| Hospedagem | Servidor próprio (Coolify) | Controle total; custo diluído entre tenants |
| Mobile | PWA (Progressive Web App) | Mecânicos acessam via celular sem app store |
| Multi-tenancy | Schema compartilhado + RLS | Isolamento no banco; manutenção centralizada |
| Integração externa | API REST pública + Webhooks | Qualquer sistema pode integrar |

---

## 3. Fluxo Geral da Plataforma

### 3.1 Fluxo Manual (sem integração)

```
Gerente / Operador
    → Login no painel web
    → Cria chamado manualmente
    → Atribui mecânico
    → Mecânico recebe no PWA
    → Executa manutenção
    → Preenche checklist + fotos
    → Fecha OS com assinatura digital
    → Relatório gerado
```

### 3.2 Fluxo via API (com integração)

```
Sistema Externo (atendimento, ERP, app próprio)
    → POST /api/v1/orders (com API Key)
    → Chamado criado automaticamente
    → Regras de despacho aplicadas
    → Mecânico notificado via PWA
    → Webhook: order.assigned
    → Mecânico executa e fecha OS
    → Webhook: order.completed
    → Sistema externo recebe confirmação
```

### 3.3 Fluxo de Emergência via API

```
Sistema Externo
    → POST /api/v1/orders { "priority": "P0" }
    → Chamado criado com prioridade máxima
    → Alerta visual + sonoro no painel
    → Webhook: order.created (com flag emergency: true)
    → Mecânico de plantão notificado imediatamente
    → SLA de resposta ativado
```

---

## 4. Arquitetura em Camadas

```
┌─────────────────────────────────────────────────────────────┐
│                      FRONTEND                                │
│             Vue 3 + PrimeVue + TailwindCSS                   │
│             PWA (Vite Plugin) + Pinia                        │
├─────────────────────────────────────────────────────────────┤
│                WEBSOCKET (Tempo Real)                         │
│                Laravel Reverb + Echo                          │
├────────────────────────┬────────────────────────────────────┤
│    API INTERNA (SPA)   │       API PÚBLICA (v1)              │
│   Laravel Sanctum      │    API Keys / OAuth 2.0             │
│   Cookie-based Auth    │    Token-based Auth                  │
│   Rotas /api/*         │    Rotas /api/v1/*                  │
├────────────────────────┴────────────────────────────────────┤
│                    APLICAÇÃO                                  │
│              Laravel 11 (PHP 8.3+)                           │
│         Multi-Tenancy (stancl/tenancy + RLS)                 │
├─────────────────────────────────────────────────────────────┤
│                  FILAS / ASYNC                                │
│              Laravel Queue + Redis                            │
│     Jobs: importação, notificações, webhooks                 │
├─────────────────────────────────────────────────────────────┤
│               BANCO DE DADOS                                  │
│            PostgreSQL 16 + RLS                                │
│       Row-Level Security por tenant                           │
├─────────────────────────────────────────────────────────────┤
│              WEBHOOKS DE SAÍDA                                │
│     Notificação assíncrona de eventos para                   │
│     sistemas externos (atendimento, ERPs, etc)               │
└─────────────────────────────────────────────────────────────┘
```

---

## 5. Autenticação Dupla

O sistema possui **duas camadas de autenticação** distintas:

### 5.1 Autenticação de Usuários (Frontend)

| Aspecto | Detalhe |
|---------|---------|
| Mecanismo | Laravel Sanctum (SPA cookie-based) |
| Proteção | CSRF token obrigatório |
| Sessão | Cookie httpOnly, secure, SameSite |
| Roles | Admin, Gerente, Mecânico, Visualizador |

```
Frontend Vue.js ──[Cookie httpOnly + CSRF]──▶ Backend Laravel
```

### 5.2 Autenticação de Integrações (API Pública)

| Aspecto | Detalhe |
|---------|---------|
| Mecanismo (MVP) | API Keys por tenant |
| Mecanismo (futuro) | OAuth 2.0 Client Credentials |
| Identificação | Header `Authorization: Bearer {api_key}` |
| Escopo | Scopes granulares por key (ex: `orders:write`) |
| Rate limiting | Por API Key, configurável por plano |
| Vínculo | API Key → tenant_id (RLS continua funcionando) |

```
Sistema Externo ──[Bearer API Key]──▶ API Pública /api/v1/*
                                      │
                                      ├── Valida API Key
                                      ├── Identifica tenant_id
                                      ├── Verifica scopes
                                      ├── Aplica rate limit
                                      ├── Injeta tenant no RLS
                                      └── Processa requisição
```

---

## 6. Multi-Tenancy e Isolamento de Dados

Empresas de manutenção concorrentes compartilham a mesma infraestrutura. A segurança é construída em camadas, com o banco de dados como última linha de defesa.

| Mecanismo | Onde Atua | O que Protege |
|-----------|-----------|---------------|
| RLS PostgreSQL | Banco de dados | Isolamento físico de dados por tenant |
| Sanctum (cookie) + tenant_id | API interna (middleware) | Autenticação de usuários humanos |
| API Key + tenant_id | API pública (middleware) | Autenticação de integrações externas |
| stancl/tenancy | Laravel (aplicação) | Contexto de tenant injetado automaticamente |
| Rate limiting | Borda (Nginx/Cloudflare) | Prevenção de abuso e DDoS por tenant |
| Schema por tenant | Banco (opcional, tier Enterprise) | Isolamento total para clientes críticos |

---

## 7. Diagrama de Contexto (C4 — Nível 1)

```
                    ┌───────────────────┐
                    │  Sistema Externo   │
                    │  (Atendimento,    │
                    │   ERP, App, etc)  │
                    └────────┬──────────┘
                             │ API REST + Webhooks
                    ┌────────▼──────────┐
                    │    Plataforma     │
                    │   Gerenciamento   │◄──── Admin / Gerente (Painel Web)
                    │   Elevadores     │◄──── Mecânico (PWA)
                    └──┬──────────┬────┘
                       │          │
              ┌────────┘          └────────┐
              ▼                            ▼
     ┌──────────────┐            ┌──────────────┐
     │  PostgreSQL   │            │  Nuvem Fiscal│
     │  + RLS        │            │  (NFS-e)     │
     └──────────────┘            │  (Fase 2)    │
                                  └──────────────┘
```

---

## 8. Webhooks — Comunicação com Sistemas Externos

O sistema de gerenciamento **notifica** sistemas externos sobre eventos via webhooks HTTP.

### 8.1 Eventos Disponíveis

| Evento | Quando Dispara |
|--------|----------------|
| `order.created` | Novo chamado criado (manual ou via API) |
| `order.status_changed` | Mudança de status do chamado |
| `order.assigned` | Mecânico atribuído ao chamado |
| `order.completed` | Chamado concluído pelo mecânico |
| `order.closed` | Chamado fechado (finalizado) |
| `order.sla_warning` | Chamado prestes a violar SLA |
| `order.sla_violated` | SLA violado |
| `technician.availability_changed` | Mecânico mudou disponibilidade |

### 8.2 Garantias

| Garantia | Implementação |
|----------|---------------|
| Autenticidade | Assinatura HMAC-SHA256 em cada delivery |
| Entrega | Até 5 retentativas com backoff exponencial |
| Idempotência | `delivery_id` único por entrega |
| Auditoria | Todas as deliveries logadas com status |

---

## 9. API Pública — Visão Geral

A API REST pública é o **ponto de integração** com qualquer sistema externo.

### 9.1 Características

| Característica | Detalhe |
|---------------|---------|
| Formato | JSON (request e response) |
| Versionamento | Prefixo `/api/v1/` |
| Paginação | Cursor-based |
| Idempotência | Header `Idempotency-Key` em POSTs |
| Rate limiting | Headers `X-RateLimit-*` |
| Documentação | OpenAPI/Swagger auto-gerado |

### 9.2 Recursos Disponíveis

| Recurso | Operações | Endpoint Base |
|---------|-----------|---------------|
| Chamados/OS | CRUD + transição de status | `/api/v1/orders` |
| Elevadores | CRUD + histórico | `/api/v1/elevators` |
| Condomínios | CRUD + elevadores vinculados | `/api/v1/condominiums` |
| Técnicos | Listar + disponibilidade | `/api/v1/technicians` |
| Webhooks | Registrar, listar, remover | `/api/v1/webhooks` |

> Documentação completa em [doc/api-publica.md](api-publica.md)

---

## 10. Decisões Arquiteturais (ADRs)

| # | Decisão | Contexto | Consequência |
|---|---------|----------|--------------|
| ADR-001 | Multi-tenancy com RLS (não schema separado) | Múltiplos tenants no MVP; schema separado é caro de manter | Manutenção centralizada; migrar para schema por tenant caso Enterprise |
| ADR-002 | PWA em vez de app nativo | Mecânicos usam celulares variados; evitar custo de app stores | Acesso imediato via browser; limitações em push notifications offline |
| ADR-003 | Coolify como PaaS | Controle total da infra; custo baixo para múltiplos tenants | Responsabilidade operacional do time; sem SLA de terceiros |
| ADR-004 | Laravel Reverb para WebSocket | Nativo no Laravel 11; sem dependência externa | Simplifica deploy; funciona dentro do ecossistema Laravel |
| ADR-005 | Separação Atendimento × Gerenciamento | Clientes diferentes para cada sistema; independência comercial | Complexidade de integração; flexibilidade de mercado |
| ADR-006 | API Keys no MVP (não OAuth) | Único integrador no início; simplicidade | Migrar para OAuth 2.0 quando surgir demanda de múltiplos integradores |
| ADR-007 | Webhooks para notificação de eventos | Sistemas externos precisam saber status do chamado em tempo real | Complexidade de delivery assíncrona; retry mechanism necessário |
| ADR-008 | Versionamento de API com prefixo `/v1/` | Permitir evolução sem quebrar integradores | Manter v1 funcional por pelo menos 12 meses após lançar v2 |
| ADR-009 | Idempotency-Key em POSTs | Evitar chamados duplicados em falha de rede | Armazenar keys por 24h; custo de storage mínimo |
