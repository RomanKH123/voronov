# Telegram через Cloudflare Workers

`worker.js` — готовый JS-скрипт для облачного Worker. VPS и PHP на промежуточном сервере не нужны.

```
Сайт → Worker /api → Telegram
Telegram → Worker /webhook → сайт /api/telegram-webhook.php
```

## Размещение через Cloudflare

1. В аккаунте Cloudflare откройте Workers & Pages, создайте Worker и вставьте содержимое `worker.js` в редактор. Опубликуйте его.
2. В настройках Worker добавьте три переменные типа **Secret**, скопировав соответствующие значения из приватного `.env` сайта: `TELEGRAM_BOT_TOKEN`, `TELEGRAM_RELAY_SECRET`, `TELEGRAM_WEBHOOK_SECRET`. Не вставляйте токен в исходный JS. В Cloudflare секреты доступны скрипту через объект `env`; обычный файл `.env` туда не загружается.
3. Добавьте обычную переменную `TELEGRAM_SITE_URL` со значением `https://voronov-art.ru`. Примените изменения.
4. Возьмите адрес опубликованного Worker. В `.env` основного сайта укажите:

```dotenv
TELEGRAM_RELAY_URL=https://YOUR-WORKER.workers.dev/api
TELEGRAM_WEBHOOK_URL=https://YOUR-WORKER.workers.dev/webhook
```

5. Загрузите обновлённые файлы сайта и `.env` на хостинг. Запустите там `php api/telegram-setup.php`. Команда зарегистрирует webhook через Worker, поэтому прямое соединение основного сервера с Telegram не потребуется.
6. В личном чате с `@voronov_art_bot` отправьте `/start`, затем логин и пароль. После успешного входа отправьте тестовую заявку с сайта.

Если работаете через CLI, в этой папке подготовлен `wrangler.jsonc`. Секреты задаются командами `npx wrangler secret put TELEGRAM_BOT_TOKEN`, `npx wrangler secret put TELEGRAM_RELAY_SECRET` и `npx wrangler secret put TELEGRAM_WEBHOOK_SECRET`; публикация — `npx wrangler deploy`.

Worker проверяет секреты в обоих направлениях, разрешает только нужные API-методы и пересылает webhook исключительно на настроенный сайт. Логин и пароль обрабатываются на основном сайте; на Worker они не сохраняются. Логи Worker отключены в CLI-конфигурации; при размещении через панель не включайте запись тел запросов. Доступность выбранного домена Worker нужно проверить именно с хостинга сайта: этот вариант зависит от доступности Cloudflare. При необходимости подключите доступный собственный домен к Worker.

Уведомления отправляются синхронно; при сетевой ошибке заявка останется в админке, но автоматического повтора уведомления пока нет. Для возврата к прямому соединению удалите `TELEGRAM_RELAY_URL` и `TELEGRAM_WEBHOOK_URL` из `.env`, затем снова выполните настройку webhook.

Документация: [обработчик Worker](https://developers.cloudflare.com/workers/runtime-apis/handlers/fetch/), [секреты](https://developers.cloudflare.com/workers/configuration/secrets/).
