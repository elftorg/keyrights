# drdroid.keyrights

Модуль управления защищёнными записями KeyRights для Bitrix24 On-Premise.

Текущий релиз: **2.0.1**.

Основной репозиторий: [github.com/elftorg/keyrights](https://github.com/elftorg/keyrights/).

## Возможности

- хранение логинов, паролей, URL и заметок в зашифрованном виде;
- разграничение доступа пользователей и групп Bitrix24;
- наследование прав разделов;
- журналирование операций и импорт/экспорт данных;
- AES-256-GCM с поддержкой чтения legacy-данных;
- API с централизованной авторизацией, CSRF-защитой и проверкой прав;
- защита favicon-запросов от SSRF;
- копирование в буфер обмена через современный Clipboard API с безопасным
  fallback через `document.execCommand`, без Flash и ZeroClipboard;
- ORM-модели и индексы для служебных таблиц;
- локализация на русском, украинском и английском языках.

## Требования

- PHP 8.2 или новее;
- Bitrix24 On-Premise 20.5.400 или новее;
- расширение OpenSSL;
- MySQL/MariaDB или PostgreSQL, поддерживаемые вашей версией Bitrix24;
- Composer 2 для разработки и обновления зависимостей.

## Установка

1. Скачайте assets `drdroid.keyrights-<версия>.tar.gz` и `SHA256SUMS` из
   нужного [GitHub Release](https://github.com/elftorg/keyrights/releases).
   Не используйте архивы `Source code` — это архивы репозитория, а не пакет
   Bitrix-модуля.
2. Проверьте контрольную сумму в каталоге со скачанными файлами:

   ```bash
   sha256sum -c SHA256SUMS
   ```

3. Распакуйте пакет именно в `/bitrix/modules/`:

   ```bash
   tar -xzf drdroid.keyrights-<версия>.tar.gz -C /bitrix/modules/
   ```

   В результате должен существовать файл
   `/bitrix/modules/drdroid.keyrights/install/index.php`. Не распаковывайте
   архив внутрь дополнительной папки `drdroid.keyrights/`.
4. В административной части Bitrix24 откройте Marketplace → Установленные
   решения (список модулей), найдите `drdroid.keyrights` и нажмите «Установить».
5. Укажите ключевую фразу и подтвердите установку.
6. Откройте `/keyrights/`.

Установщик зарегистрирует компонент `drdroid:keyrights` и скопирует его в
`/bitrix/components/drdroid/keyrights/`. Таблицы базы данных сохраняют
совместимые технические идентификаторы, чтобы не ломать существующие данные.

## Разработка

```bash
composer install
composer test
```

Frontend находится в `install/components/drdroid/keyrights/`.

```bash
npm ci --prefix install/components/drdroid/keyrights
npm run build --prefix install/components/drdroid/keyrights
```

Подробности по архитектуре, безопасности и выпуску находятся в каталоге
[`docs/`](docs/).

## Обратная связь

Предложения, ошибки и запросы на улучшение публикуйте в
[GitHub Issues](https://github.com/elftorg/keyrights/issues).
Модуль не отправляет отчёты или содержимое журналов на внешний адрес.

## Релиз

Готовые архивы и их SHA-256 публикуются как assets соответствующего GitHub Release.
Каждый пакет имеет единственную корневую папку `drdroid.keyrights/`, поэтому
его можно распаковать непосредственно в `/bitrix/modules/`.

## Лицензия

Проект распространяется по лицензии [MIT](LICENSE).
