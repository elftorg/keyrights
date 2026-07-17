<?php

$install = file_get_contents(__DIR__ . '/../install/index.php');
$form = file_get_contents(__DIR__ . '/../install/unstep1.php');

if ($install === false || $form === false) {
    fwrite(STDERR, "FAIL: installer files are not readable\n");
    exit(1);
}

$checks = [
    strpos($install, '$step !== 2') !== false,
    strpos($install, "\$moduleParams['deleteTables']") !== false,
    strpos($install, 'if (!$keepData)') !== false,
    strpos($form, 'name="module[deleteTables]"') !== false,
    strpos($install, 'DeleteDirFilesEx("/bitrix/cache/drdroid.keyrights/")') !== false,
];

if (in_array(false, $checks, true)) {
    fwrite(STDERR, "FAIL: uninstall safety contract changed\n");
    exit(1);
}

fwrite(STDOUT, 'OK: installer safety contract (' . count($checks) . " checks)\n");
