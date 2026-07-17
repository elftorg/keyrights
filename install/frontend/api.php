<?php
define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('DisableEventsCheck', true);
define('PUBLIC_AJAX_MODE', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

global $APPLICATION;
$APPLICATION->RestartBuffer();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

$routeParam = array_key_exists('action', $_GET) ? $_GET['action'] : ($_POST['action'] ?? '');
$route = trim((string)$routeParam, '/');
if (!preg_match('#^(?:crypt|api|exchange)(?:/|$)#', $route)) {
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

CKeyrights::getInstance([
    'BASE_PATH' => $_SERVER['DOCUMENT_ROOT'],
    'SEF_MODE' => 'Y',
    'SEF_FOLDER' => '/keyrights/',
    'ROUTE' => $route,
])->run();
