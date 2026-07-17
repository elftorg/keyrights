<?php
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$isApiRequest = is_string($requestPath)
    && preg_match('#^/keyrights/(?:crypt|api|exchange)(?:/|$)#', $requestPath);

if ($isApiRequest) {
    define('STOP_STATISTICS', true);
    define('NO_KEEP_STATISTIC', true);
    define('NO_AGENT_STATISTIC', true);
    define('DisableEventsCheck', true);

    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

    global $APPLICATION;
    $APPLICATION->SetShowIncludeAreas(false);
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');

    if (!\Bitrix\Main\Loader::includeModule('drdroid.keyrights') || !class_exists('CKeyrights')) {
        $APPLICATION->RestartBuffer();
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
    ])->run();
    die();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

\Bitrix\Main\UI\Extension::load([
    'main.popup',
    'ui.buttons',
    'ui.forms',
    'ui.dialogs.messagebox',
]);

$keyrightsCss = '/bitrix/components/drdroid/keyrights/static/css/style.css';
$keyrightsCssFile = $_SERVER['DOCUMENT_ROOT'] . $keyrightsCss;
$keyrightsCssVersion = is_file($keyrightsCssFile) ? (int)filemtime($keyrightsCssFile) : 0;

\Bitrix\Main\Page\Asset::getInstance()->addCss(
    $keyrightsCss . ($keyrightsCssVersion ? '?v=' . $keyrightsCssVersion : '')
);

define('KEYRIGHTS_CSS_PRELOADED', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

global $APPLICATION;
$APPLICATION->SetShowIncludeAreas(false);

$APPLICATION->IncludeComponent(
    'drdroid:keyrights',
    '',
    [
        'SEF_MODE' => 'Y',
        'SEF_FOLDER' => '/keyrights/',
    ],
    false
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
