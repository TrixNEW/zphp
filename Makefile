PKG_CONFIG_PATH := /opt/homebrew/opt/mysql-client/lib/pkgconfig:/opt/homebrew/opt/libpq/lib/pkgconfig:/opt/homebrew/opt/openssl@3/lib/pkgconfig:/opt/homebrew/opt/curl/lib/pkgconfig:/opt/homebrew/opt/icu4c@77/lib/pkgconfig:/opt/homebrew/opt/icu4c/lib/pkgconfig:/opt/homebrew/opt/gmp/lib/pkgconfig:/opt/homebrew/opt/gd/lib/pkgconfig:/opt/homebrew/opt/libsodium/lib/pkgconfig:/opt/homebrew/opt/openldap/lib/pkgconfig:$(PKG_CONFIG_PATH)
export PKG_CONFIG_PATH

.PHONY: build release test compat pdo examples bench bench-macro laravel symfony all-tests check docs clean help
build: ## Build the debug binary
	zig build

release: ## Build the optimized binary
	zig build -Doptimize=ReleaseFast

test: ## Run Zig unit tests
	zig build test

compat: build ## Run PHP compatibility tests (requires PHP 8.4)
	./tests/run

pdo: build ## Run PDO driver tests
	./tests/pdo_test

examples: build ## Run example project tests (requires PHP 8.4)
	./tests/examples_test

bench: ## Run runtime benchmarks (ReleaseFast)
	zig build -Doptimize=ReleaseFast
	./benchmarks/runtime/run

bench-macro: ## Track real-app perf vs php (WordPress + Laravel harnesses, ReleaseFast)
	zig build -Doptimize=ReleaseFast
	./benchmarks/macro/run

laravel: build ## Run Laravel compatibility tests (requires PHP 8.4 + composer)
	./tests/laravel/run

symfony: build ## Run Symfony component and serve compatibility tests (requires PHP 8.4 + composer)
	./tests/symfony/run
	./tests/symfony/serve_run

all-tests: test compat examples laravel ## Run all tests

check: test compat examples ## Run the standard local verification suite

docs: ## Serve docs locally with live reload
	mdbook serve docs

clean: ## Clean build artifacts
	rm -rf zig-out .zig-cache

help: ## Show help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(firstword $(MAKEFILE_LIST)) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-20s\033[0m %s\n", $$1, $$2}'
