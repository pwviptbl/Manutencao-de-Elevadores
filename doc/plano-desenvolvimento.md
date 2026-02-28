# Plano de Desenvolvimento Sequencial

> Sistema de Gerenciamento de Manutenção de Elevadores (SaaS)  
> Versão 2.0 | Fevereiro 2026

---

## Visão Geral das Fases

```
FASE 0 ──► FASE 1 ──► FASE 2 ──► FASE 3 ──► FASE 4
Setup      Backend    Frontend    Piloto     Rollout
Projeto    MVP        MVP         1 cliente  Expansão
```

> **Nota v2.0:** A fase de "Integração IA" foi removida. A IA pertence ao sistema de atendimento (produto separado). Este plano foca exclusivamente no sistema de gerenciamento com API pública.

---

## FASE 0 — Setup do Projeto e Infraestrutura

**Duração estimada:** 1 semana  
**Pré-requisito:** Nenhum  
**Entregável:** Ambiente de desenvolvimento funcional com CI/CD

### Tarefas

| # | Tarefa | Detalhes | Prioridade |
|---|--------|----------|------------|
| 0.1 | Criar repositório GitHub | Monorepo com `/backend` e `/frontend` | P0 |
| 0.2 | Setup Laravel 11 | PHP 8.3+, configuração inicial, `.env.example` | P0 |
| 0.3 | Setup Vue 3 + Vite | Composition API, PrimeVue, TailwindCSS, Pinia | P0 |
| 0.4 | Setup PostgreSQL 16 | Docker local, criação de schemas de dev/test | P0 |
| 0.5 | Configurar Coolify | Servidor de staging com deploy automático | P0 |
| 0.6 | Pipeline CI/CD | GitHub Actions: lint, SAST (Enlightn + Psalm + Semgrep), testes | P0 |
| 0.7 | Configurar Redis | Para filas (Laravel Queue) e cache | P1 |
| 0.8 | Docker Compose local | Ambiente completo de desenvolvimento | P0 |
| 0.9 | Documentação de setup | README com instruções para onboarding de devs | P1 |

### Critérios de Aceite

- [ ] `docker compose up` sobe ambiente completo
- [ ] Pipeline CI roda em < 5 minutos
- [ ] Deploy automático no staging via push na `main`
- [ ] SAST configurado e bloqueando PRs com vulnerabilidades

---

## FASE 1 — Backend MVP

**Duração estimada:** 5-6 semanas  
**Pré-requisito:** FASE 0 completa  
**Entregável:** API REST funcional (interna + pública) com multi-tenancy e chamados

### Sprint 1.1 — Autenticação e Multi-Tenancy (Semana 1-2)

| # | Tarefa | Detalhes |
|---|--------|----------|
| 1.1.1 | Instalar e configurar stancl/tenancy | Package Laravel para multi-tenancy |
| 1.1.2 | Modelar tabela `tenants` | ID, nome, slug, plano, configurações |
| 1.1.3 | Implementar RLS no PostgreSQL | Políticas por tenant_id em todas as tabelas |
| 1.1.4 | Autenticação com Laravel Sanctum | Login, logout, revogação/expiração de sessão |
| 1.1.5 | Sistema de Roles (RBAC) | Roles: `admin`, `gerente`, `mecanico`, `visualizador` |
| 1.1.6 | Middleware de tenant (Sanctum) | Validar tenant_id e injetar no RLS via `EnsureTenant` |
| 1.1.7 | Testes de isolamento | Garantir que Tenant A não acessa dados do Tenant B |
| 1.1.8 | Seeder de dados de teste | Tenants, usuários, roles para desenvolvimento |

### Sprint 1.2 — Gestão de Chamados / OS (Semana 2-3)

| # | Tarefa | Detalhes |
|---|--------|----------|
| 1.2.1 | Model `ServiceOrder` | Status, prioridade (P0-P3), tipo, descrição, source, external_ref |
| 1.2.2 | Model `Elevator` | Nº série, fabricante, modelo, andar, condomínio (ref) |
| 1.2.3 | Model `Condominium` | CNPJ, endereço, contatos, SLA contratado |
| 1.2.4 | Model `Technician` | Nome, CREA, telefone, região, disponibilidade |
| 1.2.5 | CRUD completo de Chamados | API REST com validação, filtros, paginação |
| 1.2.6 | CRUD de Elevadores | Vinculados a condomínios |
| 1.2.7 | CRUD de Condomínios | Com validação de CNPJ |
| 1.2.8 | CRUD de Mecânicos/Técnicos | Com região de atendimento |
| 1.2.9 | Máquina de estados do chamado | `aberto → atribuido → em_andamento → concluido → fechado` |
| 1.2.10 | Histórico de mudanças (audit log) | Registrar quem alterou o quê e quando |

