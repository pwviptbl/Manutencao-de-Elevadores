# Manutenção de Elevadores — Plataforma SaaS

> Callcenter de Manutenção de Elevadores  
> Documento Técnico de Pré-Engenharia — v1.0 | Fevereiro 2026

---

## Sobre o Projeto

Plataforma SaaS multi-tenant para **callcenter B2B** que atende ~70 empresas de manutenção de elevadores. Centraliza abertura de chamados, despacho de mecânicos, triagem por IA e acompanhamento em tempo real — substituindo múltiplos sistemas manuais por uma interface unificada.

**Objetivo:** reduzir o tempo de abertura de chamado de ~5 minutos para **menos de 60 segundos**.

---

## Stack Principal

| Camada | Tecnologia |
|--------|------------|
| Backend | Laravel 11 (PHP 8.3+) |
| Frontend | Vue 3 + PrimeVue + TailwindCSS |
| Banco | PostgreSQL 16 + RLS |
| WebSocket | Laravel Reverb + Echo |
| Filas | Laravel Queue + Redis |
| IA | GPT-4o-mini / Claude Haiku |
| WhatsApp | Evolution API → Meta Cloud API |
| Hospedagem | Coolify (self-hosted) |
| CI/CD | GitHub Actions |

---

## Documentação

| Documento | Descrição |
|-----------|-----------|
| [doc/arquitetura.md](doc/arquitetura.md) | Visão geral da arquitetura, fluxos, camadas, ADRs |
| [doc/plano-desenvolvimento.md](doc/plano-desenvolvimento.md) | Plano sequencial de desenvolvimento em 5 fases |
| [doc/stack-tecnica.md](doc/stack-tecnica.md) | Stack detalhada, packages, estrutura de diretórios |
| [doc/seguranca.md](doc/seguranca.md) | Segurança, DevSecOps, multi-tenancy, LGPD |
| [doc/modulos.md](doc/modulos.md) | Módulos MVP e Segunda Fase com detalhamento |
| [doc/modelo-negocio.md](doc/modelo-negocio.md) | Planos, projeção de receita, custos, onboarding |

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
│   │   ├── Http/Controllers/{Api,Webhook}/
│   │   ├── Models/
│   │   ├── Services/{AI,WhatsApp,Voip,Invoice}/
│   │   ├── Jobs/
│   │   └── Events/
│   ├── database/{migrations,seeders,factories}/
│   ├── routes/
│   └── tests/{Feature,Unit}/
├── frontend/             # Vue 3 + Vite
│   └── src/
│       ├── components/{layout,orders,alerts,shared}/
│       ├── pages/{orders,condominiums,elevators,mechanic}/
│       ├── stores/
│       └── composables/
├── docker/
│   ├── nginx/default.conf
│   └── postgres/init.sql
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
| 1 | Backend MVP | 4-5 semanas | � Em andamento |
| 2 | Frontend MVP | 4-5 semanas | 🔲 Pendente |
| 3 | Integração IA | 3-4 semanas | 🔲 Pendente |
| 4 | Piloto com 1 cliente | A definir | 🔲 Pendente |
| 5 | Rollout geral (70 clientes) | A definir | 🔲 Pendente |

---

## Licença

Confidencial — uso interno.
