# ─────────────────────────────────────────
# Makefile — Elevadores SaaS
# Atalhos para operações comuns de desenvolvimento
# ─────────────────────────────────────────

.PHONY: help up down restart build logs shell-backend shell-frontend \
        migrate seed fresh test test-coverage lint format install \
        artisan tinker queue horizon

# Cores
GREEN  := \033[0;32m
YELLOW := \033[1;33m
RESET  := \033[0m

help: ## Exibe esta ajuda
	@echo ""
	@echo "$(GREEN)Elevadores SaaS — Comandos disponíveis$(RESET)"
	@echo ""
	@awk 'BEGIN {FS = ":.*##"} /^[a-zA-Z_-]+:.*##/ { printf "  $(YELLOW)%-20s$(RESET) %s\n", $$1, $$2 }' $(MAKEFILE_LIST)
	@echo ""

# ─────────────────────────────────────────
# DOCKER
# ─────────────────────────────────────────

setup: ## Configuração inicial completa (primeira vez)
	@echo "$(GREEN)► Configuração inicial...$(RESET)"
	cp -n .env.example .env || true
	cp -n backend/.env.example backend/.env || true
	$(MAKE) build
	$(MAKE) up
	$(MAKE) install
	$(MAKE) migrate
	$(MAKE) seed
	@echo "$(GREEN)✓ Ambiente pronto! Acesse http://localhost$(RESET)"

build: ## Build das imagens Docker
	docker compose build --no-cache

up: ## Sobe todos os serviços
	docker compose up -d

down: ## Para todos os serviços
	docker compose down

restart: ## Reinicia todos os serviços
	docker compose restart

ps: ## Status dos containers
	docker compose ps

logs: ## Logs de todos os serviços
	docker compose logs -f

logs-backend: ## Logs do backend
	docker compose logs -f backend

logs-queue: ## Logs da fila
	docker compose logs -f queue

logs-reverb: ## Logs do WebSocket
	docker compose logs -f reverb

# ─────────────────────────────────────────
# ACESSO AOS CONTAINERS
# ─────────────────────────────────────────

shell-backend: ## Shell no container do backend
	docker compose exec backend bash

shell-frontend: ## Shell no container do frontend
	docker compose exec frontend sh

shell-postgres: ## PSQL no banco de dados
	docker compose exec postgres psql -U elevadores -d elevadores

shell-redis: ## Redis CLI
	docker compose exec redis redis-cli -a secret

# ─────────────────────────────────────────
# BACKEND — Laravel
# ─────────────────────────────────────────

install: ## Instala dependências PHP (composer install)
	docker compose exec backend composer install

artisan: ## Executa um comando artisan. Ex: make artisan CMD="route:list"
	docker compose exec backend php artisan $(CMD)

tinker: ## Abre o Tinker (REPL do Laravel)
	docker compose exec backend php artisan tinker

migrate: ## Executa as migrations
	docker compose exec backend php artisan migrate

migrate-test: ## Executa as migrations no banco de testes
	docker compose exec backend php artisan migrate --env=testing

seed: ## Executa os seeders
	docker compose exec backend php artisan db:seed

fresh: ## Apaga tudo e recria o banco (CUIDADO!)
	docker compose exec backend php artisan migrate:fresh --seed

generate-key: ## Gera APP_KEY
	docker compose exec backend php artisan key:generate

swagger: ## Gera documentação OpenAPI
	docker compose exec backend php artisan scribe:generate

# ─────────────────────────────────────────
# TESTES
# ─────────────────────────────────────────

test: ## Executa todos os testes PHP
	docker compose exec backend php artisan test

test-coverage: ## Executa testes com cobertura de código
	docker compose exec backend php artisan test --coverage --min=80

test-unit: ## Apenas testes unitários
	docker compose exec backend php artisan test --testsuite=Unit

test-feature: ## Apenas testes de feature
	docker compose exec backend php artisan test --testsuite=Feature

test-filter: ## Executa testes filtrados. Ex: make test-filter FILTER=TenantTest
	docker compose exec backend php artisan test --filter=$(FILTER)

test-frontend: ## Executa testes do frontend (Vitest)
	docker compose exec frontend npm run test:unit

# ─────────────────────────────────────────
# QUALIDADE DE CÓDIGO
# ─────────────────────────────────────────

lint: ## Verifica estilo de código (Pint)
	docker compose exec backend vendor/bin/pint --test

format: ## Corrige estilo de código (Pint)
	docker compose exec backend vendor/bin/pint

psalm: ## Análise estática de tipos (Psalm)
	docker compose exec backend vendor/bin/psalm

enlightn: ## Análise de segurança (Enlightn)
	docker compose exec backend vendor/bin/enlightn

audit: ## Auditoria de vulnerabilidades
	docker compose exec backend composer audit
	docker compose exec frontend npm audit

lint-frontend: ## ESLint no frontend
	docker compose exec frontend npm run lint

lint-fix-frontend: ## ESLint com auto-fix no frontend
	docker compose exec frontend npm run lint:fix

# ─────────────────────────────────────────
# FILAS E WEBSOCKET
# ─────────────────────────────────────────

queue: ## Inicia worker de filas (foreground)
	docker compose exec backend php artisan queue:work

horizon: ## Abre o Horizon no browser
	@echo "$(GREEN)Horizon disponível em: http://localhost/horizon$(RESET)"

queue-flush: ## Limpa todas as filas
	docker compose exec backend php artisan queue:flush

# ─────────────────────────────────────────
# UTILITÁRIOS
# ─────────────────────────────────────────

clear: ## Limpa todos os caches do Laravel
	docker compose exec backend php artisan optimize:clear

optimize: ## Otimiza caches (para produção)
	docker compose exec backend php artisan optimize
