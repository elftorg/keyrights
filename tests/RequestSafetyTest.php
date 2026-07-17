<?php

require_once __DIR__ . '/../classes/general/ckeyrights.php';

$handler = (new ReflectionClass(CKeyrights::class))->newInstanceWithoutConstructor();
$method = new ReflectionMethod($handler, 'getSafeBackUrl');
$method->setAccessible(true);

$cases = [
    '/keyrights/' => '/keyrights/',
    '/keyrights/?section=42' => '/keyrights/?section=42',
    '/keyrights/item/7?tab=access' => '/keyrights/item/7?tab=access',
    '//evil.example/path' => '/keyrights/',
    'https://evil.example/keyrights/' => '/keyrights/',
    '/another-module/' => '/keyrights/',
    "/keyrights/\r\nLocation: https://evil.example" => '/keyrights/',
];

$checks = [];
foreach ($cases as $requestUri => $expected) {
    $_SERVER['REQUEST_URI'] = $requestUri;
    $checks[] = $method->invoke($handler) === $expected;
}

$handlerSource = file_get_contents(__DIR__ . '/../classes/general/ckeyrights.php');
$frontendSource = file_get_contents(__DIR__ . '/../install/frontend/index.php');
$importSource = file_get_contents(__DIR__ . '/../lib/model/import.php');
$historySource = file_get_contents(__DIR__ . '/../lib/model/history.php');

$checks[] = strpos($handlerSource, 'KEYRIGHTS_BASE_URL') === false;
$checks[] = strpos($handlerSource, 'KEYRIGHTS_CSS_PRELOADED') === false;
$checks[] = strpos($frontendSource, 'KEYRIGHTS_CSS_PRELOADED') === false;
$checks[] = strpos($frontendSource, "'CSS_PRELOADED' => 'Y'") !== false;
$checks[] = strpos($importSource, "\$tmpResult['s']") === false;
$checks[] = strpos($historySource, 'use ConvertTimeStamp;') === false;

if (in_array(false, $checks, true)) {
    fwrite(STDERR, "FAIL: request safety or dead-code cleanup changed\n");
    exit(1);
}

fwrite(STDOUT, 'OK: request safety and cleanup (' . count($checks) . " checks)\n");
