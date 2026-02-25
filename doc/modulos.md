# Módulos da Plataforma

> Plataforma SaaS — Callcenter de Manutenção de Elevadores  
> Versão 1.0 | Fevereiro 2026

---

## Visão Geral

Os módulos estão organizados em duas fases: **MVP** (obrigatório no primeiro release) e **Segunda Fase** (expansão pós-validação com piloto).

```
MVP (Fase 1)                          Segunda Fase
┌─────────────────────────┐           ┌─────────────────────────┐
│ ✅ Gestão de Chamados   │           │ 📄 Emissão de NFS-e     │
│ ✅ Cadastro Elevadores  │           │ 📦 Controle de Estoque  │
│ ✅ Cadastro Condomínios │           │ 📋 Contratos Manutenção │
│ ✅ Despacho Mecânicos   │           │ 📊 Relatórios e BI      │
│ ✅ Painel Tempo Real    │           │ 💬 IA — WhatsApp        │
│ ✅ App Mecânico (PWA)   │           │ 🎤 IA — Voz             │
│ ✅ Multi-tenancy        │           │ 🏢 Schema por Tenant    │
│ ✅ Importação Assíncrona│           │                         │
│ ✅ Autenticação (RBAC)  │           │                         │
└─────────────────────────┘           └─────────────────────────┘
```

---

## MVP — Módulos Obrigatórios

### 1. Gestão de Chamados (OS)

**Descrição:** Abertura, acompanhamento, histórico e fechamento de ordens de serviço.

| Funcionalidade | Detalhes |
|----------------|----------|
| Criar chamado | Manual (painel) ou automático (IA) |
| Prioridades | P0 (emergência), P1 (urgente), P2 (normal), P3 (baixa) |
| Status | `aberto → atribuído → em_andamento → concluído → fechado` |
| Tipos | Corretiva, Preventiva, Emergência |
| Origem | WhatsApp, Voz, Painel, Importação |
| Histórico | Timeline com todas as mudanças, quem fez e quando |
| Filtros | Por status, prioridade, elevador, condomínio, mecânico, período |
| SLA | Tempo máximo por prioridade; alertas de violação |

### 2. Cadastro de Elevadores

| Campo | Obrigatório | Tipo |
|-------|-------------|------|
| Nº de série | Sim | String |
| Fabricante | Sim | String |
| Modelo | Sim | String |
| Andar | Sim | Integer |
| Condomínio | Sim | FK |
| Data última revisão | Não | Date |
| Fotos | Não | Array de imagens |
| Observações | Não | Texto livre |

### 3. Cadastro de Condomínios

| Campo | Obrigatório | Tipo |
|-------|-------------|------|
| Nome | Sim | String |
| CNPJ | Sim | String (validado) |
| Endereço completo | Sim | String |
| CEP | Sim | String |
| Cidade / UF | Sim | String |
| Telefone | Sim | String |
| E-mail de contato | Sim | String (validado) |
| SLA contratado (horas) | Não | Integer |

### 4. Despacho de Mecânicos

| Funcionalidade | Detalhes |
|----------------|----------|
| Fila de chamados | Ordenada por prioridade e SLA |
| Atribuição | Manual (atendente) ou automática (por região/disponibilidade) |
| Status do mecânico | Disponível, Em atendimento, Indisponível |
| Rastreamento | Qual mecânico está em qual chamado |
| Região | Mecânico atende área geográfica definida |

### 5. Painel em Tempo Real

| Widget | Dados |
|--------|-------|
| Chamados abertos | Total + breakdown por prioridade |
| Alertas P0 | Destaque visual com som |
| Fila de atendimento | Próximos chamados a vencer SLA |
| KPIs | Tempo médio de abertura, resolução, SLA cumprido |
| Mecânicos ativos | Quem está onde, disponibilidade |

**Tecnologia:** WebSocket via Laravel Reverb + Echo — atualização < 500ms.

### 6. App do Mecânico (PWA)

| Funcionalidade | Detalhes |
|----------------|----------|
| Ver chamados atribuídos | Lista mobile-friendly |
| Aceitar/recusar | Ação rápida com confirmação |
| Checklist de manutenção | Itens dinâmicos por tipo |
| Upload de fotos | Câmera do celular, compressão |
| Assinatura digital | Canvas touch para assinatura |
| Fechar OS | Resumo + assinatura + envio |
| Modo offline básico | Cache local; sync ao reconectar |

