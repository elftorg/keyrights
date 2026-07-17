<?php

$MESS['KEYRIGHTS_MODULE_NAME'] = 'KeyRights';
$MESS['KEYRIGHTS_MODULE_DESCRIPTION'] = 'Безпечне зберігання паролів із розмежуванням прав доступу для коробкового Бітрікс24.';
$MESS['KEYRIGHTS_INSTALL'] = 'Встановлення KeyRights';
$MESS['KEYRIGHTS_INSTALL_PROCESS_TITLE'] = 'Встановлення KeyRights';
$MESS['KEYRIGHTS_INSTALL_PROCESS_DESCRIPTION'] = 'Залишився один крок до завершення встановлення KeyRights';
$MESS['KEYRIGHTS_INSTALL_REQUIREMENTS_HEADER'] = 'Переконайтеся, що виконано такі вимоги:';
$MESS['KEYRIGHTS_INSTALL_REQUIREMENTS'] =
    '<li>База даних MySQL/MariaDB або PostgreSQL, що підтримується Бітрікс</li>' .
    '<li>Коробковий Бітрікс24 версії 20.5.400 або новішої</li>' .
    '<li>PHP 8.2 або новіший</li>' .
    '<li>OpenSSL та обов’язкові криптографічні функції PHP</li>' .
    '<li>Встановлено модулі «Інформаційні блоки» та «Керування структурою»</li>' .
    '<li>Файли модуля доступні для читання; наявні цільові файли можна змінити, а відсутні файли й каталоги — створити</li>';
