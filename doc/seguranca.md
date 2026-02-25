# Segurança e DevSecOps

> Plataforma SaaS — Callcenter de Manutenção de Elevadores  
> Versão 1.0 | Fevereiro 2026

---

## 1. Princípio Fundamental

> **70 empresas concorrentes compartilham a mesma infraestrutura.** Um vazamento ou falha de controle de acesso (IDOR) destrói o negócio. A segurança é construída em camadas, com o banco de dados como última linha de defesa — independente do código da aplicação.

---

## 2. Camadas de Segurança

```
┌───────────────────────────────────────────────────┐
│            BORDA (Nginx / Cloudflare)              │
│         Rate limiting por tenant + DDoS            │
├───────────────────────────────────────────────────┤
│            TRANSPORTE (HTTPS)                      │
│         TLS obrigatório em toda comunicação        │
├───────────────────────────────────────────────────┤
│            APLICAÇÃO (Laravel)                     │
│  Sanctum (cookie/token) + tenant_id em middleware  │
│    stancl/tenancy: contexto automático             │
├───────────────────────────────────────────────────┤
│            BANCO DE DADOS (PostgreSQL)             │
│    RLS: isolamento físico de dados por tenant      │
│    Última linha de defesa — independe do código     │
└───────────────────────────────────────────────────┘
```

---

## 3. Isolamento Multi-Tenant

| Mecanismo | Onde Atua | O que Protege |
|-----------|-----------|---------------|
| **RLS PostgreSQL** | Banco de dados | Isolamento físico de dados por tenant |
| **Sanctum + tenant_id** | API (middleware) | Autenticação e autorização por requisição |
| **stancl/tenancy** | Laravel (aplicação) | Contexto de tenant injetado automaticamente |
| **Rate limiting** | Borda (Nginx/Cloudflare) | Prevenção de abuso e DDoS por tenant |
| **Schema por tenant** | Banco (opcional, tier Enterprise) | Isolamento total para clientes críticos |

### 3.1 RLS — Row-Level Security

Cada tabela crítica recebe política de RLS. O banco **recusa** entregar dados do Tenant A para o Tenant B **mesmo que haja um bug na API**.

```sql
-- Habilitar RLS na tabela
ALTER TABLE service_orders ENABLE ROW LEVEL SECURITY;

-- Política de isolamento
CREATE POLICY tenant_isolation ON service_orders
    USING (tenant_id = current_setting('app.tenant_id')::uuid);

-- Forçar RLS mesmo para owner da tabela
ALTER TABLE service_orders FORCE ROW LEVEL SECURITY;
```

### 3.2 Middleware de Tenant (Laravel)

```php
// Toda requisição autenticada DEVE conter tenant_id
// O middleware injeta o contexto no PostgreSQL
class EnsureTenant
{
    public function handle($request, Closure $next)
    {
        $tenantId = auth()->user()->tenant_id;
        
        DB::statement("SELECT set_config('app.tenant_id', ?, false)", [(string) $tenantId]);
        
        return $next($request);
    }
}
```

---

## 4. Checklist DevSecOps — Obrigatório desde o Dia Zero

| Camada | Controle | Ferramenta | Status |
|--------|----------|------------|--------|
| **API** | Sanctum com tenant_id validado em todo middleware | Laravel Sanctum + middleware `EnsureTenant` | 🔲 |
| **Banco** | RLS obrigatório + usuário DB sem permissão de DROP | PostgreSQL nativo | 🔲 |
| **Secrets** | Zero secrets em código ou logs | Variável de ambiente / Vault | 🔲 |
| **SAST** | Análise estática a cada commit | Enlightn + Psalm + Semgrep | 🔲 |
| **Logs** | Nenhum dado PII em log — somente IDs | Política de logging no Laravel | 🔲 |
| **Transporte** | HTTPS forçado em toda comunicação | Nginx + Let's Encrypt | 🔲 |
| **Backups** | Snapshot diário com restore testado mensalmente | pg_dump + S3 / Backblaze | 🔲 |
| **Dependências** | Auditoria de pacotes no CI | `composer audit` + `npm audit` | 🔲 |

---

## 5. Autenticação e Autorização

### 5.1 Autenticação

- **Laravel Sanctum** para autenticação SPA (cookie-based) e API (token-based)
- Tokens com expiração configurável
- Rate limiting no login: 5 tentativas por minuto
- Logout invalida todos os tokens ativos

