<?php

require_once __DIR__ . '/../lib/model/history.php';

$history = (new ReflectionClass(\Drdroid\Keyrights\Model\History::class))
    ->newInstanceWithoutConstructor();
$method = new ReflectionMethod($history, 'parseDateRange');
$method->setAccessible(true);

$valid = $method->invoke($history, [
    'dateFrom' => '29.02.2024',
    'dateUntil' => '01.03.2024',
]);

$checks = [
    is_array($valid),
    isset($valid['from'], $valid['until']),
    date('H:i:s', $valid['from']) === '00:00:00',
    date('H:i:s', $valid['until']) === '23:59:59',
    $method->invoke($history, ['dateFrom' => '31.02.2024', 'dateUntil' => '01.03.2024']) === null,
    $method->invoke($history, ['dateFrom' => '2024-02-29', 'dateUntil' => '01.03.2024']) === null,
    $method->invoke($history, ['dateFrom' => '02.03.2024', 'dateUntil' => '01.03.2024']) === null,
    $method->invoke($history, ['dateFrom' => ['29.02.2024'], 'dateUntil' => '01.03.2024']) === null,
];

if (in_array(false, $checks, true)) {
    fwrite(STDERR, "FAIL: strict history date validation changed\n");
    exit(1);
}

fwrite(STDOUT, 'OK: strict history date validation (' . count($checks) . " checks)\n");
