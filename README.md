# konstantin-melnikov.ru

Лендинг по Битрикс24 (услуги, эксперт, форма заявки в Telegram). HTML + Bootstrap 5 + PHP.

Фото эксперта: положи в `public/img/author.jpg` — иначе показывается заглушка.

## Локально

```bash
cp .env.example .env
# Заполни TELEGRAM_BOT_TOKEN и TELEGRAM_CHAT_ID в .env (для формы)
make up
```

Сайт: http://konstantin-melnikov.loc:8080

Добавь в `/etc/hosts`: `127.0.0.1 konstantin-melnikov.loc`

```bash
make down   # остановить
```

## Деплой на сервер

Папка `/var/www/konstantin-melnikov.ru` может не существовать — при первом деплое через GitHub Actions она создаётся и репозиторий клонируется автоматически.

Один раз на сервере создай `.env` (если ещё нет):

```bash
ssh root@89.111.131.21
mkdir -p /var/www/konstantin-melnikov.ru
# Если папка пустая, при первом деплое через GitHub Actions сюда склонируется репо
# Либо вручную: git clone <URL> . && cp .env.example .env
nano /var/www/konstantin-melnikov.ru/.env   # TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID
```

Домен должен указывать на сервер; Traefik уже стоит и возьмёт конфиг из labels (HTTPS, Let's Encrypt).

## GitHub Actions

При push в `main` деплой идёт автоматически: создаётся папка (если нет), при первом заходе — клонирование репо, затем `git pull` и `docker compose --profile prod up -d --build`.

Секреты в репозитории (Settings → Secrets and variables → Actions):

- `DEPLOY_HOST` — IP или хост сервера (например `89.111.131.21`)
- `DEPLOY_USER` — пользователь SSH (например `root`)
- `SSH_PRIVATE_KEY` — приватный ключ для доступа к серверу (содержимое, без пароля удобнее)

Для приватного репозитория: на сервере должен быть настроен доступ к GitHub (deploy key в `~/.ssh` или добавь публичный ключ сервера в GitHub как Deploy key репозитория).

Рекомендуется отдельный SSH-ключ для деплоя в `~/.ssh/authorized_keys` на сервере.

## Ручной деплой

```bash
make deploy DEPLOY_HOST=root@89.111.131.21
```

(На машине должен быть доступ по SSH без пароля, например по ключу.)