### 5.2 Roles (RBAC)

| Role | Permissões |
|------|-----------|
| **admin** | Tudo: configurações do tenant, usuários, relatórios, importação |
| **atendente** | Chamados: criar, editar, atribuir, visualizar. Alertas em tempo real |
| **mecanico** | Chamados atribuídos: aceitar, checklist, fotos, fechar OS |
| **visualizador** | Somente leitura: dashboard, relatórios, histórico |

### 5.3 Regras de Acesso Cruzado

```
✗ Mecânico NÃO pode ver chamados de outros mecânicos
✗ Atendente NÃO pode alterar configurações do tenant
✗ Visualizador NÃO pode criar ou editar nada
✗ NENHUM role acessa dados de outro tenant (RLS garante)
```

---

## 6. Segurança da IA

### 6.1 Risco

Manutenção de elevadores é um domínio de **risco de vida**. Um LLM pode alucinar ou classificar incorretamente uma emergência.

### 6.2 Mitigação: Filtro Determinístico (Camada 0)

A IA **NUNCA** é consultada em situações de emergência:

1. **Filtro Regex** processa a mensagem **antes** do LLM
2. Se detectar palavra-chave de emergência → **bypass total**
3. Chamado P0 criado por lógica determinística (100% previsível)
4. LLM só é consultado para chamados de rotina

### 6.3 Palavras-Chave de Emergência

```
preso | travado | pessoa presa
caiu | queda | despencou
fumaça | fumaca | fogo | incêndio | incendio | cheiro de queimado
socorro | ajuda | emergência | emergencia | urgente
não abre (em contexto de pessoa dentro)
```

### 6.4 Logging e Auditoria de IA

- Toda interação com LLM é logada (input, output, latência, custo)
- Nenhum dado PII é enviado ao LLM (somente descrições anonimizadas)
- Logs retidos por 90 dias para auditoria
- Revisão mensal de falsos positivos/negativos

---

## 7. Proteção de Dados (LGPD)

| Requisito | Implementação |
|-----------|---------------|
| Consentimento | Termos aceitos no onboarding do tenant |
| Minimização | Apenas dados necessários para operação |
| Acesso | Usuário pode solicitar exportação de seus dados |
| Exclusão | Direito ao esquecimento implementado por tenant |
| Portabilidade | Exportação completa em CSV/JSON |
| Logs PII | Nenhum dado pessoal em logs — somente IDs referenciáveis |
| Retenção | Dados operacionais retidos por 5 anos (obrigação fiscal); dados pessoais conforme política |

---

## 8. Segurança da Infraestrutura

### 8.1 Servidor (Coolify)

- [ ] SSH apenas por chave (senha desabilitada)
- [ ] Firewall: apenas portas 80, 443, 22 abertas
- [ ] Atualizações automáticas de segurança do SO
- [ ] Monitoramento de uptime via healthcheck

### 8.2 Banco de Dados

- [ ] PostgreSQL **não exposto** na internet (apenas localhost / rede interna)
- [ ] Usuário da aplicação **sem permissão de DROP**
- [ ] Backups criptografados
- [ ] Restore testado mensalmente

### 8.3 Redis

- [ ] Senha configurada
- [ ] Não exposto na internet
- [ ] Dados sensíveis nunca armazenados sem expiração

### 8.4 Backup e Disaster Recovery

| Item | Frequência | Ferramenta | Destino |
|------|-----------|------------|---------|
| Banco (pg_dump) | Diário | Cron + script | S3 / Backblaze |
| Uploads/fotos | Diário | rsync / rclone | S3 / Backblaze |
| Configuração Coolify | Semanal | Export + git | Repositório privado |
| Teste de restore | Mensal | Manual | Ambiente de staging |

---

## 9. Resposta a Incidentes

### Classificação

| Severidade | Exemplo | Tempo de Resposta |
|-----------|---------|-------------------|
| **Crítico** | Vazamento entre tenants, acesso não autorizado | < 1 hora |
| **Alto** | Falha de autenticação, dados corrompidos | < 4 horas |
| **Médio** | Feature com bug de segurança, log com PII | < 24 horas |
| **Baixo** | Dependência com CVE de baixo risco | < 1 semana |

### Procedimento (Crítico)

1. Isolar o sistema afetado
2. Notificar stakeholders
3. Investigar e documentar
4. Corrigir e validar
5. Post-mortem e atualizar políticas
