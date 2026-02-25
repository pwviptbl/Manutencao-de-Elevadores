# Plano de Desenvolvimento Sequencial

> Plataforma SaaS — Callcenter de Manutenção de Elevadores  
> Versão 1.0 | Fevereiro 2026

---

## Visão Geral das Fases

```
FASE 0 ──► FASE 1 ──► FASE 2 ──► FASE 3 ──► FASE 4 ──► FASE 5
Setup      Backend    Frontend    IA          Piloto     Rollout
Projeto    MVP        MVP         Integração  1 cliente  70 clientes
```

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

**Duração estimada:** 4-5 semanas  
**Pré-requisito:** FASE 0 completa  
**Entregável:** API REST funcional com multi-tenancy e chamados

### Sprint 1.1 — Autenticação e Multi-Tenancy (Semana 1-2)

| # | Tarefa | Detalhes |
|---|--------|----------|
| 1.1.1 | Instalar e configurar stancl/tenancy | Package Laravel para multi-tenancy |
| 1.1.2 | Modelar tabela `tenants` | ID, nome, slug, plano, configurações |
| 1.1.3 | Implementar RLS no PostgreSQL | Políticas por tenant_id em todas as tabelas |
| 1.1.4 | Autenticação com Laravel Sanctum | Login, logout, refresh token |
| 1.1.5 | Sistema de Roles (RBAC) | Roles: `admin`, `atendente`, `mecanico`, `visualizador` |
| 1.1.6 | Middleware de tenant | Validar tenant_id em todo request autenticado |
| 1.1.7 | Testes de isolamento | Garantir que Tenant A não acessa dados do Tenant B |
| 1.1.8 | Seeder de dados de teste | Tenants, usuários, roles para desenvolvimento |

### Sprint 1.2 — Gestão de Chamados / OS (Semana 2-3)

| # | Tarefa | Detalhes |
|---|--------|----------|
| 1.2.1 | Model `ServiceOrder` | Status, prioridade (P0-P3), tipo, descrição, timestamps |
| 1.2.2 | Model `Elevator` | Nº série, fabricante, modelo, andar, condomínio (ref) |
| 1.2.3 | Model `Condominium` | CNPJ, endereço, contatos, SLA contratado |
| 1.2.4 | Model `Technician` | Nome, CREA, telefone, região, disponibilidade |
| 1.2.5 | CRUD completo de Chamados | API REST com validação, filtros, paginação |
| 1.2.6 | CRUD de Elevadores | Vinculados a condomínios |
| 1.2.7 | CRUD de Condomínios | Com validação de CNPJ |
| 1.2.8 | CRUD de Mecânicos/Técnicos | Com região de atendimento |
| 1.2.9 | Máquina de estados do chamado | `aberto → atribuído → em_andamento → concluído → fechado` |
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
| 1.4.3 | Sistema de notificações | Push, e-mail, SMS (para emergências) |
| 1.4.4 | Alerta de emergência P0 | Visual + sonoro no painel; webhook para plantão |
| 1.4.5 | Testes de carga WebSocket | Simular 70 tenants com conexões simultâneas |

### Critérios de Aceite — FASE 1

- [ ] API REST completa com documentação (OpenAPI/Swagger)
- [ ] Multi-tenancy com RLS testado e validado
- [ ] RBAC funcional com 4 roles
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
| 2.2.1 | Dashboard principal | KPIs: chamados abertos, P0 ativos, SLA, fila |
| 2.2.2 | Lista de chamados | Tabela com filtros, busca, paginação, status colorido |
| 2.2.3 | Detalhe do chamado | Timeline, histórico, atribuição, fotos |
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

### Sprint 2.4 — PWA do Mecânico (Semana 4-5)

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

### Critérios de Aceite — FASE 2

- [ ] Painel responsivo funcionando em desktop e tablet
- [ ] PWA instalável no celular do mecânico
- [ ] Alertas P0 com som e alerta visual em < 2 segundos
- [ ] Importação com feedback visual em tempo real
- [ ] Validação de formulários client-side + server-side

---

## FASE 3 — Integração IA

**Duração estimada:** 3-4 semanas  
**Pré-requisito:** FASE 1 e FASE 2 completas  
**Entregável:** Triagem automática via WhatsApp funcional

### Sprint 3.1 — Filtro de Emergência + WhatsApp (Semana 1-2)

| # | Tarefa | Detalhes |
|---|--------|----------|
| 3.1.1 | Instalar Evolution API (self-hosted) | Docker, configuração de QR Code por tenant |
| 3.1.2 | Webhook de mensagens recebidas | Endpoint que recebe mensagens do WhatsApp |
| 3.1.3 | Filtro Regex (Camada 0) | Detecção de palavras de emergência |
| 3.1.4 | Ações automáticas P0 | Chamado + webhook + alerta + bypass do LLM |
| 3.1.5 | Testes de falso positivo/negativo | Suite de testes com 100+ variações de mensagens |

### Sprint 3.2 — LLM de Triagem (Semana 2-3)

| # | Tarefa | Detalhes |
|---|--------|----------|
| 3.2.1 | Integração com API OpenAI/Anthropic | GPT-4o-mini ou Claude Haiku |
| 3.2.2 | Prompt engineering de triagem | Coleta estruturada: tipo, local, urgência |
| 3.2.3 | Conversação multi-turno | Bot coleta informações faltantes |
| 3.2.4 | Criação automática de chamado | Dados coletados → Service Order via API |
| 3.2.5 | Fallback para atendente | Se LLM não conseguir classificar em 3 tentativas |
| 3.2.6 | Logging de conversas IA | Para auditoria e melhoria do prompt |

