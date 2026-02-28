# Modelo de Negócio (SaaS B2B)

> Sistema de Gerenciamento de Manutenção de Elevadores (SaaS)  
> Versão 2.0 | Fevereiro 2026

---

## 1. Contexto de Negócio

### 1.1 Dois Produtos, Dois Mercados

O ecossistema é composto por **dois sistemas independentes** que podem ser contratados separadamente:

| Sistema | Operado por | Público-alvo | Função |
|---------|-------------|-------------|--------|
| **Sistema de Atendimento** | Empresa de callcenter (produto separado) | Operadores de atendimento | Receber ligações/WhatsApp, triagem por IA, abrir chamados via API |
| **Sistema de Gerenciamento** | Este sistema | Empresas de manutenção de elevadores | Gestão de chamados, mecânicos, elevadores, OS, relatórios |

### 1.2 Independência Comercial

```
┌─────────────────────────────────────────────────────────────────┐
│                     CENÁRIOS POSSÍVEIS                           │
│                                                                  │
│  Cenário 1: Empresa contrata AMBOS os sistemas                  │
│  → Atendimento abre chamados automaticamente via API             │
│  → Gerenciamento processa e despacha mecânicos                   │
│                                                                  │
│  Cenário 2: Empresa contrata APENAS Gerenciamento                │
│  → Usa o painel web manualmente para criar chamados              │
│  → Ou integra com seu próprio sistema via API                    │
│                                                                  │
│  Cenário 3: Empresa contrata APENAS Atendimento                  │
│  → Atendimento abre chamados no sistema próprio da empresa       │
│  → Não usa nosso sistema de gerenciamento                        │
│                                                                  │
│  Cenário 4: Empresa cancela Atendimento, mantém Gerenciamento   │
│  → Continua usando o gerenciamento normalmente                   │
│  → Cria chamados manualmente ou integra outro sistema            │
└─────────────────────────────────────────────────────────────────┘
```

### 1.3 Gargalos que o Gerenciamento Resolve

- Dados descentralizados: histórico de manutenção fragmentado em planilhas e sistemas legados
- Sem visibilidade consolidada de mecânicos, estoque e contratos
- Falta de rastreamento de SLA e tempo de resposta
- Sem padronização de checklist e procedimentos de manutenção
- Comunicação falha entre operador e mecânico em campo
- Nenhum sistema de despacho inteligente por região/disponibilidade

---

## 2. Planos de Assinatura — Sistema de Gerenciamento

| Plano | Preço/mês | Perfil | O que inclui |
|-------|-----------|--------|--------------|
| **Starter** | R$ 197 | Empresa pequena | Até 5 técnicos, 100 chamados/mês, sem API, sem NFS-e |
| **Pro** | R$ 397 | Empresa média | Até 15 técnicos, chamados ilimitados, API (rate limit básico), NFS-e |
| **Business** | R$ 697 | Empresa grande | Técnicos ilimitados, API (rate limit alto), NFS-e, Estoque, Relatórios BI |
| **Enterprise** | A negociar | Rede de elevadores | Schema exclusivo, SLA 99.9%, API sem rate limit, suporte dedicado |

### 2.1 Diferencial por Plano

```
Starter           Pro               Business           Enterprise
├── Chamados      ├── Chamados      ├── Chamados       ├── Tudo Business
├── Cadastros     ├── Cadastros     ├── Cadastros      ├── Schema exclusivo
├── Despacho      ├── Despacho      ├── Despacho       ├── SLA 99.9%
├── PWA Mecânico  ├── PWA Mecânico  ├── PWA Mecânico   ├── API sem rate limit
├── Dashboard     ├── Dashboard     ├── Dashboard      ├── Suporte dedicado
├── Importação    ├── Importação    ├── Importação     └── Customizações
│                 ├── API Pública   ├── API Pública
│                 ├── Webhooks      ├── Webhooks
│                 ├── NFS-e         ├── NFS-e
│                 │                 ├── Estoque
│                 │                 ├── Contratos
│                 │                 └── Relatórios BI
```

### 2.2 Limites de API por Plano

| Plano | API | Rate Limit | Webhooks | API Keys |
|-------|-----|------------|----------|----------|
| Starter | ❌ Não incluso | — | — | — |
| Pro | ✅ | 60 req/min | 3 webhooks | 2 keys |
| Business | ✅ | 300 req/min | 10 webhooks | 5 keys |
| Enterprise | ✅ | Sem limite | Ilimitado | Ilimitado |

---

## 3. Projeção de Receita — Gerenciamento

