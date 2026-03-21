# Docker Compose helpers — see `make help`
DOCKER_COMPOSE ?= docker compose

.PHONY: help up start stop down restart teardown \
	shell shell-php shell-db shell-nginx \
	php php-run

help:
	@echo ""
	@echo "  make up / make start   Start all services in the background"
	@echo "  make stop              Stop all services (containers kept)"
	@echo "  make down              Stop and remove containers (networks; volumes kept)"
	@echo "  make teardown CONFIRM=yes  Nuke: compose down -v, rm vendor + composer.lock + symfony.lock"
	@echo "  make restart           Restart all services"
	@echo ""
	@echo "Shell into a service:"
	@echo "  make shell-php"
	@echo "  make shell-db"
	@echo "  make shell-nginx"



up start:
	$(DOCKER_COMPOSE) up -d

stop:
	$(DOCKER_COMPOSE) stop

down:
	$(DOCKER_COMPOSE) down

teardown:
ifneq ($(CONFIRM),yes)
	@echo "Refusing: this removes Docker stack + DB volume + vendor/ + composer.lock + symfony.lock"
	@echo "Run: make teardown CONFIRM=yes"
	@exit 1
else
	$(DOCKER_COMPOSE) down -v --remove-orphans
	rm -rf vendor
	rm -f composer.lock symfony.lock
	@echo "Teardown complete."
endif

restart:
	$(DOCKER_COMPOSE) restart



shell-php:
	$(DOCKER_COMPOSE) exec php bash

shell-db:
	$(DOCKER_COMPOSE) exec db bash

shell-nginx:
	$(DOCKER_COMPOSE) exec nginx sh
