# Релизный процесс

1. Обновить номер версии в `install/version.php` и frontend package metadata.
2. Выполнить `composer validate` и `composer test`.
3. Выполнить `npm ci` и production-сборку frontend.
4. Проверить отсутствие устаревших module ID, namespace и путей.
5. Собрать архив с корневой директорией `drdroid.keyrights/`.
6. Создать SHA-256 файл и проверить содержимое архива в чистом каталоге.

Релиз 2.0.0 собран из содержимого модуля без каталогов `docs/`, `release/`,
`.git/` и файлов разработки репозитория.
