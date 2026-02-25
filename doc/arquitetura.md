# Arquitetura da Plataforma

> Plataforma SaaS — Callcenter de Manutenção de Elevadores  
> Versão 1.0 | Fevereiro 2026

---

## 1. Visão Geral

Plataforma SaaS multi-tenant 100% web. Um único deploy serve todos os clientes (~70 empresas de manutenção de elevadores) com **isolamento completo de dados**. A IA atua como camada de atendimento de entrada — não como substituta do atendente humano, mas como **acelerador de triagem** para chamados de rotina.

**Objetivo central:** reduzir o tempo de abertura de chamado de ~5 minutos para menos de 60 segundos.

---

## 2. Modelo de Entrega

| Critério | Decisão | Justificativa |
|---|---|---|
| Tipo de aplicação | 100% Web (SaaS) | Um deploy serve todos; sem instalação em cliente |
| Hospedagem | Servidor próprio (Coolify) | Controle total; custo diluído entre tenants |
| Mobile | PWA (Progressive Web App) | Mecânicos acessam via celular sem app store |
| Multi-tenancy | Schema compartilhado + RLS | Isolamento no banco; manutenção centralizada |
| Canal de entrada IA | WhatsApp + Voz | Canais já usados pelos condomínios hoje |

---

## 3. Fluxo Geral da Plataforma

```
Condomínio
    → WhatsApp ou Ligação
    → Filtro de Emergência (Camada 0)
    → IA de Triagem (Camada 1)
    → Chamado criado automaticamente
    → Despacho de mecânico
    → Acompanhamento em tempo real
    → Fechamento de OS
    → Emissão de NFS-e
```

O fluxo **híbrido (determinístico + generativo)** é o diferencial arquitetural. A IA **NÃO** é a primeira linha de defesa em situações de risco. Um filtro de expressões regulares atua antes do LLM para garantir que emergências nunca dependam de inferência probabilística.

---

## 4. Arquitetura em Camadas

```
┌─────────────────────────────────────────────────────┐
│                    FRONTEND                          │
│           Vue 3 + PrimeVue + TailwindCSS             │
│           PWA (Vite Plugin) + Pinia                  │
├─────────────────────────────────────────────────────┤
│              WEBSOCKET (Tempo Real)                  │
│              Laravel Reverb + Echo                   │
├─────────────────────────────────────────────────────┤
│                  API BACKEND                         │
│              Laravel 11 (PHP 8.3+)                   │
│         Sanctum Auth + Multi-Tenancy (RLS)           │
├─────────────────────────────────────────────────────┤
│                FILAS / ASYNC                         │
│            Laravel Queue + Redis                     │
│     Jobs: IA, importação, notificações               │
├─────────────────────────────────────────────────────┤
│              BANCO DE DADOS                          │
│           PostgreSQL 16 + RLS                        │
│       Row-Level Security por tenant                  │
├─────────────────────────────────────────────────────┤
│            INTEGRAÇÕES EXTERNAS                      │
│  WhatsApp (Evolution API / Meta Cloud API)           │
│  VOIP (Asterisk / Twilio)                            │
│  LLM (GPT-4o-mini / Claude Haiku)                   │
│  STT (Whisper API)                                   │
│  NFS-e (Nuvem Fiscal API)                            │
└─────────────────────────────────────────────────────┘
```

---

## 5. Fluxo Híbrido IA: Determinístico + Generativo

| Camada | Nome | Tecnologia | Ações |
|--------|------|------------|-------|
| **0** | Filtro de Emergência | Regex / Keyword Match (síncrono) | Bypass total, webhook, alerta visual/sonoro |
| **1** | Triagem Rotina | LLM (GPT-4o-mini / Claude Haiku) | Coleta estruturada, abre chamado normal |
| **2** | Escalonamento | Lógica determinística (regras) | Prioridade, fila, SLA por contrato |
| **3** | Atendente Humano | Painel web em tempo real | Casos complexos, validação, override |

