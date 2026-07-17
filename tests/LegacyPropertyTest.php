<?php

require_once __DIR__ . '/../lib/model/rightmanager.php';

$reflection = new ReflectionClass(Drdroid\Keyrights\Model\RightManager::class);
$manager = $reflection->newInstanceWithoutConstructor();
$method = $reflection->getMethod('extractHtmlPropertyText');
$method->setAccessible(true);

$ciphertext = 'v2:nonce:tag:ciphertext';
$legacyProperty = serialize(['TYPE' => 'TEXT', 'TEXT' => $ciphertext]);
$actual = $method->invoke($manager, $legacyProperty);
if ($actual !== $ciphertext) {
    fwrite(STDERR, "FAIL: legacy HTML property was not decoded safely\n");
    exit(1);
}

$objectPayload = 'O:8:"stdClass":0:{}';
if ($method->invoke($manager, $objectPayload) !== $objectPayload) {
    fwrite(STDERR, "FAIL: object serialization must not be interpreted\n");
    exit(1);
}

fwrite(STDOUT, "OK: legacy property parser\n");