$MESS['KEYRIGHTS_HEADER_STEP1'] = 'Параметри встановлення';
$MESS['KEYRIGHTS_LICENSE_STEP1'] = 'Я прочитав ліцензійну угоду та погоджуюся з нею';
$MESS['KEYRIGHTS_INSTALL_LICENSE_REQUIRED'] = 'Для встановлення потрібно прийняти ліцензійну угоду';
$MESS['KEYRIGHTS_INSTALL_REQUIREMENTS_ERROR'] = 'Мінімальні системні вимоги не виконано';
$MESS['KEYRIGHTS_INSTALL_REQUIREMENTS_REPAIR'] = 'Усуньте зазначені проблеми та повторіть встановлення';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DB'] = 'KeyRights потребує базу даних MySQL/MariaDB або PostgreSQL';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DB_ACCESS'] = 'Не вдалося виконати тестовий запис до тимчасової таблиці бази даних';
$MESS['KEYRIGHTS_INSTALL_REQERROR_BX'] = 'Потрібен Бітрікс версії 20.5.400 або новішої';
$MESS['KEYRIGHTS_INSTALL_REQERROR_PHP'] = 'Потрібен PHP 8.2 або новіший';
$MESS['KEYRIGHTS_INSTALL_REQERROR_OPENSSL'] = 'Не встановлено розширення PHP OpenSSL';
$MESS['KEYRIGHTS_INSTALL_REQERROR_CRYPTO'] = 'OpenSSL або обов’язкові криптографічні функції PHP недоступні';
$MESS['KEYRIGHTS_INSTALL_REQERROR_IBLOCK'] = 'Модуль «Інформаційні блоки» не встановлено';
$MESS['KEYRIGHTS_INSTALL_REQERROR_FILEMAN'] = 'Модуль «Керування структурою» не встановлено';
$MESS['KEYRIGHTS_INSTALL_REQERROR_REWRITE'] = 'Немає прав на запис у файл urlrewrite.php';
$MESS['KEYRIGHTS_INSTALL_REQERROR_FILESYSTEM'] = 'Деякі файли модуля недоступні для читання або цільові каталоги недоступні для запису';
$MESS['KEYRIGHTS_INSTALL_REQUIREMENTS_WARNING'] = 'Попередження перевірки файлової системи:';
$MESS['KEYRIGHTS_INSTALL_REQERROR_SITE_ID'] = 'Не визначено ідентифікатор сайту SITE_ID';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DOCROOT'] = 'Не знайдено кореневий каталог сайту: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_SITE'] = 'Не вдалося отримати налаштування сайту #SITE_ID#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_SOURCE_MISSING'] = 'Відсутній обов’язковий вихідний файл або каталог: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_SOURCE_UNREADABLE'] = 'Немає прав на читання вихідного файла або каталогу: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_TARGET_CONFLICT'] = 'Шлях #PATH# зайнятий об’єктом іншого типу; очікується #TYPE#';
$MESS['KEYRIGHTS_INSTALL_FS_TYPE_DIRECTORY'] = 'каталог';
$MESS['KEYRIGHTS_INSTALL_FS_TYPE_FILE'] = 'файл';
$MESS['KEYRIGHTS_INSTALL_REQERROR_FILE_WRITE'] = 'Наявний файл неможливо відкрити для зміни: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_TARGET_PARENT'] = 'Не знайдено наявний батьківський каталог для створення шляху: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_TARGET_PARENT_CONFLICT'] = 'Неможливо створити #PATH#: батьківський шлях #PARENT# не є каталогом';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_CREATE_FILE'] = 'PHP не може створити тимчасовий файл у каталозі: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_WRITE_FILE'] = 'PHP не може записати дані до тимчасового файла в каталозі: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_READ_FILE'] = 'PHP не може прочитати створений тимчасовий файл у каталозі: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_DELETE_FILE'] = 'PHP не може видалити створений тимчасовий файл у каталозі: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_CREATE_DIRECTORY'] = 'PHP не може створити тимчасовий підкаталог у каталозі: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_DELETE_DIRECTORY'] = 'PHP не може видалити створений тимчасовий підкаталог у каталозі: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQWARNING_CHMOD_FILE'] = 'PHP не може змінити права створеного тимчасового файла в каталозі #PATH#; перевірте власника та налаштування файлової системи';
$MESS['KEYRIGHTS_INSTALL_REQWARNING_CHMOD_DIRECTORY'] = 'PHP не може змінити права створеного тимчасового підкаталогу в каталозі #PATH#; перевірте власника та налаштування файлової системи';
$MESS['KEYRIGHTS_INSTALL_LEGACY_TABLES_INCOMPLETE'] = 'Виявлено неповний набір старих таблиць KeyRights. Для безпечної міграції потрібні обидві таблиці: sib_kr_item та sib_kr_right';
$MESS['KEYRIGHTS_INSTALL_LEGACY_MIGRATION_ERROR'] = 'Не вдалося перенести дані зі старих таблиць KeyRights';
$MESS['KEYRIGHTS_INSTALL_PASS_LABEL'] = 'Введіть ключову фразу для шифрування паролів: ';
$MESS['KEYRIGHTS_INSTALL_PASS_ALREADY_EXISTS_REWRITE'] = 'Клієнтський пароль уже заданий. Залиште поле порожнім, щоб зберегти його.';
$MESS['KEYRIGHTS_INSTALL_PASS_EMPTY'] = 'Ключова фраза має містити щонайменше 16 символів';
$MESS['KEYRIGHTS_INSTALL_PASS_GENERATE'] = 'Згенерувати ключову фразу';
$MESS['KEYRIGHTS_INSTALL_PASS_GENERATED_NOTICE'] = 'Збережіть ключову фразу в надійному місці: без неї дані неможливо розшифрувати.';
$MESS['KEYRIGHTS_INSTALL_BUTTON_INSTALL'] = 'Встановити';
$MESS['KEYRIGHTS_UNINSTALL'] = 'Видалення KeyRights';
$MESS['KEYRIGHTS_UNINSTALL_SAVE_TABLES'] = 'Зберегти таблиці бази даних';
$MESS['KEYRIGHTS_IBLOCK_NAME'] = 'Дані';
$MESS['KEYRIGHTS_IBLOCK_SECTION_NAME'] = 'Папка';
$MESS['KEYRIGHTS_IBLOCK_ELEMENT_NAME'] = 'Запис';