### 5.1 Palavras-Chave de Emergência (Camada 0)

O filtro deve cobrir ao menos os seguintes termos (acentuados e sem acento):

- `"preso"` / `"travado"` / `"pessoa presa"`
- `"caiu"` / `"queda"` / `"despencou"`
- `"fumaça"` / `"fogo"` / `"incêndio"` / `"cheiro de queimado"`
- `"socorro"` / `"ajuda"` / `"emergência"` / `"urgente"`
- `"não abre"` (em contexto de pessoa dentro)

### 5.2 Ações Automáticas ao Detectar Emergência

1. Chamado criado imediatamente com prioridade **P0**
2. Webhook dispara notificação push/SMS para plantão de mecânico
3. Alerta visual e sonoro no painel do atendente (cor vermelha, toque)
4. Atendente humano assume em **menos de 5 segundos**
5. LLM **nunca** é consultado — decisão é 100% determinística

> **Argumento jurídico:** se houver acidente e o sistema tiver classificado incorretamente via LLM, o passivo é enorme. O filtro determinístico é a blindagem legal e operacional do produto.

---

## 6. Multi-Tenancy e Isolamento de Dados

70 empresas concorrentes compartilham a mesma infraestrutura. A segurança é construída em camadas, com o banco de dados como última linha de defesa.

| Mecanismo | Onde Atua | O que Protege |
|---|---|---|
| RLS PostgreSQL | Banco de dados | Isolamento físico de dados por tenant |
| JWT + tenant_id | API (middleware) | Autenticação e autorização por requisição |
| stancl/tenancy | Laravel (aplicação) | Contexto de tenant injetado automaticamente |
| Rate limiting | Borda (Nginx/Cloudflare) | Prevenção de abuso e DDoS por tenant |
| Schema por tenant | Banco (opcional, tier Enterprise) | Isolamento total para clientes críticos |

---

## 7. Diagrama de Contexto (C4 — Nível 1)

```
                    ┌──────────────┐
                    │  Condomínio  │
                    │  (Usuário)   │
                    └──────┬───────┘
                           │ WhatsApp / Ligação
                    ┌──────▼───────┐
                    │  Plataforma  │
                    │    SaaS      │◄──── Atendente (Painel Web)
                    │  Elevadores  │◄──── Mecânico (PWA)
                    └──┬───┬───┬──┘
                       │   │   │
            ┌──────────┘   │   └──────────┐
            ▼              ▼              ▼
    ┌──────────────┐ ┌──────────┐ ┌──────────────┐
    │  WhatsApp /  │ │   LLM    │ │  Nuvem Fiscal│
    │  Evolution   │ │  OpenAI  │ │   (NFS-e)    │
    │    API       │ │  Claude  │ │              │
    └──────────────┘ └──────────┘ └──────────────┘
```

---

## 8. Decisões Arquiteturais (ADRs)

| # | Decisão | Contexto | Consequência |
|---|---------|----------|--------------|
| ADR-001 | Multi-tenancy com RLS (não schema separado) | 70 tenants no MVP; schema separado é caro de manter | Manutenção centralizada; migrar para schema por tenant caso Enterprise |
| ADR-002 | Filtro Regex antes do LLM | Domínio de risco de vida; LLM pode alucinar | Zero dependência de IA em emergências |
| ADR-003 | PWA em vez de app nativo | Mecânicos usam celulares variados; evitar custo de app stores | Acesso imediato via browser; limitações em push notifications offline |
| ADR-004 | Evolution API no MVP | Custo zero por mensagem; validação rápida | Migrar para Meta Cloud API quando volume > 5.000 conversas/mês |
| ADR-005 | Coolify como PaaS | Controle total da infra; custo baixo para 70 tenants | Responsabilidade operacional do time; sem SLA de terceiros |
| ADR-006 | Laravel Reverb para WebSocket | Nativo no Laravel 11; sem dependência externa | Simplifica deploy; funciona dentro do ecossistema Laravel |
