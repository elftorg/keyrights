<?php

$install = file_get_contents(__DIR__ . '/../install/index.php');
$form = file_get_contents(__DIR__ . '/../install/unstep1.php');
$step = file_get_contents(__DIR__ . '/../install/step1.php');
$pgsqlSchema = file_get_contents(__DIR__ . '/../install/db/pgsql/install.sql');

if ($install === false || $form === false || $step === false || $pgsqlSchema === false) {
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
    strpos($install, 'TableExists') !== false,
    strpos($pgsqlSchema, 'CREATE TABLE IF NOT EXISTS sib_kr_item') !== false,
    strpos($pgsqlSchema, '"group"') !== false,
];

if (in_array(false, $checks, true)) {
    fwrite(STDERR, "FAIL: uninstall safety contract changed\n");
    exit(1);
}

fwrite(STDOUT, 'OK: installer safety contract (' . count($checks) . " checks)\n");
