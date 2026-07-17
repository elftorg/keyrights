# Архитектура

## Слои

- `include.php` — регистрация Bitrix autoload и Composer autoload;
- `classes/general/` — legacy-фасад `CKeyrights`, точка входа модуля;
- `lib/controller/` — маршрутизация API и фильтры Engine Controller;
- `lib/model/` — бизнес-логика прав, пользователей, импорта и истории;
- `lib/orm/` — ORM-таблицы служебных сущностей;
- `lib/security/` — централизованные политики доступа и сетевые проверки;
- `install/` — установщик, миграции базы данных, frontend и компонент;
- `tests/` — unit-проверки политик и безопасности установщика.

## Runtime-пути

- страница модуля: `/keyrights/`;
- API-совместимый endpoint: `/keyrights/api.php`;
- компонент: `drdroid:keyrights`;
- установочный компонент: `install/components/drdroid/keyrights/`;
- установленный frontend: `/bitrix/components/drdroid/keyrights/static/`.

## Совместимость данных

Смена имени PHP-модуля не меняет существующие технические имена таблиц и
кодов информационных блоков. Это позволяет устанавливать пакет в окружение с
уже созданными данными без автоматического переименования хранилища.
