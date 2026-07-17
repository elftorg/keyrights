# drdroid.keyrights

Модуль управления защищёнными записями KeyRights для Bitrix24 On-Premise.

Текущий релиз: **2.0.0**.

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
- MySQL/MariaDB с поддержкой InnoDB;
- Composer 2 для разработки и обновления зависимостей.

## Установка

1. Распакуйте архив из `release/` в каталог `/bitrix/modules/` так, чтобы
   получился путь `/bitrix/modules/drdroid.keyrights/`.
2. В административной части Bitrix24 откройте список модулей и установите
   `drdroid.keyrights`.
3. Укажите ключевую фразу и подтвердите установку.
4. Откройте `/keyrights/`.

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

Готовый архив и его SHA-256 находятся в `release/`. Архив содержит только
устанавливаемый модуль и не включает материалы репозитория.

## Лицензия

Проект распространяется по лицензии [MIT](LICENSE).