### Sprint 1.3 — Despacho e Importação (Semana 3-4)

| # | Tarefa | Detalhes |
|---|--------|----------|
| 1.3.1 | Lógica de despacho de mecânicos | Atribuição por disponibilidade e região |
| 1.3.2 | Fila de chamados por prioridade | P0 > P1 > P2 > P3, respeitando SLA |
| 1.3.3 | Módulo de importação CSV/Excel | Upload + validação síncrona + job assíncrono |
| 1.3.4 | Templates de importação | Condomínios, elevadores, histórico OS, mecânicos |
| 1.3.5 | Processamento em lotes | 500 registros por batch via Laravel Queue |
| 1.3.6 | Relatório de erros pós-importação | Linha a linha: registro rejeitado + motivo |
| 1.3.7 | Progresso via WebSocket | Exibir % de conclusão em tempo real |

### Sprint 1.4 — WebSocket e Notificações (Semana 4-5)

| # | Tarefa | Detalhes |
|---|--------|----------|
| 1.4.1 | Configurar Laravel Reverb | WebSocket server nativo do Laravel 11 |
| 1.4.2 | Eventos de chamado em tempo real | Novo chamado, mudança de status, atribuição |
| 1.4.3 | Sistema de notificações | Push (service worker), e-mail |
| 1.4.4 | Alerta de emergência P0 | Visual + sonoro no painel |
| 1.4.5 | Testes de carga WebSocket | Simular múltiplos tenants com conexões simultâneas |

### Sprint 1.5 — API Pública + Webhooks (Semana 5-6)

| # | Tarefa | Detalhes |
|---|--------|----------|
| 1.5.1 | Model `ApiKey` | Hash SHA-256, scopes, rate limit, tenant_id |
| 1.5.2 | Middleware `AuthenticateApiKey` | Validar key, injetar tenant no RLS |
| 1.5.3 | Middleware `CheckApiScope` | Verificar permissões por scope |
| 1.5.4 | Middleware `ApiRateLimiter` | Rate limit por key conforme plano |
| 1.5.5 | Middleware `IdempotencyCheck` | Verificar e armazenar Idempotency-Key |
| 1.5.6 | Controllers API v1 | Endpoints para orders, elevators, condominiums, technicians |
| 1.5.7 | Formato de resposta padrão | JSON envelope: `data`, `meta`, `error` |
| 1.5.8 | Model `Webhook` + `WebhookDelivery` | Registro de webhooks e log de entregas |
| 1.5.9 | Job `DispatchWebhook` | Envio assíncrono com assinatura HMAC |
| 1.5.10 | Retry de webhooks | Backoff exponencial, até 5 tentativas |
| 1.5.11 | Documentação OpenAPI | Scramble (Dedoc) gerando docs automáticos |
| 1.5.12 | CRUD de API Keys (painel admin) | Gerar, listar, revogar keys |
| 1.5.13 | CRUD de Webhooks (via API) | Registrar, listar, remover webhooks |
| 1.5.14 | Testes de integração API | Testar auth, scopes, rate limit, idempotência |
| 1.5.15 | Auditoria de requisições API | Log de toda requisição com key_id, IP, endpoint |

### Critérios de Aceite — FASE 1

- [ ] API REST interna completa com documentação
- [ ] API REST pública v1 com documentação OpenAPI/Swagger
- [ ] Multi-tenancy com RLS testado e validado
- [ ] RBAC funcional com 4 roles
- [ ] API Keys com scopes e rate limiting funcionando
- [ ] Webhooks disparando com assinatura HMAC e retry
- [ ] Importação CSV processando 10.000 registros sem erro
- [ ] WebSocket entregando eventos em < 500ms
- [ ] Cobertura de testes > 80%

---

## FASE 2 — Frontend MVP

**Duração estimada:** 4-5 semanas  
**Pré-requisito:** FASE 1 (Sprint 1.1 e 1.2 mínimo)  
**Entregável:** Painel web funcional + PWA do mecânico

### Sprint 2.1 — Layout Base e Autenticação (Semana 1)

