# Релизный процесс

## Подготовка

Перед выпуском обновите версию одновременно в:

- `install/version.php`;
- `composer.json`;
- `composer.lock` (обновите `content-hash` командой `composer update --lock`);
- `install/components/drdroid/keyrights/package.json`;
- `install/components/drdroid/keyrights/package-lock.json`.

Добавьте запись в `CHANGELOG.md`, затем выполните локальные проверки:

```bash
composer validate
composer install --no-dev --no-interaction --prefer-dist
composer test
npm ci --prefix install/components/drdroid/keyrights --legacy-peer-deps
npm run build --prefix install/components/drdroid/keyrights
./scripts/build-release.sh 2.1.6
```

Скрипт создаёт `dist/drdroid.keyrights-2.1.6.tar.gz` и `dist/SHA256SUMS`,
проверяет корневую папку модуля, документацию рядом с ней, файлы установщика,
Composer runtime и собранный frontend.

## Публикация

Workflow `.github/workflows/release.yml` запускается для тега формата `vX.Y.Z`:

```bash
git tag -a v2.1.6 -m 'Release drdroid.keyrights 2.1.6'
git push origin v2.1.6
```

Workflow повторяет проверки PHP и frontend, проверяет совпадение тега со всеми
файлами версии, собирает установочный архив и публикует его вместе с
`SHA256SUMS` в GitHub Release. Запуск вручную через Actions также возможен:
укажите версию, совпадающую с `install/version.php`.

В корне выпускаемого архива находятся `drdroid.keyrights/`, `README.md`,
`INSTALLATION.md`, `SECURITY.md`, `ARCHITECTURE.md`, `RELEASE.md`,
`CHANGELOG.md`, `CONTRIBUTING.md` и `LICENSE`. Markdown-инструкции и проектная
лицензия не помещаются внутрь каталога модуля. `tests/`, аудиты, `.git/`,
`.github/` и frontend `node_modules/` исключаются; `vendor/` с
runtime-зависимостями включается в папку модуля.

Релиз 2.1.6 собирается из содержимого модуля без каталогов `docs/`, `release/`,
`.git/` и файлов разработки репозитория.
