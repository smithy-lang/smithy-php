PHP ?= php
COMPOSER ?= composer
BENCHMARK_PHP ?= $(PHP) -d opcache.enable_cli=1 -d opcache.jit=tracing -d opcache.jit_buffer_size=128M

.DEFAULT_GOAL := help

.PHONY: help install autoload test lint static-analysis check codegen codegen-output codegen-output-check license-check package-manifests-check composer-validate clean benchmark benchmark-generate benchmark-serde benchmark-client benchmark-update-results benchmark-clean
.NOTPARALLEL: check benchmark

help: ## List available targets
	@awk 'BEGIN {FS = ":.*## "}; /^[a-zA-Z0-9_-]+:.*## / {printf "  %-24s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install Composer dependencies
	$(COMPOSER) install $(ARGS)

autoload: ## Regenerate Composer autoload files
	$(COMPOSER) dump-autoload $(ARGS)

test: ## Run the test suite
	$(COMPOSER) test $(if $(ARGS),-- $(ARGS))

lint: ## Check PHP syntax
	$(PHP) tools/lint.php src tests tools benchmarks codegen-output bin/smithy-php-codegen

static-analysis: ## Run PHPStan
	$(COMPOSER) static-analysis $(if $(ARGS),-- $(ARGS))

check: ## Run checks used by CI
	$(MAKE) lint
	$(MAKE) static-analysis ARGS=
	$(MAKE) test ARGS=
	$(MAKE) codegen-output-check ARGS=
	$(MAKE) license-check
	$(MAKE) package-manifests-check
	$(MAKE) composer-validate

codegen: ## Run the code generator; pass options with ARGS
	$(COMPOSER) codegen $(if $(ARGS),-- $(ARGS))

codegen-output: ## Regenerate checked-in codegen output
	$(COMPOSER) codegen-output $(if $(ARGS),-- $(ARGS))

codegen-output-check: ## Verify checked-in codegen output is current
	$(COMPOSER) codegen-output:check $(if $(ARGS),-- $(ARGS))

license-check: ## Check source license headers
	$(PHP) tools/check-license-headers.php

package-manifests-check: ## Check package manifests and license files against the root
	$(PHP) tools/package-manifests/check.php

composer-validate: ## Validate the root and package manifests with Composer
	$(COMPOSER) validate --strict --no-check-lock
	@for manifest in src/*/composer.json; do \
		$(COMPOSER) validate --strict "$$manifest" || exit 1; \
	done

clean: ## Remove generated build and test artifacts
	rm -rf build .phpunit.cache

benchmark: benchmark-serde benchmark-client ## Run all benchmarks

benchmark-generate: ## Generate benchmark clients and shapes
	$(PHP) benchmarks/serde/generate.php

benchmark-serde: benchmark-generate ## Run protocol serialization benchmarks
	$(BENCHMARK_PHP) benchmarks/serde/run.php

benchmark-client: benchmark-generate ## Run generated client benchmarks
	$(BENCHMARK_PHP) benchmarks/client/run.php

benchmark-update-results: benchmark-generate ## Record benchmark results in the README
	$(BENCHMARK_PHP) benchmarks/serde/run.php --update-readme
	$(BENCHMARK_PHP) benchmarks/client/run.php --update-readme

benchmark-clean: ## Remove generated benchmark artifacts
	$(PHP) benchmarks/serde/generate.php --clean
