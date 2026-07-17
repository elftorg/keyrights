# Релизный процесс

## Подготовка

Перед выпуском обновите версию одновременно в:

- `install/version.php`;
- `composer.json`;
- `install/components/drdroid/keyrights/package.json`;
- `install/components/drdroid/keyrights/package-lock.json`.

Добавьте запись в `CHANGELOG.md`, затем выполните локальные проверки:

```bash
composer validate
composer install --no-dev --no-interaction --prefer-dist
composer test
npm ci --prefix install/components/drdroid/keyrights --legacy-peer-deps
npm run build --prefix install/components/drdroid/keyrights
./scripts/build-release.sh 2.0.1
```

Скрипт создаёт `dist/drdroid.keyrights-2.0.1.tar.gz` и `dist/SHA256SUMS`,
проверяет единственную корневую папку архива и наличие файлов установщика,
Composer runtime и собранного frontend.

## Публикация

Workflow `.github/workflows/release.yml` запускается для тега формата `vX.Y.Z`:

```bash
git tag -a v2.0.1 -m 'Release drdroid.keyrights 2.0.1'
git push origin v2.0.1
```

Workflow повторяет проверки PHP и frontend, проверяет совпадение тега со всеми
файлами версии, собирает установочный архив и публикует его вместе с
`SHA256SUMS` в GitHub Release. Запуск вручную через Actions также возможен:
укажите версию, совпадающую с `install/version.php`.

Выпускаемый архив содержит только устанавливаемый модуль. Его структура имеет
вид `drdroid.keyrights/<файлы модуля>` и не включает `docs/`, `tests/`,
`release/`, `.git/`, `.github/` и frontend `node_modules/`. Каталог `vendor/`
с runtime-зависимостями включается в пакет.

Релиз 2.0.0 собран из содержимого модуля без каталогов `docs/`, `release/`,
`.git/` и файлов разработки репозитория.
