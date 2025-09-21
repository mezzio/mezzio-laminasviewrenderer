# Run `make` (no arguments) to get a short description of what is available
# within this `Makefile`.

MDLINT_FILE = https://raw.githubusercontent.com/laminas/laminas-continuous-integration-action/refs/heads/1.43.x/setup/markdownlint/markdownlint.json

help: ## shows this help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_\-\.]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
.PHONY: help

docs-lint: .markdownlint.json ## Lint documentation
	docker run -it -w /app -v ${PWD}:/app --rm davidanson/markdownlint-cli2 "README.md"
.PHONY: docs-lint

.markdownlint.json: ## Fetch the most recent settings for Markdown lint
	curl -o .markdownlint.json ${MDLINT_FILE}

install: install-tools ## Install PHP dependencies
	composer install
.PHONY: install

install-tools: ## Install standalone dev tools
	cd tools/crc && composer install
.PHONY: install-tools

update: ## Update PHP dependencies
	composer update
.PHONY: update

bump: bump-tools ## Bump dev dependencies and update
	composer update && composer bump -D && composer update
.PHONY: bump

bump-tools: ## Bump and update standalone dev tools
	cd tools/crc && composer update && composer bump -D && composer update
.PHONY: bump-tools

clean: ## Clear out caches and documentation assets
	rm -rf .phpunit.cache
	rm -f .phpcs-cache
	vendor/bin/psalm --clear-cache
	rm -f .markdownlint.json
.PHONY: clean

sa: ## Run static analysis checks
	vendor/bin/psalm --no-cache
.PHONY: sa

set-baseline: ## Expand the Psalm baseline with current issues
	vendor/bin/psalm --no-cache --set-baseline=psalm-baseline.xml
.PHONY: set-baseline

update-baseline: ## Remove resolved issues from the baseline
	vendor/bin/psalm --no-cache --update-baseline
.PHONY: update-baseline

cs: ## Run coding standards checks
	vendor/bin/phpcs
.PHONY: cs

test: ## Run unit tests
	vendor/bin/phpunit
.PHONY: test

composer-validate: ## Validate composer.json and lock
	composer validate --strict
.PHONY: composer-validate

composer-require-checker: ## Check for symbols from un-declared dependencies
	tools/crc/vendor/bin/composer-require-checker check --config-file=tools/crc/config.json
.PHONY: composer-require-checker

qa: composer-validate cs sa test composer-require-checker docs-lint ## Run all QA Checks
.PHONY: qa
