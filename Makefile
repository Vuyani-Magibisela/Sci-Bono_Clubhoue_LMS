# Sci-Bono LMS Makefile
# Phase 7: API Development & Testing
# Provides easy-to-use commands for development, testing, and deployment

.PHONY: help install test test-unit test-api test-models test-coverage test-verbose setup-test-env clean lint deploy-staging deploy-prod

# Colors for output
GREEN = \033[0;32m
YELLOW = \033[1;33m
RED = \033[0;31m
BLUE = \033[0;34m
NC = \033[0m # No Color

# Configuration
PROJECT_NAME = Sci-Bono LMS
PHP = php
TEST_RUNNER = tests/run-tests.php
TEST_CONFIG = tests/config/test-config.json

# Default target
help: ## Show this help message
	@echo "$(BLUE)╔══════════════════════════════════════════════════════════════════════════════╗$(NC)"
	@echo "$(BLUE)║                            $(PROJECT_NAME) - Development Tasks                     ║$(NC)"
	@echo "$(BLUE)║                       Phase 7: API Development & Testing                    ║$(NC)"
	@echo "$(BLUE)╚══════════════════════════════════════════════════════════════════════════════╝$(NC)"
	@echo ""
	@echo "$(GREEN)Available commands:$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(YELLOW)%-20s$(NC) %s\n", $$1, $$2}'
	@echo ""
	@echo "$(GREEN)Examples:$(NC)"
	@echo "  make install          # Set up the development environment"
	@echo "  make test             # Run all tests"
	@echo "  make test-coverage    # Run tests with code coverage"
	@echo "  make lint             # Check code quality"
	@echo ""

# Development setup
install: ## Install dependencies and set up development environment
	@echo "$(GREEN)[INFO]$(NC) Setting up development environment..."
	@if [ -f composer.json ]; then \
		echo "$(YELLOW)[INFO]$(NC) Installing PHP dependencies..."; \
		composer install --prefer-dist; \
	else \
		echo "$(YELLOW)[WARNING]$(NC) No composer.json found, skipping PHP dependencies"; \
	fi
	@echo "$(GREEN)[INFO]$(NC) Setting up test environment..."
	@chmod +x tests/setup-test-environment.sh
	@./tests/setup-test-environment.sh
	@echo "$(GREEN)[SUCCESS]$(NC) Development environment ready!"

# Test commands
test: ## Run all tests
	@echo "$(GREEN)[INFO]$(NC) Running all tests..."
	@$(PHP) $(TEST_RUNNER) --suite=all --verbose

test-unit: ## Run unit tests only
	@echo "$(GREEN)[INFO]$(NC) Running unit tests..."
	@$(PHP) $(TEST_RUNNER) --suite=unit --verbose

test-models: ## Run model tests only
	@echo "$(GREEN)[INFO]$(NC) Running model tests..."
	@$(PHP) $(TEST_RUNNER) --suite=models --verbose

test-api: ## Run API tests only
	@echo "$(GREEN)[INFO]$(NC) Running API tests..."
	@$(PHP) $(TEST_RUNNER) --suite=api --verbose

test-integration: ## Run integration tests only
	@echo "$(GREEN)[INFO]$(NC) Running integration tests..."
	@$(PHP) $(TEST_RUNNER) --suite=integration --verbose

test-coverage: ## Run tests with code coverage report
	@echo "$(GREEN)[INFO]$(NC) Running tests with code coverage..."
	@$(PHP) $(TEST_RUNNER) --suite=all --coverage --verbose
	@echo "$(GREEN)[INFO]$(NC) Coverage report generated in tests/coverage/"

test-verbose: ## Run tests with detailed verbose output
	@echo "$(GREEN)[INFO]$(NC) Running tests with verbose output..."
	@$(PHP) $(TEST_RUNNER) --suite=all --verbose --stop-on-failure

test-quick: ## Run a quick smoke test
	@echo "$(GREEN)[INFO]$(NC) Running quick smoke test..."
	@$(PHP) $(TEST_RUNNER) --suite=models --filter=testCreateUser --verbose

test-performance: ## Run performance tests
	@echo "$(GREEN)[INFO]$(NC) Running performance tests..."
	@$(PHP) $(TEST_RUNNER) --suite=performance --verbose

# Test environment management
setup-test-env: ## Set up test environment
	@echo "$(GREEN)[INFO]$(NC) Setting up test environment..."
	@chmod +x tests/setup-test-environment.sh
	@./tests/setup-test-environment.sh

clean-test-env: ## Clean up test environment
	@echo "$(GREEN)[INFO]$(NC) Cleaning up test environment..."
	@rm -rf tests/reports/* tests/coverage/* tests/logs/* tests/temp/*
	@echo "$(GREEN)[SUCCESS]$(NC) Test environment cleaned!"

reset-test-db: ## Reset test database
	@echo "$(GREEN)[INFO]$(NC) Resetting test database..."
	@mysql -u root -e "DROP DATABASE IF EXISTS sci_bono_lms_test;"
	@mysql -u root -e "CREATE DATABASE sci_bono_lms_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	@echo "$(GREEN)[SUCCESS]$(NC) Test database reset!"

# Code quality
lint: ## Run code quality checks
	@echo "$(GREEN)[INFO]$(NC) Running code quality checks..."
	@echo "$(YELLOW)[CHECK]$(NC) PHP syntax check..."
	@find app/ -name "*.php" -exec $(PHP) -l {} \; | grep -v "No syntax errors detected" || true
	@echo "$(YELLOW)[CHECK]$(NC) Looking for potential issues..."
	@grep -r "TODO\|FIXME\|XXX" app/ --include="*.php" | head -10 || echo "No TODO/FIXME comments found"
	@echo "$(YELLOW)[CHECK]$(NC) Checking for hardcoded passwords..."
	@grep -r "password.*=" app/ --include="*.php" | grep -v "\$$_POST\|hash\|password_" | head -5 || echo "No hardcoded passwords found"
	@echo "$(GREEN)[SUCCESS]$(NC) Code quality check completed!"

format: ## Format code (if formatter available)
	@echo "$(YELLOW)[INFO]$(NC) Code formatting not configured yet"
	@echo "$(YELLOW)[INFO]$(NC) Consider setting up PHP-CS-Fixer or similar tool"

# Documentation
docs: ## Generate documentation
	@echo "$(GREEN)[INFO]$(NC) Generating documentation..."
	@echo "$(YELLOW)[TODO]$(NC) Documentation generation not implemented yet"

api-docs: ## Generate API documentation
	@echo "$(GREEN)[INFO]$(NC) Generating API documentation..."
	@echo "$(YELLOW)[TODO]$(NC) API documentation generation not implemented yet"

# Database operations
db-migrate: ## Run database migrations
	@echo "$(GREEN)[INFO]$(NC) Running database migrations..."
	@if [ -d "Database" ]; then \
		for file in Database/*.sql; do \
			echo "$(YELLOW)[INFO]$(NC) Executing $$file..."; \
			mysql -u root sci_bono_lms < "$$file"; \
		done; \
	else \
		echo "$(YELLOW)[WARNING]$(NC) No Database directory found"; \
	fi

db-seed: ## Seed database with test data
	@echo "$(GREEN)[INFO]$(NC) Seeding database..."
	@echo "$(YELLOW)[TODO]$(NC) Database seeding not implemented yet"

# Development server
serve: ## Start development server
	@echo "$(GREEN)[INFO]$(NC) Starting PHP development server..."
	@echo "$(YELLOW)[INFO]$(NC) Server will be available at http://localhost:8000"
	@echo "$(YELLOW)[INFO]$(NC) Press Ctrl+C to stop"
	@$(PHP) -S localhost:8000 -t .

# CI/CD operations
ci-test: ## Run tests in CI environment
	@echo "$(GREEN)[INFO]$(NC) Running CI tests..."
	@export APP_ENV=testing && \
	$(PHP) $(TEST_RUNNER) --suite=all --coverage --verbose --stop-on-failure --output=tests/reports/ci-results.json

ci-security: ## Run security checks
	@echo "$(GREEN)[INFO]$(NC) Running security checks..."
	@if [ -f composer.lock ]; then \
		composer audit || echo "Composer audit not available"; \
	fi
	@echo "$(YELLOW)[CHECK]$(NC) Checking for potential security issues..."
	@grep -r "eval\|exec\|system\|shell_exec" app/ --include="*.php" | head -5 || echo "No dangerous functions found"

ci-build: ## Build for CI/CD
	@echo "$(GREEN)[INFO]$(NC) Building for CI/CD..."
	@mkdir -p build
	@tar -czf build/sci-bono-lms-$(shell date +%Y%m%d-%H%M%S).tar.gz \
		--exclude='.git' \
		--exclude='tests' \
		--exclude='node_modules' \
		--exclude='build' \
		.
	@echo "$(GREEN)[SUCCESS]$(NC) Build package created in build/"

# Deployment
deploy-staging: ## Deploy to staging environment
	@echo "$(GREEN)[INFO]$(NC) Deploying to staging..."
	@echo "$(YELLOW)[TODO]$(NC) Staging deployment not configured yet"
	@echo "$(YELLOW)[INFO]$(NC) This would typically involve:"
	@echo "  - Running tests"
	@echo "  - Building application"
	@echo "  - Uploading to staging server"
	@echo "  - Running database migrations"
	@echo "  - Restarting services"

deploy-prod: ## Deploy to production environment
	@echo "$(GREEN)[INFO]$(NC) Deploying to production..."
	@echo "$(RED)[WARNING]$(NC) Production deployment should be done carefully!"
	@echo "$(YELLOW)[TODO]$(NC) Production deployment not configured yet"

# Monitoring
logs: ## View application logs
	@echo "$(GREEN)[INFO]$(NC) Viewing recent logs..."
	@if [ -d "logs" ]; then \
		tail -f logs/*.log; \
	elif [ -d "tests/logs" ]; then \
		tail -f tests/logs/*.log; \
	else \
		echo "$(YELLOW)[WARNING]$(NC) No log directory found"; \
	fi

status: ## Show system status
	@echo "$(GREEN)[INFO]$(NC) System Status:"
	@echo "  PHP Version: $(shell $(PHP) -r 'echo PHP_VERSION;')"
	@echo "  MySQL Status: $(shell mysql --version 2>/dev/null | head -1 || echo 'Not available')"
	@echo "  Test Database: $(shell mysql -u root -e 'SELECT "Available" as status;' sci_bono_lms_test 2>/dev/null || echo 'Not available')"
	@echo "  Disk Usage: $(shell df -h . | tail -1 | awk '{print $$5}') of $(shell df -h . | tail -1 | awk '{print $$2}')"
	@echo "  Memory Usage: $(shell free -m 2>/dev/null | grep Mem | awk '{printf "%.1f%%", $$3/$$2 * 100.0}' || echo 'N/A')"

# Cleanup
clean: ## Clean up temporary files and caches
	@echo "$(GREEN)[INFO]$(NC) Cleaning up..."
	@rm -rf tests/reports/* tests/coverage/* tests/logs/* tests/temp/*
	@rm -rf build/*
	@find . -name "*.log" -type f -delete 2>/dev/null || true
	@find . -name ".DS_Store" -type f -delete 2>/dev/null || true
	@echo "$(GREEN)[SUCCESS]$(NC) Cleanup completed!"

# Development utilities
watch-tests: ## Watch for file changes and run tests
	@echo "$(GREEN)[INFO]$(NC) Watching for file changes..."
	@echo "$(YELLOW)[TODO]$(NC) File watching not implemented yet"
	@echo "$(YELLOW)[INFO]$(NC) Consider using entr or similar tool:"
	@echo "  find app/ -name '*.php' | entr make test-quick"

debug-test: ## Run tests with debugging enabled
	@echo "$(GREEN)[INFO]$(NC) Running tests with debugging..."
	@export XDEBUG_MODE=debug && $(PHP) $(TEST_RUNNER) --suite=models --verbose --stop-on-failure

benchmark: ## Run performance benchmarks
	@echo "$(GREEN)[INFO]$(NC) Running performance benchmarks..."
	@time $(PHP) $(TEST_RUNNER) --suite=performance --verbose

# Version information
version: ## Show version information
	@echo "$(BLUE)$(PROJECT_NAME) - Phase 7: API Development & Testing$(NC)"
	@echo "Version: 1.0.0-alpha"
	@echo "PHP: $(shell $(PHP) -v | head -1)"
	@echo "MySQL: $(shell mysql --version 2>/dev/null || echo 'Not available')"
	@if [ -f composer.json ]; then \
		echo "Composer: $(shell composer --version 2>/dev/null || echo 'Not available')"; \
	fi

# Advanced testing
test-parallel: ## Run tests in parallel (experimental)
	@echo "$(GREEN)[INFO]$(NC) Running tests in parallel..."
	@echo "$(YELLOW)[WARNING]$(NC) Parallel testing is experimental"
	@$(PHP) $(TEST_RUNNER) --parallel --verbose

test-memory: ## Run tests with memory profiling
	@echo "$(GREEN)[INFO]$(NC) Running tests with memory profiling..."
	@$(PHP) -d memory_limit=64M $(TEST_RUNNER) --verbose

test-stress: ## Run stress tests
	@echo "$(GREEN)[INFO]$(NC) Running stress tests..."
	@for i in {1..5}; do \
		echo "$(YELLOW)[INFO]$(NC) Stress test iteration $$i/5"; \
		$(PHP) $(TEST_RUNNER) --suite=models --verbose; \
	done

# Report generation
report: ## Generate comprehensive test report
	@echo "$(GREEN)[INFO]$(NC) Generating comprehensive test report..."
	@$(PHP) $(TEST_RUNNER) --suite=all --coverage --verbose --output=tests/reports/comprehensive-report.json
	@echo "$(GREEN)[SUCCESS]$(NC) Report generated: tests/reports/comprehensive-report.json"

# Help for specific commands
help-test: ## Show detailed help for testing commands
	@echo "$(BLUE)Testing Commands Help$(NC)"
	@echo ""
	@echo "$(YELLOW)Basic Testing:$(NC)"
	@echo "  make test           - Run all test suites"
	@echo "  make test-unit      - Run unit tests only"
	@echo "  make test-api       - Run API tests only" 
	@echo "  make test-models    - Run model tests only"
	@echo ""
	@echo "$(YELLOW)Advanced Testing:$(NC)"
	@echo "  make test-coverage  - Run with code coverage"
	@echo "  make test-verbose   - Run with detailed output"
	@echo "  make test-quick     - Quick smoke test"
	@echo "  make test-stress    - Stress testing"
	@echo ""
	@echo "$(YELLOW)Test Environment:$(NC)"
	@echo "  make setup-test-env - Set up test environment"
	@echo "  make clean-test-env - Clean test environment"
	@echo "  make reset-test-db  - Reset test database"