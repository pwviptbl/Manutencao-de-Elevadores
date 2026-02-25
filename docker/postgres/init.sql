-- ─────────────────────────────────────────
-- Inicialização do banco PostgreSQL 16
-- Executado na primeira criação do container
-- ─────────────────────────────────────────

-- Habilitar extensões necessárias
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "unaccent";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";  -- busca por trigrama (useful para busca de texto)

-- ─────────────────────────────────────────
-- Função auxiliar para RLS
-- Retorna o tenant_id da sessão atual
-- ─────────────────────────────────────────
CREATE OR REPLACE FUNCTION current_tenant_id()
RETURNS UUID AS $$
BEGIN
    RETURN current_setting('app.tenant_id', true)::UUID;
EXCEPTION
    WHEN others THEN
        RETURN NULL;
END;
$$ LANGUAGE plpgsql STABLE;

-- ─────────────────────────────────────────
-- Role de aplicação (sem permissão de DROP)
-- ─────────────────────────────────────────
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = 'elevadores_app') THEN
        CREATE ROLE elevadores_app LOGIN PASSWORD 'secret';
    END IF;
END
$$;

GRANT CONNECT ON DATABASE elevadores TO elevadores_app;
GRANT USAGE ON SCHEMA public TO elevadores_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public
    GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO elevadores_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public
    GRANT USAGE, SELECT ON SEQUENCES TO elevadores_app;

-- ─────────────────────────────────────────
-- Banco de testes separado
-- ─────────────────────────────────────────
SELECT 'CREATE DATABASE elevadores_test'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'elevadores_test')\gexec

\c elevadores_test;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "unaccent";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
