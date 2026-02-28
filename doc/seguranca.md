# Segurança e DevSecOps

> Sistema de Gerenciamento de Manutenção de Elevadores (SaaS)  
> Versão 2.0 | Fevereiro 2026

---

## 1. Princípio Fundamental

> **Múltiplas empresas concorrentes compartilham a mesma infraestrutura.** Um vazamento ou falha de controle de acesso (IDOR) destrói o negócio. A segurança é construída em camadas, com o banco de dados como última linha de defesa — independente do código da aplicação.

> **A plataforma expõe uma API pública.** Além da proteção interna, a API precisa de autenticação robusta, rate limiting, e auditoria de todas as requisições externas.

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
│         API PÚBLICA (Autenticação Externa)         │
│    API Keys com scopes + rate limit por key        │
│    Validação de Idempotency-Key                    │
├───────────────────────────────────────────────────┤
│         APLICAÇÃO (Laravel)                        │
│  Sanctum (cookie/token) + tenant_id em middleware  │
│    stancl/tenancy: contexto automático             │
├───────────────────────────────────────────────────┤
│         BANCO DE DADOS (PostgreSQL)                │
│    RLS: isolamento físico de dados por tenant      │
│    Última linha de defesa — independe do código     │
└───────────────────────────────────────────────────┘
```

---

## 3. Isolamento Multi-Tenant

| Mecanismo | Onde Atua | O que Protege |
|-----------|-----------|---------------|
| **RLS PostgreSQL** | Banco de dados | Isolamento físico de dados por tenant |
| **Sanctum + tenant_id** | API interna (middleware) | Autenticação de usuários humanos |
| **API Key + tenant_id** | API pública (middleware) | Autenticação de integrações externas |
| **stancl/tenancy** | Laravel (aplicação) | Contexto de tenant injetado automaticamente |
| **Rate limiting** | Borda (Nginx/Cloudflare) | Prevenção de abuso e DDoS por tenant |
| **Schema por tenant** | Banco (opcional, tier Enterprise) | Isolamento total para clientes críticos |

### 3.1 RLS — Row-Level Security

Cada tabela crítica recebe política de RLS. O banco **recusa** entregar dados do Tenant A para o Tenant B **mesmo que haja um bug na API ou na autenticação da API Key**.

```sql
-- Habilitar RLS na tabela
ALTER TABLE service_orders ENABLE ROW LEVEL SECURITY;

-- Política de isolamento
CREATE POLICY tenant_isolation ON service_orders
    USING (tenant_id = current_setting('app.tenant_id')::uuid);

-- Forçar RLS mesmo para owner da tabela
ALTER TABLE service_orders FORCE ROW LEVEL SECURITY;
```

### 3.2 Middleware de Tenant — Usuários (Laravel)

```php
// Toda requisição de usuário autenticado via Sanctum
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

### 3.3 Middleware de Tenant — API Key (Integrações)

```php
// Toda requisição de integração autenticada via API Key
class AuthenticateApiKey
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if (!$token || !str_starts_with($token, 'elev_')) {
            return response()->json([
                'error' => ['code' => 'UNAUTHORIZED', 'message' => 'API Key inválida']
            ], 401);
        }
        
        $keyHash = hash('sha256', $token);
        $apiKey = ApiKey::where('key_hash', $keyHash)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->first();
        
        if (!$apiKey) {
            return response()->json([
                'error' => ['code' => 'UNAUTHORIZED', 'message' => 'API Key inválida ou expirada']
            ], 401);
        }
        
        // Injetar tenant no RLS
        DB::statement("SELECT set_config('app.tenant_id', ?, false)", [(string) $apiKey->tenant_id]);
        
        // Registrar uso
        $apiKey->touch('last_used_at');
        
        // Disponibilizar key e scopes no request
        $request->merge(['api_key' => $apiKey]);
        
        return $next($request);
    }
}
```

### 3.4 Middleware de Scopes

```php
// Verificar se API Key tem o scope necessário
class CheckApiScope
{
    public function handle($request, Closure $next, string $scope)
    {
        $apiKey = $request->get('api_key');
        
        if (!in_array($scope, $apiKey->scopes)) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => "Scope '{$scope}' necessário para esta operação"
                ]
            ], 403);
        }
        
        return $next($request);
    }
}
```

---

## 4. Segurança da API Pública

### 4.1 API Keys — Armazenamento Seguro

