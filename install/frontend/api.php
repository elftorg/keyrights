<?php
define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('DisableEventsCheck', true);
define('PUBLIC_AJAX_MODE', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

global $APPLICATION;
$APPLICATION->RestartBuffer();

$route = isset($_REQUEST['action']) ? trim((string)$_REQUEST['action'], '/') : '';
if (!preg_match('#^(?:crypt|api|exchange|safari-enter-two)(?:/|$)#', $route)) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        ['result' => 'error', 'error' => 'Action not found'],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    die();
}

if (!\Bitrix\Main\Loader::includeModule('drdroid.keyrights') || !class_exists('CKeyrights')) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        ['result' => 'error', 'error' => 'KeyRights module is unavailable'],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    die();
}

// Reuse the module router while bypassing Bitrix's public URL rewriter.
$_SERVER['REAL_FILE_PATH'] = '/keyrights/index.php';
$_SERVER['REQUEST_URI'] = '/keyrights/' . $route;

CKeyrights::getInstance([
    'BASE_PATH' => $_SERVER['DOCUMENT_ROOT'],
    'SEF_MODE' => 'Y',
    'SEF_FOLDER' => '/keyrights/',
])->run();
