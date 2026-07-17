<?php

$MESS['KEYRIGHTS_MODULE_NAME'] = 'KeyRights';

$MESS['KEYRIGHTS_MODULE_DESCRIPTION'] = 'Безопасный парольник с разделением прав доступа для Корпоративного портала 1С-Битрикс.';

$MESS['KEYRIGHTS_INSTALL'] = 'Установка модуля KeyRights';

$MESS['KEYRIGHTS_INSTALL_PROCESS_DESCRIPTION'] = 'Вы в одном шаге от завершения установки KeyRights';
$MESS['KEYRIGHTS_INSTALL_PROCESS_TITLE'] = 'Установка KeyRights';

$MESS['KEYRIGHTS_INSTALL_REQUIREMENTS_HEADER'] = 'Для корректной работы модуля необходимо, чтобы следующие требования были соблюдены. Проверьте, пожалуйста:';

$MESS['KEYRIGHTS_INSTALL_REQUIREMENTS'] =

    "<li>БД MySQL/MariaDB или PostgreSQL, поддерживаемая Битрикс</li>" .

    "<li><b>Битрикс версии 20.5.400</b> и выше;</li>" .

    "<li><b>PHP 8.2</b> или новее;</li>" .

    "<li>Расширение PHP <b>OpenSSL</b> и криптографические функции PHP.</li>" .

    "<li>Установлены модули <b>&laquo;Информационные блоки&raquo;</b> и <b>&laquo;Управление структурой&raquo;</b>.</li>" .

    "<li>Исходные файлы доступны для чтения; существующие целевые файлы можно изменить, а отсутствующие файлы и каталоги — создать.</li>";

$MESS['KEYRIGHTS_HEADER_STEP1'] = 'Параметры установки';

$MESS['KEYRIGHTS_LICENSE_STEP1'] = 'Я прочитал <a href="https://github.com/elftorg/keyrights/blob/main/LICENSE" target="_blank" rel="noopener noreferrer">лицензионное соглашение</a> и согласен с ним';
$MESS['KEYRIGHTS_INSTALL_LICENSE_REQUIRED'] = 'Для установки необходимо принять лицензионное соглашение';

$MESS['KEYRIGHTS_INSTALL_REQUIREMENTS_ERROR'] = "Не удовлетворены минимальные системные требования";

$MESS['KEYRIGHTS_INSTALL_REQUIREMENTS_REPAIR'] = "Пожалуйста, устраните перечисленные замечания, и попробуйте повторить установку";

$MESS['KEYRIGHTS_INSTALL_REQERROR_DB'] = 'Установка KeyRights возможна только на MySQL/MariaDB или PostgreSQL';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DB_ACCESS'] = 'Не удалось выполнить тестовую операцию записи во временную таблицу базы данных';

$MESS['KEYRIGHTS_INSTALL_REQERROR_BX'] = 'Требуется Битрикс, версии 20.5.400 и выше';

$MESS['KEYRIGHTS_INSTALL_REQERROR_PHP'] = 'Требуется PHP 8.2 или новее';
$MESS['KEYRIGHTS_INSTALL_REQERROR_OPENSSL'] = 'Не установлено расширение PHP OpenSSL';
$MESS['KEYRIGHTS_INSTALL_REQERROR_CRYPTO'] = 'Недоступны OpenSSL или обязательные криптографические функции PHP';

$MESS['KEYRIGHTS_INSTALL_REQERROR_IBLOCK'] = 'Не установлен модуль Информационные блоки';
$MESS['KEYRIGHTS_INSTALL_REQERROR_FILEMAN'] = 'Не установлен модуль Управление структурой';