| Controle | Implementação |
|----------|---------------|
| **Hashing** | SHA-256 — key nunca armazenada em texto plano |
| **Prefixo** | `elev_pk_` para produção, `elev_sk_` para sandbox |
| **Exibição única** | Key exibida apenas no momento da criação |
| **Rotação** | Admin pode revogar e gerar nova key a qualquer momento |
| **Expiração** | Configurável — com alerta 7 dias antes |
| **IP Whitelist** | Opcional — restringir key a IPs específicos |

### 4.2 Rate Limiting da API

| Camada | Implementação |
|--------|---------------|
| **Nginx** | Limite global por IP (proteção DDoS) |
| **Laravel** | Limite por API Key conforme plano do tenant |
| **Headers** | `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset` |
| **Excedido** | HTTP 429 com `Retry-After` header |

### 4.3 Idempotência

| Controle | Implementação |
|----------|---------------|
| **Header** | `Idempotency-Key` em requisições POST |
| **Storage** | Redis com TTL de 24 horas |
| **Comportamento** | Mesma key = mesma resposta (sem criar duplicata) |
| **Obrigatoriedade** | Recomendado, não obrigatório |

### 4.4 Validação de Input na API

| Controle | Implementação |
|----------|---------------|
| **Content-Type** | Apenas `application/json` aceito |
| **Tamanho** | Limite de 1MB por request body |
| **Sanitização** | Todos os campos de texto sanitizados (XSS) |
| **Validação** | FormRequest do Laravel com regras por endpoint |
| **UUID** | Validação de formato UUID em todos os IDs |

### 4.5 Auditoria da API

Toda requisição à API pública é logada:

| Campo | Descrição |
|-------|-----------|
| `api_key_id` | Qual key foi usada |
| `tenant_id` | Qual tenant foi acessado |
| `method` | GET, POST, PATCH, DELETE |
| `endpoint` | Rota acessada |
| `status_code` | Código HTTP da resposta |
| `ip_address` | IP de origem |
| `user_agent` | User-Agent do cliente |
| `request_id` | Identificador único do request |
| `response_time_ms` | Tempo de resposta em milissegundos |
| `timestamp` | Data/hora UTC |

> **Retenção:** Logs de API retidos por **90 dias**.

---

## 5. Segurança dos Webhooks

### 5.1 Assinatura HMAC

Toda delivery de webhook é assinada com HMAC-SHA256 usando o `secret` definido no registro do webhook:

```
X-Signature-256: sha256={hmac_do_body}
X-Delivery-Id: {uuid_unico_da_delivery}
X-Event: order.created
```

### 5.2 Proteção contra Replay

| Controle | Implementação |
|----------|---------------|
| **Delivery ID** | Cada delivery tem UUID único — receptor pode deduplificar |
| **Timestamp** | Incluso no payload — receptor pode rejeitar se muito antigo |
| **HTTPS** | Webhooks enviados apenas para URLs HTTPS |

### 5.3 Retry e Timeout

| Aspecto | Valor |
|---------|-------|
| Timeout por delivery | 10 segundos |
| Retries | Até 5 tentativas |
| Backoff | 1s → 30s → 5min → 30min → 2h |
| Resposta esperada | HTTP 2xx |
| Falha persistente | Webhook desativado após 5 falhas consecutivas, alerta no painel |

---

## 6. Autenticação e Autorização

### 6.1 Autenticação de Usuários (Frontend)

- **Laravel Sanctum** para autenticação SPA (cookie-based)
- Cookies `httpOnly`, `secure`, `SameSite=Lax`
- CSRF token validado em toda requisição
- Rate limiting no login: 5 tentativas por minuto
- Logout invalida todas as sessões ativas

### 6.2 Roles (RBAC)

| Role | Permissões |
|------|-----------| 
| **admin** | Tudo: configurações do tenant, usuários, API keys, relatórios, importação |
| **gerente** | Chamados: criar, editar, atribuir, visualizar. Cadastros. Alertas em tempo real |
| **mecanico** | Chamados atribuídos: aceitar, checklist, fotos, fechar OS |
| **visualizador** | Somente leitura: dashboard, relatórios, histórico |

### 6.3 Regras de Acesso Cruzado

```
✗ Mecânico NÃO pode ver chamados de outros mecânicos
✗ Gerente NÃO pode alterar configurações do tenant
✗ Visualizador NÃO pode criar ou editar nada
✗ API Key NÃO pode acessar dados de outro tenant (RLS garante)
✗ NENHUM role/key acessa dados de outro tenant (RLS garante)
```