### Sprint 3.3 — VOIP e STT (Semana 3-4) — Opcional para MVP

| # | Tarefa | Detalhes |
|---|--------|----------|
| 3.3.1 | Setup Asterisk (self-hosted) | URA inteligente com menu de opções |
| 3.3.2 | Integração Whisper STT | Transcrição de áudio em tempo real |
| 3.3.3 | Pipeline voz → texto → LLM | Fluxo completo de triagem por voz |
| 3.3.4 | Gravação de chamadas | Armazenamento com retenção configurável |

### Critérios de Aceite — FASE 3

- [ ] Mensagem no WhatsApp com emergência → chamado P0 em < 3 segundos
- [ ] Triagem por IA gerando chamado correto em > 90% dos casos de rotina
- [ ] Fallback para humano funcionando sem perda de contexto
- [ ] Logs de IA auditáveis

---

## FASE 4 — Piloto com 1 Cliente

**Duração estimada:** 2-3 semanas  
**Pré-requisito:** FASE 3 completa  
**Entregável:** Validação em produção controlada

### Tarefas

| # | Tarefa | Detalhes |
|---|--------|----------|
| 4.1 | Selecionar cliente piloto | Idealmente empresa média, receptiva, ~50 elevadores |
| 4.2 | Migração assistida de dados | Importação de condomínios, elevadores, histórico |
| 4.3 | Treinamento de atendentes | Sessão presencial/remota de 2h |
| 4.4 | Treinamento de mecânicos | Tutorial PWA + sessão de 1h |
| 4.5 | Operação paralela (30 dias) | Sistema antigo + novo rodando simultaneamente |
| 4.6 | Monitoramento intensivo | Logs, métricas, feedback diário |
| 4.7 | Ajustes pós-feedback | Correções de UX, performance, regras de negócio |
| 4.8 | Relatório de validação | Métricas: tempo médio, SLA, satisfação, bugs |

### Critérios de Aceite — FASE 4

- [ ] Zero incidentes de segurança (vazamento entre tenants)
- [ ] Tempo médio de abertura de chamado < 90 segundos
- [ ] Uptime > 99.5% durante os 30 dias
- [ ] Cliente piloto aprova prosseguir

---

## FASE 5 — Rollout Geral + Módulos Fase 2

**Duração estimada:** 8-12 semanas (contínuo)  
**Pré-requisito:** FASE 4 aprovada  
**Entregável:** 70 clientes onboarded + módulos avançados

### 5.1 — Onboarding em Ondas

| Onda | Clientes | Semana | Estratégia |
|------|----------|--------|------------|
| 1 | 5 clientes | Semana 1-2 | Migração assistida completa |
| 2 | 15 clientes | Semana 3-4 | Semi-assistida + templates |
| 3 | 25 clientes | Semana 5-7 | Self-service + suporte por chat |
| 4 | 25 clientes | Semana 8-12 | Self-service completo |

### 5.2 — Módulos de Segunda Fase (paralelos ao rollout)

| Módulo | Prioridade | Estimativa |
|--------|------------|------------|
| Emissão de NFS-e (Nuvem Fiscal) | Alta | 2 semanas |
| Controle de Estoque | Média | 3 semanas |
| Contratos de Manutenção | Média | 2 semanas |
| Relatórios e BI (SLA, MTTR, custos) | Alta | 3 semanas |
| IA — Voz (Asterisk + Whisper + LLM) | Média | 3 semanas |
| Schema por Tenant (Enterprise) | Baixa | 2 semanas |

---

## Cronograma Visual

```
MÊS 1          MÊS 2          MÊS 3          MÊS 4          MÊS 5+
├───────────────┼───────────────┼───────────────┼───────────────┼──────────
│ FASE 0        │               │               │               │
│ Setup (1 sem) │               │               │               │
│───────────────│               │               │               │
│ FASE 1 ────────────────────►  │               │               │
│ Backend MVP (4-5 sem)         │               │               │
│               │ FASE 2 ────────────────────►  │               │
│               │ Frontend MVP (4-5 sem)        │               │
│               │               │ FASE 3 ────────────────────►  │
│               │               │ Integração IA (3-4 sem)       │
│               │               │               │ FASE 4        │
│               │               │               │ Piloto (2-3s) │
│               │               │               │───────────────│
│               │               │               │ FASE 5 ────────────────►
│               │               │               │ Rollout (8-12 sem)
└───────────────┴───────────────┴───────────────┴───────────────┴──────────
```

> **Nota:** As Fases 1 e 2 podem ter sobreposição parcial. O frontend pode iniciar na Semana 3 do backend, assim que os endpoints de autenticação e chamados estiverem prontos.

---

## Dependências Críticas

| Dependência | Impacto | Mitigação |
|-------------|---------|-----------|
| Reunião de levantamento de requisitos | Bloqueia regras de negócio detalhadas | Agendar imediatamente |
| Respostas do cliente (Seção 10 do doc técnico) | Define volume, SLA, integrações | Enviar questionário antes da reunião |
| Servidor Coolify configurado | Bloqueia deploy de staging | Configurar na primeira semana |
| Conta Evolution API / WhatsApp | Bloqueia integração IA | Iniciar setup na FASE 1 |
| Acesso a dados de migração do piloto | Bloqueia FASE 4 | Solicitar na FASE 2 |