$MESS['KEYRIGHTS_INSTALL_REQERROR_REWRITE'] = 'Нет прав на запись в файл urlrewrite.php';
$MESS['KEYRIGHTS_INSTALL_REQERROR_FILESYSTEM'] = 'Не все файлы модуля доступны для чтения или целевые каталоги и служебные файлы доступны для записи';
$MESS['KEYRIGHTS_INSTALL_REQUIREMENTS_WARNING'] = 'Предупреждения проверки файловой системы:';
$MESS['KEYRIGHTS_INSTALL_REQERROR_SITE_ID'] = 'Не определён идентификатор сайта SITE_ID';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DOCROOT'] = 'Не найден корневой каталог сайта: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_SITE'] = 'Не удалось получить настройки сайта #SITE_ID#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_SOURCE_MISSING'] = 'Отсутствует обязательный исходный файл или каталог: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_SOURCE_UNREADABLE'] = 'Нет прав на чтение исходного файла или каталога: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_TARGET_CONFLICT'] = 'Путь #PATH# занят объектом другого типа; ожидается #TYPE#';
$MESS['KEYRIGHTS_INSTALL_FS_TYPE_DIRECTORY'] = 'каталог';
$MESS['KEYRIGHTS_INSTALL_FS_TYPE_FILE'] = 'файл';
$MESS['KEYRIGHTS_INSTALL_REQERROR_FILE_WRITE'] = 'Существующий файл нельзя открыть для изменения: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_TARGET_PARENT'] = 'Не найден существующий родительский каталог для создания пути: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_TARGET_PARENT_CONFLICT'] = 'Нельзя создать #PATH#: родительский путь #PARENT# не является каталогом';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_CREATE_FILE'] = 'PHP не может создать временный файл в каталоге: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_WRITE_FILE'] = 'PHP не может записать данные во временный файл в каталоге: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_READ_FILE'] = 'PHP не может прочитать созданный временный файл в каталоге: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_DELETE_FILE'] = 'PHP не может удалить созданный временный файл в каталоге: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_CREATE_DIRECTORY'] = 'PHP не может создать временный подкаталог в каталоге: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_DELETE_DIRECTORY'] = 'PHP не может удалить созданный временный подкаталог в каталоге: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQWARNING_CHMOD_FILE'] = 'PHP не может изменить права созданного временного файла в каталоге #PATH#; проверьте владельца и настройки файловой системы';
$MESS['KEYRIGHTS_INSTALL_REQWARNING_CHMOD_DIRECTORY'] = 'PHP не может изменить права созданного временного подкаталога в каталоге #PATH#; проверьте владельца и настройки файловой системы';
$MESS['KEYRIGHTS_INSTALL_LEGACY_TABLES_INCOMPLETE'] = 'Обнаружен неполный набор старых таблиц KeyRights. Для безопасной миграции необходимы обе таблицы: sib_kr_item и sib_kr_right';
$MESS['KEYRIGHTS_INSTALL_LEGACY_MIGRATION_ERROR'] = 'Не удалось перенести данные из старых таблиц KeyRights';

$MESS['KEYRIGHTS_INSTALL_PASS_LABEL'] = 'Введите ключ-фразу для шифрации паролей: ';

$MESS['KEYRIGHTS_INSTALL_PASS_ALREADY_EXISTS_REWRITE'] = 'Клиентский пароль уже задан (модуль KeyRights ранее был установлен).<br>Если вы хотите использовать существующий пароль - оставьте это поле пустым.';

$MESS['KEYRIGHTS_INSTALL_PASS_EMPTY'] = 'Ключевая фраза должна содержать не менее 16 символов';
$MESS['KEYRIGHTS_INSTALL_PASS_GENERATE'] = 'Сгенерировать ключ-фразу';
$MESS['KEYRIGHTS_INSTALL_PASS_GENERATED_NOTICE'] = 'Сохраните ключ-фразу в надёжном месте: без неё данные нельзя будет расшифровать.';

$MESS['KEYRIGHTS_INSTALL_BUTTON_INSTALL'] = 'Установить';

$MESS['KEYRIGHTS_UNINSTALL'] = 'Удаление модуля KeyRights';

$MESS['KEYRIGHTS_UNINSTALL_SAVE_TABLES'] = 'Сохранить таблицы';



$MESS['KEYRIGHTS_IBLOCK_NAME'] = "Данные";

$MESS['KEYRIGHTS_IBLOCK_SECTION_NAME'] = 'Раздел';

$MESS['KEYRIGHTS_IBLOCK_ELEMENT_NAME'] = 'Элемент';



//$MESS[''] = '';
