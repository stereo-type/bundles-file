# Подключаем переменные из .env
include .env
export

.PHONY: clean
clean: cache-clear fix stan

.PHONY: cache-clear
cache-clear: ## Отчистка кешей
	@composer dump-autoload
.PHONY: fix
fix: ## Отчистка кешей
	@composer fix
.PHONY: stan
stan: ## Отчистка кешей
	@composer stan
