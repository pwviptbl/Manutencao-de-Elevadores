# Modelo de Negócio (SaaS B2B)

> Plataforma SaaS — Callcenter de Manutenção de Elevadores  
> Versão 1.0 | Fevereiro 2026

---

## 1. Contexto de Negócio

O cliente opera um callcenter B2B atendendo aproximadamente **70 empresas** de manutenção de elevadores. O modelo atual é totalmente manual: quando um condomínio liga ou envia mensagem reportando um problema, um atendente humano abre chamado no software específico de cada cliente — o que exige que a equipe domine **múltiplos sistemas distintos**.

### 1.1 Gargalos Identificados

- Atendentes precisam dominar **N softwares diferentes** (um por empresa cliente)
- Abertura de chamado manual: **média de 4 a 7 minutos** por atendimento
- Nenhuma automação de triagem ou escalonamento por urgência
- Dados descentralizados: histórico de manutenção fragmentado
- Sem visibilidade consolidada de mecânicos, estoque e contratos

### 1.2 Oportunidade

O cliente já possui o ativo mais difícil de construir: **relacionamento consolidado com 70 empresas** e o fluxo operacional mapeado. A plataforma é a monetização desse relacionamento.

---

## 2. Planos de Assinatura

| Plano | Preço/mês | Perfil | O que inclui |
|-------|-----------|--------|--------------|
| **Starter** | R$ 297 | Empresa pequena | Até 5 técnicos, 100 chamados/mês, sem IA, sem NFS-e |
| **Pro** | R$ 597 | Empresa média | Até 15 técnicos, ilimitado, IA WhatsApp, NFS-e |
| **Business** | R$ 997 | Empresa grande | Ilimitado, IA Voz + WhatsApp, NFS-e, Estoque, Migração assistida |
| **Enterprise** | A negociar | Rede de elevadores | Schema exclusivo, SLA 99.9%, suporte dedicado, API aberta |

### Diferencial por Plano

```
Starter          Pro              Business          Enterprise
├── Chamados     ├── Chamados     ├── Chamados      ├── Tudo Business
├── Cadastros    ├── Cadastros    ├── Cadastros     ├── Schema exclusivo
├── Despacho     ├── Despacho     ├── Despacho      ├── SLA 99.9%
├── PWA          ├── PWA          ├── PWA           ├── API aberta
├── Importação   ├── Importação   ├── Importação    ├── Suporte dedicado
│                ├── IA WhatsApp  ├── IA WhatsApp   └── Customizações
│                ├── NFS-e        ├── IA Voz
│                │                ├── NFS-e
│                │                ├── Estoque
│                │                └── Migração assistida
```

---

## 3. Projeção de Receita

| Cenário | Clientes | Ticket Médio | Receita Bruta/mês |
|---------|----------|-------------|-------------------|
| **Conservador** (50% no Pro) | 70 | R$ 597 | R$ 41.790 |
| **Realista** (mix Pro/Business) | 70 | R$ 750 | R$ 52.500 |
| **Otimista** (expansão 100 clientes) | 100 | R$ 750 | R$ 75.000 |

### Margem

- **Custo de infraestrutura:** ~R$ 1.000 a 2.000/mês em qualquer cenário
- **Margem bruta:** superior a **95%** após desenvolvimento amortizado

---

## 4. Custos de Infraestrutura

**Base de cálculo:** 70 clientes ativos, média de 500 chamados/dia (agregado).

### 4.1 Comparativo: MVP Self-Hosted vs Stack Paga

| Item | Stack Paga (Twilio/Zenvia) | MVP Self-Hosted |
|------|--------------------------|-----------------|
| Hospedagem + banco | R$ 300 a 800/mês | R$ 200 a 500/mês (Coolify VPS) |
| WhatsApp | R$ 400 a 900/mês | R$ 50/mês (infra Evolution API) |
| VOIP | R$ 400 a 900/mês | R$ 150 a 400/mês (Asterisk VPS) |
| LLM (GPT-4o-mini) | R$ 80 a 220/mês | R$ 80 a 220/mês (igual) |
| STT (Whisper API) | R$ 30 a 80/mês | R$ 30 a 80/mês (ou grátis self-hosted) |
| NFS-e (Nuvem Fiscal) | R$ 150/mês | R$ 150/mês (igual) |
| **TOTAL** | **R$ 1.360 a 2.100/mês** | **R$ 660 a 1.400/mês** |

### 4.2 Evolução para Produção

- Evolution API adequada para MVP e validação
- Migrar para **Meta Cloud API** quando volume > 5.000 conversas/mês
- Custo adicional diluído no preço do plano Business

---

## 5. Revenue Share por Automação (Opcional)

> Cobrar **R$ 1,50 por chamado fechado automaticamente pela IA** sem intervenção humana.

Isso alinha o incentivo do cliente com o uso da plataforma:
- Quanto mais a IA trabalha → mais ele paga
- Quanto mais a IA trabalha → mais ele **economiza** em atendentes
- O cliente só paga quando a automação entrega valor real

### Projeção (500 chamados/dia, 30% automação IA)

```
500 chamados/dia × 30% automação = 150 chamados IA/dia
150 × R$ 1,50 = R$ 225/dia
R$ 225 × 30 dias = R$ 6.750/mês adicional
```

---

## 6. Estratégia de Onboarding

### 6.1 Redução de Atrito

A maior barreira para fechar negócio não é tecnológica — é a **fricção de migração**.

| Estratégia | Detalhes |
|-----------|----------|
| Migração assistida gratuita | Inclusa no plano Business |
| Templates pré-formatados | Disponibilizados **antes** do contrato |
| Operação paralela | Sistema antigo + novo por 30 dias |
| Suporte de implantação | SLA de resposta em 2h na primeira semana |

### 6.2 Funil de Conversão

```
70 empresas (relacionamento existente)
    └── Apresentação da plataforma (POC)
        └── Migração assistida gratuita (Business)
            └── Operação paralela (30 dias)
                └── Desligamento sistema antigo
                    └── Upsell: IA, NFS-e, Estoque
```

---

## 7. Perguntas para Reunião de Levantamento

> **Regra:** estas perguntas devem ser respondidas **antes** de iniciar qualquer engenharia detalhada.

### 7.1 Operacional

- [ ] Qual o volume médio de chamados por dia (total e por cliente)?
- [ ] Os clientes já têm WhatsApp Business próprio ou tudo cai no número do callcenter?
- [ ] O callcenter emite OS em nome dos clientes ou apenas repassa para o sistema deles?
- [ ] Os mecânicos têm smartphone? Usam algum app hoje?
- [ ] Existe SLA de atendimento contratado com os clientes hoje?

### 7.2 Técnico / Dados

- [ ] Existe software atual que precisa de integração ou apenas migração?
- [ ] Há contrato de exclusividade com softwares atuais?
- [ ] Em que formato estão os dados hoje? (planilha, sistema, papel)
- [ ] Quantos elevadores em média cada cliente administra?

### 7.3 Comercial

- [ ] Qual o ticket médio cobrado por cliente hoje (sem software)?
- [ ] Os clientes pagam pelo software básico oferecido hoje?
- [ ] Há clientes que já manifestaram interesse em solução mais completa?
- [ ] Qual o prazo ideal para ter um MVP funcionando?