| # | Tarefa | Detalhes |
|---|--------|----------|
| 2.1.1 | Layout principal | Sidebar, header, breadcrumbs, tema por tenant |
| 2.1.2 | Tela de Login | Autenticação via Sanctum |
| 2.1.3 | Roteamento por Role | Redirect baseado no papel do usuário |
| 2.1.4 | Guarda de rotas | Middleware de autenticação no Vue Router |
| 2.1.5 | Store de autenticação (Pinia) | Estado do usuário, tenant, permissões |

### Sprint 2.2 — Painel de Chamados (Semana 2-3)

| # | Tarefa | Detalhes |
|---|--------|----------|
| 2.2.1 | Dashboard principal | KPIs: chamados abertos, P0 ativos, SLA, fila, via API |
| 2.2.2 | Lista de chamados | Tabela com filtros, busca, paginação, status colorido |
| 2.2.3 | Detalhe do chamado | Timeline, histórico, atribuição, fotos, origem (API/painel) |
| 2.2.4 | Criação manual de chamado | Formulário com validação (VeeValidate + Zod) |
| 2.2.5 | Alertas em tempo real | WebSocket: toast para novos chamados, badge na sidebar |
| 2.2.6 | Alerta P0 (emergência) | Tela inteira vermelha + som de alerta |

### Sprint 2.3 — Cadastros e Importação (Semana 3-4)

| # | Tarefa | Detalhes |
|---|--------|----------|
| 2.3.1 | CRUD de Condomínios (tela) | Lista, criação, edição, busca por CNPJ |
| 2.3.2 | CRUD de Elevadores (tela) | Vinculado a condomínio, fotos, histórico |
| 2.3.3 | CRUD de Mecânicos (tela) | Lista, disponibilidade, região |
| 2.3.4 | Tela de importação | Upload drag-and-drop, preview, progress bar |
| 2.3.5 | Download de templates | CSV/Excel pré-formatados |
| 2.3.6 | Relatório de erros de importação | Tabela com status por linha |

### Sprint 2.4 — PWA do Mecânico (Semana 4)

| # | Tarefa | Detalhes |
|---|--------|----------|
| 2.4.1 | Tela de chamados atribuídos | Lista mobile-friendly |
| 2.4.2 | Aceitar/recusar chamado | Ação rápida com confirmação |
| 2.4.3 | Checklist de manutenção | Itens dinâmicos por tipo de manutenção |
| 2.4.4 | Upload de fotos | Câmera do celular, compressão automática |
| 2.4.5 | Assinatura digital | Canvas para assinatura do responsável no celular |
| 2.4.6 | Fechamento de OS | Resumo + assinatura + envio |
| 2.4.7 | Configuração PWA | Manifest, service worker, ícone, splash screen |
| 2.4.8 | Modo offline básico | Cache de chamados atribuídos; sync ao reconectar |

### Sprint 2.5 — Configurações e API Keys (Semana 5)

| # | Tarefa | Detalhes |
|---|--------|----------|
| 2.5.1 | Tela de API Keys | Lista, criar, revogar keys (somente admin) |
| 2.5.2 | Tela de Webhooks | Lista, criar, remover, ver log de deliveries |
| 2.5.3 | Configurações do Tenant | Logo, cores, SLA default, plano |
| 2.5.4 | Gerenciamento de Usuários | CRUD de usuários e roles (somente admin) |
| 2.5.5 | Documentação inline da API | Link para Swagger UI dentro do painel |

### Critérios de Aceite — FASE 2

- [ ] Painel responsivo funcionando em desktop e tablet
- [ ] PWA instalável no celular do mecânico
- [ ] Alertas P0 com som e alerta visual em < 2 segundos
- [ ] Importação com feedback visual em tempo real
- [ ] Validação de formulários client-side + server-side
- [ ] Tela de API Keys e Webhooks funcional para admins
- [ ] Documentação Swagger acessível pelo painel

---

## FASE 3 — Piloto com 1 Cliente

**Duração estimada:** 2-3 semanas  
**Pré-requisito:** FASE 1 e FASE 2 completas  
**Entregável:** Validação em produção controlada

### Tarefas

