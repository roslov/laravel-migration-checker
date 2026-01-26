.DEFAULT_GOAL := help

# Current user ID and group ID except MacOS where it conflicts with Docker abilities
ifeq ($(shell uname), Darwin)
    export UID=1000
    export GID=1000
else
    export UID=$(shell id -u)
    export GID=$(shell id -g)
endif

# Default command for `make run`
COMMAND ?= php -v

# Docker command to run commands in a container
DOCKER_RUN = docker run --rm -v $$PWD:/app -w /app -it php:8.1

# Command to install Composer
COMPOSER_INSTALL = apt update && apt install -y unzip git && git config --global --add safe.directory /app && php -r "copy(\"https://getcomposer.org/installer\", \"composer-setup.php\");" && php composer-setup.php && php -r "unlink(\"composer-setup.php\");" && mv composer.phar /usr/local/bin/composer

init: composer-update ## Initialize the project

shell: ## Get into container shell
	$(DOCKER_RUN) bash

run: ## Run a command in container
	$(DOCKER_RUN) $(COMMAND)

composer-install: ## Run `composer install`
	$(DOCKER_RUN) bash -c '$(COMPOSER_INSTALL) && composer install'

composer-update: ## Run `composer update`
	$(DOCKER_RUN) bash -c '$(COMPOSER_INSTALL) && composer update'

test: ## Run all tests
	$(DOCKER_RUN) vendor/bin/phpunit

validate: syntax phpcs phpstan rector ## Do all validations (coding style, PHP syntax, static analysis, etc.)

phpcs: ## Validate the coding style
	$(DOCKER_RUN) vendor/bin/phpcs \
		--extensions=php \
		--colors \
		--standard=PSR12Ext \
		--runtime-set php_version 80100 \
		--ignore=vendor/* \
		-p \
		-s \
		.

phpcbf: ## Fix the coding style
	$(DOCKER_RUN) vendor/bin/phpcbf \
		--extensions=php \
		--colors \
		--standard=PSR12Ext \
		--runtime-set php_version 80100 \
		--ignore=vendor/* \
		-p \
		.

syntax: ## Validate PHP syntax
	$(DOCKER_RUN) vendor/bin/parallel-lint --colors src tests

phpstan: ## Do static analysis with PHPStan
	$(DOCKER_RUN) vendor/bin/phpstan analyse --memory-limit=256M .

rector: ## Do code analysis with Rector
	$(DOCKER_RUN) vendor/bin/rector --dry-run

# Output the help for each task, see https://marmelab.com/blog/2016/02/29/auto-documented-makefile.html
help: ## This help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