---

## 7. Checklist DevSecOps — Obrigatório desde o Dia Zero

| Camada | Controle | Ferramenta | Status |
|--------|----------|------------|--------|
| **API Interna** | Sanctum com tenant_id validado em todo middleware | Laravel Sanctum + `EnsureTenant` | 🔲 |
| **API Pública** | API Key com scopes, rate limit, auditoria | Middleware customizado | 🔲 |
| **Banco** | RLS obrigatório + usuário DB sem permissão de DROP | PostgreSQL nativo | 🔲 |
| **Secrets** | Zero secrets em código ou logs | Variável de ambiente / Vault | 🔲 |
| **SAST** | Análise estática a cada commit | Enlightn + Psalm + Semgrep | 🔲 |
| **Logs** | Nenhum dado PII em log — somente IDs | Política de logging no Laravel | 🔲 |
| **API Keys** | Nunca em texto plano — somente hash SHA-256 | Política de armazenamento | 🔲 |
| **Transporte** | HTTPS forçado em toda comunicação | Nginx + Let's Encrypt | 🔲 |
| **Webhooks** | Assinatura HMAC-SHA256, apenas HTTPS | Implementação custom | 🔲 |
| **Backups** | Snapshot diário com restore testado mensalmente | pg_dump + S3 / Backblaze | 🔲 |
| **Dependências** | Auditoria de pacotes no CI | `composer audit` + `npm audit` | 🔲 |

---

## 8. Proteção de Dados (LGPD)

| Requisito | Implementação |
|-----------|---------------|
| Consentimento | Termos aceitos no onboarding do tenant |
| Minimização | Apenas dados necessários para operação |
| Acesso | Usuário pode solicitar exportação de seus dados |
| Exclusão | Direito ao esquecimento implementado por tenant |
| Portabilidade | Exportação completa em CSV/JSON |
| Logs PII | Nenhum dado pessoal em logs — somente IDs referenciáveis |
| Retenção | Dados operacionais retidos por 5 anos (obrigação fiscal); dados pessoais conforme política |
| API Pública | Dados PII nunca expostos em logs de API — somente IDs |

---

## 9. Segurança da Infraestrutura

### 9.1 Servidor (Coolify)

- [ ] SSH apenas por chave (senha desabilitada)
- [ ] Firewall: apenas portas 80, 443, 22 abertas
- [ ] Atualizações automáticas de segurança do SO
- [ ] Monitoramento de uptime via healthcheck

### 9.2 Banco de Dados

- [ ] PostgreSQL **não exposto** na internet (apenas localhost / rede interna)
- [ ] Usuário da aplicação **sem permissão de DROP**
- [ ] Backups criptografados
- [ ] Restore testado mensalmente

### 9.3 Redis

- [ ] Senha configurada
- [ ] Não exposto na internet
- [ ] Dados sensíveis nunca armazenados sem expiração
- [ ] Idempotency keys com TTL de 24h

### 9.4 Backup e Disaster Recovery

| Item | Frequência | Ferramenta | Destino |
|------|-----------|------------|---------| 
| Banco (pg_dump) | Diário | Cron + script | S3 / Backblaze |
| Uploads/fotos | Diário | rsync / rclone | S3 / Backblaze |
| Configuração Coolify | Semanal | Export + git | Repositório privado |
| Teste de restore | Mensal | Manual | Ambiente de staging |

---

## 10. Resposta a Incidentes

### Classificação

| Severidade | Exemplo | Tempo de Resposta |
|-----------|---------|-------------------|
| **Crítico** | Vazamento entre tenants, API Key comprometida, acesso não autorizado via API | < 1 hora |
| **Alto** | Falha de autenticação, dados corrompidos, webhook expondo dados | < 4 horas |
| **Médio** | Feature com bug de segurança, log com PII, rate limit não funcionando | < 24 horas |
| **Baixo** | Dependência com CVE de baixo risco | < 1 semana |

### Procedimento (Crítico)

1. Isolar o sistema afetado
2. Se API Key comprometida: revogar imediatamente
3. Notificar stakeholders
4. Investigar e documentar
5. Corrigir e validar
6. Post-mortem e atualizar políticas

### Procedimento (API Key Comprometida)

1. Revogar a API Key imediatamente
2. Verificar logs: quais endpoints foram acessados
3. Notificar o tenant afetado
4. Gerar nova API Key
5. Investigar como a key foi comprometida
6. Reforçar controles (IP whitelist, rotação obrigatória)