| # | Tarefa | Detalhes |
|---|--------|----------|
| 3.1 | Selecionar cliente piloto | Idealmente empresa média, receptiva, ~50 elevadores |
| 3.2 | Migração assistida de dados | Importação de condomínios, elevadores, histórico |
| 3.3 | Treinamento de gerentes | Sessão presencial/remota de 2h |
| 3.4 | Treinamento de mecânicos | Tutorial PWA + sessão de 1h |
| 3.5 | Configurar integração API | Se cliente usar callcenter: gerar API Key, registrar webhooks |
| 3.6 | Operação paralela (30 dias) | Sistema antigo + novo rodando simultaneamente |
| 3.7 | Monitoramento intensivo | Logs, métricas, feedback diário |
| 3.8 | Ajustes pós-feedback | Correções de UX, performance, regras de negócio |
| 3.9 | Relatório de validação | Métricas: tempo médio, SLA, satisfação, bugs |
| 3.10 | Testar integração com sistema de atendimento | Validar fluxo completo: atendimento → API → chamado → webhook |

### Critérios de Aceite — FASE 3

- [ ] Zero incidentes de segurança (vazamento entre tenants)
- [ ] Tempo médio de abertura de chamado < 90 segundos (manual) / < 5 segundos (via API)
- [ ] Uptime > 99.5% durante os 30 dias
- [ ] API pública funcionando com sistema de atendimento (se aplicável)
- [ ] Webhooks entregando notificações corretamente
- [ ] Cliente piloto aprova prosseguir

---

## FASE 4 — Rollout Geral + Módulos Fase 2

**Duração estimada:** 8-12 semanas (contínuo)  
**Pré-requisito:** FASE 3 aprovada  
**Entregável:** Múltiplos clientes onboarded + módulos avançados

### 4.1 — Onboarding em Ondas

| Onda | Clientes | Semana | Estratégia |
|------|----------|--------|------------|
| 1 | 5 clientes | Semana 1-2 | Migração assistida completa |
| 2 | 15 clientes | Semana 3-4 | Semi-assistida + templates |
| 3 | 25 clientes | Semana 5-7 | Self-service + suporte por chat |
| 4 | Restantes | Semana 8-12 | Self-service completo |

### 4.2 — Módulos de Segunda Fase (paralelos ao rollout)

| Módulo | Prioridade | Estimativa |
|--------|------------|------------|
| Emissão de NFS-e (Nuvem Fiscal) | Alta | 2 semanas |
| Relatórios e BI (SLA, MTTR, custos) | Alta | 3 semanas |
| Controle de Estoque | Média | 3 semanas |
| Contratos de Manutenção | Média | 2 semanas |
| Schema por Tenant (Enterprise) | Baixa | 2 semanas |
| OAuth 2.0 (substituir API Keys) | Baixa | 2 semanas |
| SDKs (PHP, JavaScript) | Baixa | 2 semanas |

---

## Cronograma Visual

```
MÊS 1          MÊS 2          MÊS 3          MÊS 4+
├───────────────┼───────────────┼───────────────┼──────────
│ FASE 0        │               │               │
│ Setup (1 sem) │               │               │
│───────────────│               │               │
│ FASE 1 ──────────────────────────────────►   │
│ Backend MVP (5-6 sem)                        │
│    ├── Auth + Multi-tenancy                  │
│    ├── Chamados + Cadastros                  │
│    ├── Despacho + Importação                 │
│    ├── WebSocket + Notificações              │
│    └── API Pública + Webhooks                │
│               │ FASE 2 ──────────────────────────────►
│               │ Frontend MVP (4-5 sem)               │
│               │    ├── Layout + Auth                 │
│               │    ├── Painel de Chamados            │
│               │    ├── Cadastros + Importação        │
│               │    ├── PWA Mecânico                  │
│               │    └── Config + API Keys             │
│               │               │ FASE 3               │
│               │               │ Piloto (2-3 sem)     │
│               │               │──────────────────────│
│               │               │ FASE 4 ──────────────────►
│               │               │ Rollout (8-12 sem)
└───────────────┴───────────────┴───────────────┴──────────
```

> **Nota:** As Fases 1 e 2 podem ter sobreposição parcial. O frontend pode iniciar na Semana 3 do backend, assim que os endpoints de autenticação e chamados estiverem prontos.

---

## Dependências Críticas

| Dependência | Impacto | Mitigação |
|-------------|---------|-----------|
| Reunião de levantamento de requisitos | Bloqueia regras de negócio detalhadas | Agendar imediatamente |
| Respostas do cliente (questionário) | Define volume, SLA, integrações | Enviar questionário antes da reunião |
| Servidor Coolify configurado | Bloqueia deploy de staging | Configurar na primeira semana |
| Acesso a dados de migração do piloto | Bloqueia FASE 3 | Solicitar na FASE 1 |
| Definição de integração com sistema de atendimento | Impacta design da API pública | Definir contrato API na Sprint 1.5 |
