<?php

$MESS['KEYRIGHTS_MODULE_NAME'] = 'KeyRights';
$MESS['KEYRIGHTS_MODULE_DESCRIPTION'] = 'Secure password storage with access permissions for Bitrix24 On-Premise.';
$MESS['KEYRIGHTS_INSTALL'] = 'Install KeyRights';
$MESS['KEYRIGHTS_INSTALL_PROCESS_TITLE'] = 'Install KeyRights';
$MESS['KEYRIGHTS_INSTALL_PROCESS_DESCRIPTION'] = 'You are one step away from completing the KeyRights installation';
$MESS['KEYRIGHTS_INSTALL_REQUIREMENTS_HEADER'] = 'Make sure the following requirements are met:';
$MESS['KEYRIGHTS_INSTALL_REQUIREMENTS'] =
    '<li>MySQL/MariaDB or PostgreSQL database supported by Bitrix</li>' .
    '<li>Bitrix24 On-Premise version 20.5.400 or later</li>' .
    '<li>PHP 8.2 or later</li>' .
    '<li>OpenSSL and the required PHP cryptographic functions</li>' .
    '<li>The Information Blocks and Site Explorer modules are installed</li>' .
    '<li>Module sources are readable; existing target files can be modified and missing files and directories can be created</li>';
$MESS['KEYRIGHTS_HEADER_STEP1'] = 'Installation settings';
$MESS['KEYRIGHTS_LICENSE_STEP1'] = 'I have read and accept the license agreement';
$MESS['KEYRIGHTS_INSTALL_LICENSE_REQUIRED'] = 'You must accept the license agreement to install KeyRights';
$MESS['KEYRIGHTS_INSTALL_REQUIREMENTS_ERROR'] = 'Minimum system requirements are not met';
$MESS['KEYRIGHTS_INSTALL_REQUIREMENTS_REPAIR'] = 'Resolve the listed issues and run the installation again';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DB'] = 'KeyRights requires a MySQL/MariaDB or PostgreSQL database';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DB_ACCESS'] = 'A temporary database write check failed';
$MESS['KEYRIGHTS_INSTALL_REQERROR_BX'] = 'Bitrix version 20.5.400 or later is required';
$MESS['KEYRIGHTS_INSTALL_REQERROR_PHP'] = 'PHP 8.2 or later is required';
$MESS['KEYRIGHTS_INSTALL_REQERROR_OPENSSL'] = 'The PHP OpenSSL extension is not installed';
$MESS['KEYRIGHTS_INSTALL_REQERROR_CRYPTO'] = 'OpenSSL or required PHP cryptographic functions are unavailable';
$MESS['KEYRIGHTS_INSTALL_REQERROR_IBLOCK'] = 'The Information Blocks module is not installed';
$MESS['KEYRIGHTS_INSTALL_REQERROR_FILEMAN'] = 'The Site Explorer module is not installed';
$MESS['KEYRIGHTS_INSTALL_REQERROR_REWRITE'] = 'The urlrewrite.php file is not writable';
$MESS['KEYRIGHTS_INSTALL_REQERROR_FILESYSTEM'] = 'Some module sources are unreadable or installation targets are not writable';
$MESS['KEYRIGHTS_INSTALL_REQUIREMENTS_WARNING'] = 'Filesystem preflight warnings:';
$MESS['KEYRIGHTS_INSTALL_REQERROR_SITE_ID'] = 'The SITE_ID constant is not defined';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DOCROOT'] = 'The site document root was not found: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_SITE'] = 'Could not load settings for site #SITE_ID#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_SOURCE_MISSING'] = 'A required source file or directory is missing: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_SOURCE_UNREADABLE'] = 'A source file or directory is not readable: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_TARGET_CONFLICT'] = 'The path #PATH# has the wrong object type; expected #TYPE#';
$MESS['KEYRIGHTS_INSTALL_FS_TYPE_DIRECTORY'] = 'directory';
$MESS['KEYRIGHTS_INSTALL_FS_TYPE_FILE'] = 'file';
$MESS['KEYRIGHTS_INSTALL_REQERROR_FILE_WRITE'] = 'An existing file cannot be opened for modification: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_TARGET_PARENT'] = 'No existing parent directory was found for the target path: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_TARGET_PARENT_CONFLICT'] = 'Cannot create #PATH#: parent path #PARENT# is not a directory';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_CREATE_FILE'] = 'PHP cannot create a temporary file in directory: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_WRITE_FILE'] = 'PHP cannot write to a temporary file in directory: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_READ_FILE'] = 'PHP cannot read the temporary file created in directory: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_DELETE_FILE'] = 'PHP cannot delete the temporary file created in directory: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_CREATE_DIRECTORY'] = 'PHP cannot create a temporary subdirectory in directory: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQERROR_DIR_DELETE_DIRECTORY'] = 'PHP cannot delete the temporary subdirectory created in directory: #PATH#';
$MESS['KEYRIGHTS_INSTALL_REQWARNING_CHMOD_FILE'] = 'PHP cannot change permissions of a temporary file created in #PATH#; check ownership and filesystem settings';
$MESS['KEYRIGHTS_INSTALL_REQWARNING_CHMOD_DIRECTORY'] = 'PHP cannot change permissions of a temporary subdirectory created in #PATH#; check ownership and filesystem settings';
$MESS['KEYRIGHTS_INSTALL_LEGACY_TABLES_INCOMPLETE'] = 'An incomplete legacy KeyRights schema was found. Both sib_kr_item and sib_kr_right are required for a safe migration';
$MESS['KEYRIGHTS_INSTALL_LEGACY_MIGRATION_ERROR'] = 'Could not migrate data from the legacy KeyRights tables';
$MESS['KEYRIGHTS_INSTALL_PASS_LABEL'] = 'Enter the passphrase used to encrypt passwords: ';
$MESS['KEYRIGHTS_INSTALL_PASS_ALREADY_EXISTS_REWRITE'] = 'A client passphrase already exists. Leave this field empty to keep it.';
$MESS['KEYRIGHTS_INSTALL_PASS_EMPTY'] = 'The encryption passphrase must contain at least 16 characters';
$MESS['KEYRIGHTS_INSTALL_PASS_GENERATE'] = 'Generate passphrase';
$MESS['KEYRIGHTS_INSTALL_PASS_GENERATED_NOTICE'] = 'Store this passphrase securely: encrypted data cannot be recovered without it.';
$MESS['KEYRIGHTS_INSTALL_BUTTON_INSTALL'] = 'Install';
$MESS['KEYRIGHTS_UNINSTALL'] = 'Uninstall KeyRights';
$MESS['KEYRIGHTS_UNINSTALL_SAVE_TABLES'] = 'Keep database tables';
$MESS['KEYRIGHTS_IBLOCK_NAME'] = 'Data';
$MESS['KEYRIGHTS_IBLOCK_SECTION_NAME'] = 'Folder';
$MESS['KEYRIGHTS_IBLOCK_ELEMENT_NAME'] = 'Entry';
