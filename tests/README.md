# Проверки

Запускайте из корня проекта. Нужны PHP 8.1+ с SQLite и современный Node.js. Настоящая база и Telegram не используются.

```
php tests/telegram-test.php
php tests/telegram-notification-test.php
node tests/telegram-worker-test.mjs
php tests/admin-bulk-test.php valid
php tests/admin-bulk-test.php bad-csrf
php tests/admin-bulk-test.php bad-status
php tests/admin-bulk-test.php bad-id
php tests/admin-bulk-test.php over-limit
php tests/admin-bulk-test.php empty
```

Проверяются вход и ограничения попыток, отсутствие данных заявки в уведомлении, защита Worker и массовая смена статусов в изолированной SQLite-базе.
