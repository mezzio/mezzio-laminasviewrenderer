# Minimum version of PHP supported by this project
PHP_VERSION ?= 8.3
PHP_EXTENSIONS ?= mbstring json xdebug
DOCKER_IMAGE_NAME = mezzio/laminas-view

# Includes _everything_ supplied by the internal tooling lib
include vendor/laminas/internal-tooling/Makefile

install-tools: ## Install standalone dev tools
	cd tools/crc && composer install
.PHONY: install-tools

bump-tools: ## Bump and update standalone dev tools
	cd tools/crc && composer update && composer bump -D && composer update
.PHONY: bump-tools

composer-require-checker: ## Check for symbols from un-declared dependencies
	tools/crc/vendor/bin/composer-require-checker check --config-file=tools/crc/config.json
.PHONY: composer-require-checker
