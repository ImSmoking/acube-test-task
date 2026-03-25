
DOCKER_COMPOSE ?= docker compose
TESTDOX_FLAG = $(if $(filter 1 yes true,$(TESTDOX)),--testdox,)
PHPUNIT = $(DOCKER_COMPOSE) exec php php bin/phpunit -c phpunit.dist.xml $(TESTDOX_FLAG)

.PHONY: help up start stop down restart teardown \
	shell shell-php shell-db shell-nginx \
	php php-run \
	test test-functional test-file

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
	@echo ""
	@echo "Tests (PHPUnit, inside php container; add TESTDOX=1 for --testdox):"
	@echo "  make test [TESTDOX=1]           All tests under tests/"
	@echo "  make test-functional [TESTDOX=1]  Only tests/functional/"
	@echo "  make test-file FILE=… [TESTDOX=1] One file, e.g. FILE=tests/functional/FooTest.php"



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

test:
	$(PHPUNIT) tests

test-functional:
	$(PHPUNIT) tests/functional

test-file:
	@test -n "$(FILE)" || (echo "Usage: make test-file FILE=tests/functional/MyTest.php"; exit 1)
	$(PHPUNIT) $(FILE)



shell-php:
	$(DOCKER_COMPOSE) exec php bash

shell-db:
	$(DOCKER_COMPOSE) exec db bash

shell-nginx:
	$(DOCKER_COMPOSE) exec nginx sh