**Entrega:** PWA instalável via browser — sem app store.

### 7. Multi-Tenancy

| Aspecto | Implementação |
|---------|---------------|
| Isolamento de dados | RLS (Row-Level Security) no PostgreSQL |
| Contexto automático | stancl/tenancy injeta tenant_id |
| Configuração por tenant | Logo, cores, SLA, plano |
| Planos | Starter, Pro, Business, Enterprise |

### 8. Importação Assíncrona

| Etapa | Detalhes |
|-------|----------|
| Upload | CSV ou Excel via drag-and-drop |
| Validação | Colunas obrigatórias, tipos, duplicatas |
| Processamento | Job assíncrono, lotes de 500 registros |
| Progresso | Tempo real via WebSocket |
| Relatório | Erros linha a linha (registro + motivo) |

**Templates disponíveis:**

| Template | Campos Obrigatórios |
|----------|-------------------|
| Condomínios | Nome, CNPJ, Endereço, CEP, Cidade, UF, Telefone, Email |
| Elevadores | Nº série, Fabricante, Modelo, Andar, Condomínio (ref), Data última revisão |
| Histórico de OS | Data, Tipo manutenção, Elevador (ref), Mecânico, Descrição |
| Mecânicos | Nome, CREA (opcional), Telefone, Região |

### 9. Autenticação e RBAC

| Role | Acesso |
|------|--------|
| **Admin** | Tudo: config, usuários, relatórios, importação |
| **Atendente** | Chamados, despacho, cadastros, alertas |
| **Mecânico** | Seus chamados, checklist, fotos, fechar OS |
| **Visualizador** | Somente leitura: dashboard e relatórios |

---

## Segunda Fase — Módulos de Expansão

### 10. Emissão de NFS-e

| Aspecto | Detalhes |
|---------|----------|
| Integração | Nuvem Fiscal API |
| Trigger | OS fechada → emissão automática ou manual |
| Multi-município | Suporte a diferentes prefeituras |
| Armazenamento | XML e PDF da nota vinculados à OS |

### 11. Controle de Estoque

| Aspecto | Detalhes |
|---------|----------|
| Peças por técnico/filial | Inventário distribuído |
| Baixa automática | Ao fechar OS, peças usadas são deduzidas |
| Alertas | Estoque mínimo, reposição necessária |
| Relatórios | Consumo por período, mecânico, tipo de peça |

### 12. Contratos de Manutenção

| Aspecto | Detalhes |
|---------|----------|
| Tipo | Mensalidade por elevador |
| Vencimentos | Alertas de renovação automáticos |
| SLA contratual | Definido por contrato, aplicado nos chamados |
| Histórico | Renovações, reajustes, cancelamentos |

### 13. Relatórios e BI

| Relatório | Métricas |
|-----------|----------|
| SLA | % cumprido vs violado, por período |
| MTTR | Tempo médio de reparo por tipo/elevador |
| Chamados | Volume por elevador, condomínio, período |
| Custos | Custo por OS, por mecânico, por peça |
| Performance | Ranking de mecânicos, tempo de resposta |

### 14. IA — WhatsApp

| Aspecto | Detalhes |
|---------|----------|
| Plataforma | Evolution API (MVP) → Meta Cloud API (produção) |
| Fluxo | Mensagem → Filtro Regex → LLM → Chamado |
| Multi-tenant | QR Code ou número por empresa |
| Conversação | Multi-turno para coleta de informações |

### 15. IA — Voz

| Aspecto | Detalhes |
|---------|----------|
| VOIP | Asterisk self-hosted (ou Twilio) |
| STT | Whisper API (OpenAI ou self-hosted) |
| Pipeline | Ligação → Transcrição → Filtro → LLM → Chamado |
| URA | Menu inteligente de opções antes da IA |
| Gravação | Armazenamento com retenção configurável |

### 16. Schema por Tenant (Enterprise)

| Aspecto | Detalhes |
|---------|----------|
| Quando | Clientes que exigem isolamento total de banco |
| Como | Schema exclusivo no PostgreSQL |
| Custo | Maior complexidade de migração e manutenção |
| SLA | 99.9% garantido, suporte dedicado |
