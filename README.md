# Manutenção de Elevadores — Sistema de Gerenciamento (SaaS)

> Plataforma SaaS de Gerenciamento de Manutenção de Elevadores  
> Versão 2.0 | Fevereiro 2026

---

## Sobre o Projeto

Plataforma SaaS multi-tenant para **gerenciamento de manutenção de elevadores**. Centraliza abertura de chamados, despacho de mecânicos, controle de elevadores e acompanhamento em tempo real.

O sistema é **exclusivamente de gerenciamento** — não inclui canais de atendimento (WhatsApp, VoIP, IA). A integração com sistemas de atendimento ou qualquer outro software externo ocorre via **API REST pública documentada**.

### Dois Sistemas, Um Ecossistema

| Sistema | Descrição | Repositório |
|---------|-----------|-------------|
| **Gerenciamento** (este) | Chamados, mecânicos, elevadores, OS, API | Este repo |
| **Atendimento** (separado) | WhatsApp, VoIP, IA, triagem | Repo separado |

Os sistemas são **independentes**: cada empresa pode contratar um, outro, ou ambos.

---

## Stack Principal

| Camada | Tecnologia |
|--------|------------|
| Backend | Laravel 11 (PHP 8.3+) |
| Frontend | Vue 3 + PrimeVue + TailwindCSS |
| Banco | PostgreSQL 16 + RLS |
| WebSocket | Laravel Reverb + Echo |
| Filas | Laravel Queue + Redis |
| API Pública | REST JSON + API Keys |
| Docs API | Scramble (OpenAPI automático) |
| Hospedagem | Coolify (self-hosted) |
| CI/CD | GitHub Actions |

---

## Documentação

| Documento | Descrição |
|-----------|-----------|
| [doc/arquitetura.md](doc/arquitetura.md) | Arquitetura, fluxos, camadas, ADRs |
| [doc/plano-desenvolvimento.md](doc/plano-desenvolvimento.md) | Plano sequencial em 4 fases |
| [doc/stack-tecnica.md](doc/stack-tecnica.md) | Stack, packages, estrutura de diretórios |
| [doc/seguranca.md](doc/seguranca.md) | Segurança, DevSecOps, multi-tenancy, API, LGPD |
| [doc/modulos.md](doc/modulos.md) | Módulos MVP e Segunda Fase |
| [doc/modelo-negocio.md](doc/modelo-negocio.md) | Planos, receita, custos, onboarding |
| [doc/api-publica.md](doc/api-publica.md) | Documentação completa da API REST pública |

---

## Como Rodar (Primeira Vez)

```bash
# 1. Clonar o repositório
git clone https://github.com/pwviptbl/Manutencao-de-Elevadores.git
cd Manutencao-de-Elevadores

# 2. Setup completo (Docker, .env, migrations, seeders)
make setup

# 3. Acesse
# Aplicação:  http://localhost
# API:        http://localhost/api
# API Pública: http://localhost/api/v1
# Docs API:   http://localhost/docs/api
# Horizon:    http://localhost/horizon
# WebSocket:  ws://localhost/app
```

> **Pré-requisitos:** Docker + Docker Compose instalados.

---

## Estrutura do Repositório

```
/
├── backend/              # Laravel 11 (PHP 8.3+)
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   ├── Api/             # Rotas internas (Sanctum)
│   │   │   └── Public/V1/      # API Pública v1 (API Key)
│   │   ├── Middleware/          # EnsureTenant, AuthenticateApiKey, etc.
│   │   ├── Models/              # Tenant, User, ServiceOrder, ApiKey, Webhook...
│   │   ├── Services/            # ApiKey, Webhook, Order, Dispatch
│   │   ├── Jobs/                # Import, Webhooks, Notificações
│   │   └── Events/              # Eventos de chamados e mecânicos
│   ├── database/{migrations,seeders,factories}/
│   ├── routes/
│   │   ├── api.php              # Rotas internas (Sanctum)
│   │   └── api_v1.php           # API Pública v1 (API Key)
│   └── tests/{Feature,Unit}/
├── frontend/             # Vue 3 + Vite
│   └── src/
│       ├── components/{layout,orders,alerts,api-keys,webhooks,shared}/
│       ├── pages/{orders,condominiums,elevators,settings,mechanic}/
│       ├── stores/
│       └── composables/
├── docker/
│   ├── nginx/default.conf
│   └── postgres/init.sql
├── doc/                  # Documentação técnica
├── .github/workflows/ci.yml
├── docker-compose.yml
└── Makefile
```

---

## Comandos Úteis

| Comando | Descrição |
|---------|-----------|
| `make up` | Sobe o ambiente |
| `make down` | Para o ambiente |
| `make test` | Roda todos os testes |
| `make test-coverage` | Testes com cobertura (mín. 80%) |
| `make migrate` | Executa migrations |
| `make fresh` | Recria banco + seed |
| `make lint` | Verifica estilo de código |
| `make shell-backend` | Shell no container PHP |
| `make logs` | Logs de todos os serviços |
| `make help` | Lista todos os comandos |

---

## Fases de Desenvolvimento

| # | Fase | Duração | Status |
|---|------|---------|--------|
| 0 | Setup do Projeto e Infra | 1 semana | ✅ Concluído |
| 1 | Backend MVP (API interna + pública) | 5-6 semanas | 🔲 Pendente |
| 2 | Frontend MVP | 4-5 semanas | 🔲 Pendente |
| 3 | Piloto com 1 cliente | 2-3 semanas | 🔲 Pendente |
| 4 | Rollout geral + Módulos Fase 2 | 8-12 semanas | 🔲 Pendente |

---

## API Pública

A API REST pública permite integração com qualquer sistema externo (atendimento, ERP, app próprio).

```bash
# Exemplo: Criar chamado via API
curl -X POST https://api.seudominio.com.br/api/v1/orders \
  -H "Authorization: Bearer elev_pk_abc123..." \
  -H "Content-Type: application/json" \
  -d '{
    "priority": "P1",
    "type": "corretiva",
    "elevator_id": "uuid-do-elevador",
    "description": "Elevador com barulho ao fechar porta"
  }'
```

> Documentação completa em [doc/api-publica.md](doc/api-publica.md)

---

## Licença

Confidencial — uso interno.
