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
    '<li>Module sources are readable and the public folder, component, menu, and urlrewrite.php are writable</li>';
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
