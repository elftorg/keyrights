<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('drdroid.keyrights', [
    'CKeyrights' => 'classes/general/ckeyrights.php',
]);

Loader::registerNamespace('Drdroid\\Keyrights', __DIR__ . '/lib');

$vendorAutoload = __DIR__ . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}
