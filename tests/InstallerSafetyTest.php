<?php

$install = file_get_contents(__DIR__ . '/../install/index.php');
$form = file_get_contents(__DIR__ . '/../install/unstep1.php');
$step = file_get_contents(__DIR__ . '/../install/step1.php');
$pgsqlSchema = file_get_contents(__DIR__ . '/../install/db/pgsql/install.sql');
$apiController = file_get_contents(__DIR__ . '/../lib/controller/apicontroller.php');

if ($install === false || $form === false || $step === false || $pgsqlSchema === false || $apiController === false) {
    fwrite(STDERR, "FAIL: installer files are not readable\n");
    exit(1);
}

$checks = [
    strpos($install, '$step !== 2') !== false,
    strpos($install, "\$moduleParams['deleteTables']") !== false,
    strpos($install, 'if (!$keepData)') !== false,
    strpos($form, 'name="module[deleteTables]"') !== false,
    strpos($install, 'DeleteDirFilesEx("/bitrix/cache/drdroid.keyrights/")') !== false,
    strpos($install, '$GLOBALS["reqCheck"] = is_array($reqCheck)') !== false,
    strpos($step, "is_array(\$reqCheck)") !== false,
    strpos($install, 'in_array($dbType, array("mysql", "pgsql"), true)') !== false,
    strpos($install, "'curl'") === false,
    strpos($apiController, 'api/favicon') === false,
    strpos($apiController, 'curl_init') === false,
    strpos($install, 'TableExists') !== false,
    strpos($install, 'migrateLegacyDatabase') !== false,
    strpos($install, 'sib_kr_item') !== false,
    strpos($install, 'sib_kr_right') !== false,
    strpos($install, 'if (!$legacyItemsExist && !$legacyRightsExist)') !== false,
    strpos($install, 'if (!$legacyItemsExist || !$legacyRightsExist)') !== false,
    strpos($install, 'DROP TABLE IF EXISTS') !== false,
    strpos($install, 'DROP TABLE IF EXISTS "sib_kr_') === false,
    strpos($step, 'jquery-3.7.1.min.js') === false,
    strpos($step, 'window.crypto.getRandomValues') !== false,
    strpos($step, "document.addEventListener('DOMContentLoaded'") !== false,
    strpos($step, '$(') === false,
    strpos($install, "base64_encode(random_bytes(24))") !== false,
    strpos($install, "(\$_REQUEST['licence_agree'] ?? '') !== 'Y'") !== false,
    strpos($install, 'checkDatabasePreflight') !== false,
    strpos($install, 'checkFilesystemPreflight') !== false,
    strpos($install, 'isWritableTarget') === false,
    strpos($install, "fopen(\$path, 'r+b')") !== false,
    strpos($install, "fopen(\$probeFile, 'x+b')") !== false,
    strpos($install, '@chmod($probeFile, 0600)') !== false,
    strpos($install, "including .left.menu.php, are created by Bitrix") !== false,
    strpos($step, "htmlspecialcharsbx((string)\$error)") !== false,
    strpos($step, "\$reqCheck['warnings']") !== false,
    strpos($install, 'CModule::IncludeModule("fileman")') !== false,
    strpos($step, 'ajax.googleapis.com') === false,
    strpos($install, 'clientPassphraseEncrypted') !== false,
    strpos($install, 'serverPassphraseEncrypted') !== false,
    strpos($install, 'protectSecret') !== false,
    strpos($install, 'upgradeStoredSecrets') !== false,
    strpos($install, 'bin2hex(random_bytes(32))') !== false,
    strpos($install, "RemoveOption(\$this->MODULE_ID, \$name)") !== false,
    strpos($pgsqlSchema, 'CREATE TABLE IF NOT EXISTS dr_kr_item') !== false,
    strpos($pgsqlSchema, '"group"') !== false,
];

if (in_array(false, $checks, true)) {
    fwrite(STDERR, "FAIL: uninstall safety contract changed\n");
    exit(1);
}

fwrite(STDOUT, 'OK: installer safety contract (' . count($checks) . " checks)\n");
