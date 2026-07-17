<?php

/**
 * Opt-in HTTP integration checks.
 *
 * Required environment variables:
 * KEYRIGHTS_TEST_URL, KEYRIGHTS_TEST_COOKIE, KEYRIGHTS_TEST_CSRF,
 * KEYRIGHTS_TEST_ITEM_ID, KEYRIGHTS_TEST_SECTION_ID.
 *
 * The cookie must belong to a non-administrator test user. The test only
 * submits denied requests only; it does not alter application data.
 */

$required = [
    'KEYRIGHTS_TEST_URL',
    'KEYRIGHTS_TEST_COOKIE',
    'KEYRIGHTS_TEST_CSRF',
    'KEYRIGHTS_TEST_ITEM_ID',
    'KEYRIGHTS_TEST_SECTION_ID',
];
foreach ($required as $name) {
    if (getenv($name) === false || getenv($name) === '') {
        if (getenv('KEYRIGHTS_TEST_REQUIRED') === '1') {
            fwrite(STDERR, "FAIL: {$name} is not configured\n");
            exit(1);
        }
        fwrite(STDOUT, "SKIP: {$name} is not configured\n");
        exit(0);
    }
}

$baseUrl = rtrim((string)getenv('KEYRIGHTS_TEST_URL'), '/');
$cookie = (string)getenv('KEYRIGHTS_TEST_COOKIE');
$csrf = (string)getenv('KEYRIGHTS_TEST_CSRF');
$itemId = (int)getenv('KEYRIGHTS_TEST_ITEM_ID');
$sectionId = (int)getenv('KEYRIGHTS_TEST_SECTION_ID');

$request = static function ($path, array $payload = []) use ($baseUrl, $cookie, $csrf) {
    $curl = curl_init($baseUrl . $path);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Cookie: ' . $cookie,
            'X-Bitrix-Csrf-Token: ' . $csrf,
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
    $raw = curl_exec($curl);
    if ($raw === false) {
        throw new RuntimeException(curl_error($curl));
    }
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    curl_close($curl);

    return [$status, json_decode(substr($raw, $headerSize), true)];
};

$assert = static function ($condition, $message) {
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
};

[$status, $data] = $request('/keyrights/api.php?action=crypt/rights/remove', [
    'csrf_token' => $csrf,
    'data' => [['id' => 1, 'isGroup' => false]],
]);
$assert($status === 403 && ($data['result'] ?? null) === 'error', 'non-admin rights/remove is denied');

[$status, $data] = $request('/keyrights/api.php?action=crypt/password/save', [
    'csrf_token' => $csrf,
    'ID' => $itemId,
    'SECTION' => $sectionId + 1,
    'NAME' => 'IDOR probe',
    'COLOR' => '',
    'CRYPTED' => 'v2:invalid',
]);
$assert($status === 403 && ($data['result'] ?? null) === 'error', 'cross-section item update is denied');

fwrite(STDOUT, "OK: HTTP access integration checks\n");
