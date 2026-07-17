# Установка и обновление

## Чистая установка

1. Скачайте из нужного [GitHub Release](https://github.com/elftorg/keyrights/releases)
   два assets: `drdroid.keyrights-<версия>.tar.gz` и `SHA256SUMS`. Архивы
   `Source code` для установки не подходят.
2. Проверьте архив:

   ```bash
   sha256sum -c SHA256SUMS
   ```

3. Распакуйте его в `/bitrix/modules/`:

   ```bash
   tar -xzf drdroid.keyrights-<версия>.tar.gz -C /bitrix/modules/
   ```

   В архиве уже есть корневая папка `drdroid.keyrights/`, поэтому результатом
   должен быть путь `/bitrix/modules/drdroid.keyrights/`. Не создавайте второй
   уровень `drdroid.keyrights/drdroid.keyrights/`.
4. В административной части Bitrix24 откройте Marketplace → Установленные
   решения (список модулей), выберите `drdroid.keyrights` и нажмите «Установить».

Установщик проверяет PHP, Bitrix и обязательные расширения, создаёт служебные
таблицы в MySQL/MariaDB или PostgreSQL, регистрирует обработчики событий и
копирует компонент.

## Перед обновлением

Перед изменением production-окружения:

1. сделайте резервную копию файлов `/bitrix/modules/drdroid.keyrights/` и
   `/bitrix/components/drdroid/keyrights/`;
2. сделайте резервную копию базы данных;
3. проверьте наличие OpenSSL и доступность Composer-зависимостей;
4. протестируйте установку в staging.

## Удаление

Удаление выполняется штатным Bitrix-установщиком. Перед удалением сохраните
резервную копию: операция удаляет созданные модулем таблицы и разделы данных.