| Cenário | Clientes | Ticket Médio | Receita Bruta/mês |
|---------|----------|-------------|-------------------|
| **Conservador** | 40 | R$ 397 | R$ 15.880 |
| **Realista** (mix Pro/Business) | 60 | R$ 500 | R$ 30.000 |
| **Otimista** (expansão) | 100 | R$ 550 | R$ 55.000 |

> **Nota:** A projeção mudou em relação à v1.0 pois o ticket médio é menor (gerenciamento apenas, sem atendimento incluso). A receita do sistema de atendimento é contabilizada separadamente.

### Margem

- **Custo de infraestrutura:** ~R$ 500 a 1.000/mês (sem custos de WhatsApp/VoIP/LLM)
- **Margem bruta:** superior a **96%** após desenvolvimento amortizado

---

## 4. Custos de Infraestrutura

**Base de cálculo:** 60 clientes ativos.

| Item | Custo Mensal |
|------|-------------|
| Hospedagem VPS (Coolify) | R$ 200 a 400/mês |
| PostgreSQL (mesmo servidor) | Incluso |
| Redis (mesmo servidor) | Incluso |
| Backup (S3/Backblaze) | R$ 30 a 50/mês |
| Domínio + SSL | R$ 10/mês |
| NFS-e (Nuvem Fiscal) — Fase 2 | R$ 150/mês |
| **TOTAL** | **R$ 390 a 610/mês** |

> **Nota:** Custos de WhatsApp, VoIP e LLM ficam no sistema de atendimento, reduzindo drasticamente o custo do gerenciamento.

---

## 5. Revenue Share por Integração (Opcional)

Para incentivar o uso da API e monetizar integrações:

> Cobrar **R$ 0,50 por chamado aberto via API** acima da franquia do plano.

| Plano | Franquia API/mês | Excedente |
|-------|-------------------|-----------|
| Pro | 500 chamados via API | R$ 0,50/chamado extra |
| Business | 2.000 chamados via API | R$ 0,30/chamado extra |
| Enterprise | Ilimitado | — |

### Projeção (60 clientes, 20% usando API)

```
12 clientes usando API × média 300 chamados excedentes/mês
= 3.600 chamados × R$ 0,50 = R$ 1.800/mês adicional
```

---

## 6. Estratégia de Onboarding

### 6.1 Redução de Atrito

| Estratégia | Detalhes |
|-----------|----------|
| Migração assistida gratuita | Inclusa no plano Business |
| Templates pré-formatados | CSV/Excel disponibilizados **antes** do contrato |
| Operação paralela | Sistema antigo + novo por 30 dias |
| API de importação | Migração de dados automatizada para clientes com sistemas legados |
| Documentação da API | Disponível publicamente para avaliação técnica antes da contratação |

### 6.2 Funil de Conversão

```
Empresas de manutenção (relacionamento existente via callcenter)
    └── Apresentação do sistema de gerenciamento (demo)
        └── Trial gratuito de 14 dias (plano Pro)
            └── Migração assistida (Business)
                └── Operação paralela (30 dias)
                    └── Desligamento sistema antigo
                        └── Upsell: NFS-e, Estoque, Relatórios BI
```

### 6.3 Vantagem Competitiva via Callcenter

A empresa de callcenter já possui **relacionamento com ~70 empresas**. Mesmo que os sistemas sejam independentes, o callcenter pode:

- **Indicar** o sistema de gerenciamento para seus clientes
- **Demonstrar** a integração API funcionando na prática
- **Cobrar comissão** por indicação (revenue share entre os 2 produtos)

---

## 7. Perguntas para Reunião de Levantamento

> **Regra:** estas perguntas devem ser respondidas **antes** de iniciar qualquer engenharia detalhada.

### 7.1 Operacional

- [ ] Qual o volume médio de chamados por dia (total e por cliente)?
- [ ] Os mecânicos têm smartphone? Usam algum app hoje?
- [ ] Existe SLA de atendimento contratado com os condomínios hoje?
- [ ] Como é feito o despacho de mecânico atualmente?
- [ ] Os clientes já têm algum sistema de gestão de OS?

### 7.2 Técnico / Dados

- [ ] Existe software atual que precisa de migração de dados?
- [ ] Em que formato estão os dados hoje? (planilha, sistema, papel)
- [ ] Quantos elevadores em média cada cliente administra?
- [ ] Os clientes têm equipe técnica para usar API ou preferem só painel web?

### 7.3 Comercial

- [ ] Qual o ticket médio cobrado por cliente hoje?
- [ ] Os clientes pagam pelo software básico oferecido hoje?
- [ ] Há clientes que já manifestaram interesse em solução mais completa?
- [ ] Qual o prazo ideal para ter um MVP funcionando?
- [ ] Quantas empresas usariam a integração via API desde o início?
