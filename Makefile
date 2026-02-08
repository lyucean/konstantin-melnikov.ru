.PHONY: up down deploy hosts

# Локальная разработка: http://konstantin-melnikov.loc:8080
up:
	@test -f .env || cp .env.example .env
	docker compose --profile dev up -d

down:
	docker compose --profile dev down

# Добавить konstantin-melnikov.loc в /etc/hosts (запросит пароль)
hosts:
	@grep -q 'konstantin-melnikov.loc' /etc/hosts && echo "Уже есть в /etc/hosts" || (echo "127.0.0.1 konstantin-melnikov.loc" | sudo tee -a /etc/hosts)

# Деплой на сервер (нужны DEPLOY_HOST и SSH-ключ)
# Пример: make deploy DEPLOY_HOST=root@89.111.131.21
DEPLOY_PATH ?= /var/www/konstantin-melnikov.ru
deploy:
	@test -n "$(DEPLOY_HOST)" || (echo "Укажите DEPLOY_HOST=user@host"; exit 1)
	rsync -avz --exclude .git --exclude .env ./ $(DEPLOY_HOST):$(DEPLOY_PATH)/
	ssh $(DEPLOY_HOST) "cd $(DEPLOY_PATH) && docker compose --profile prod up -d --build"
