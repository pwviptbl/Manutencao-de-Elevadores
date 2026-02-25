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

## Próximos Passos

| # | Etapa | Status |
|---|-------|--------|
| 1 | Reunião de Alinhamento | 🔲 Pendente |
| 2 | Documentação de Requisitos | 🔲 Pendente |
| 3 | Modelagem do Banco | 🔲 Pendente |
| 4 | Setup do Projeto | 🔲 Pendente |
| 5 | MVP Backend | 🔲 Pendente |
| 6 | MVP Frontend | 🔲 Pendente |
| 7 | Integração IA | 🔲 Pendente |
| 8 | Piloto com 1 cliente | 🔲 Pendente |
| 9 | Rollout geral (70 clientes) | 🔲 Pendente |

---

## Licença

Confidencial — uso interno.
