# KaririCode Dotenv — Makefile
# Uses global kcode.phar (kariricode-devkit) for all dev tooling

PHPUNIT    := phpunit
PHPSTAN    := phpstan
CS_FIXER   := php-cs-fixer

# Colors
GREEN  := \033[0;32m
YELLOW := \033[1;33m
NC     := \033[0m

## help: Show available commands
help:
	@echo "$(GREEN)kariricode/dotenv — available commands:$(NC)"
	@sed -n 's/^##//p' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ": "}; {printf "$(YELLOW)%-20s$(NC) %s\n", $$1, $$2}'

## test: Run PHPUnit test suite
test:
	@echo "$(GREEN)Running tests...$(NC)"
	@$(PHPUNIT) --testdox --colors=always
	@echo "$(GREEN)Tests completed!$(NC)"

## test-unit: Run Unit tests only
test-unit:
	@$(PHPUNIT) --testdox --colors=always --testsuite Unit

## test-integration: Run Integration tests only
test-integration:
	@$(PHPUNIT) --testdox --colors=always --testsuite Integration

## analyse: Run PHPStan static analysis
analyse:
	@echo "$(GREEN)Running PHPStan...$(NC)"
	@$(PHPSTAN) analyse

## cs-check: Check code style (dry-run)
cs-check:
	@echo "$(GREEN)Checking code style...$(NC)"
	@$(CS_FIXER) fix --dry-run --diff

## cs-fix: Fix code style
cs-fix:
	@echo "$(GREEN)Fixing code style...$(NC)"
	@$(CS_FIXER) fix

## quality: Run all quality checks (cs-check + analyse + test)
quality: cs-check analyse test
	@echo "$(GREEN)All quality checks passed!$(NC)"

## composer-install: Install Composer dependencies
composer-install:
	@composer install --no-interaction

## composer-update: Update Composer dependencies
composer-update:
	@composer update --no-interaction

.PHONY: help test test-unit test-integration analyse cs-check cs-fix quality composer-install composer-update
