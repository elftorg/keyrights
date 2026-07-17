<?php

if (!class_exists('CModule')) {
    class CModule {}
}
if (!function_exists('IncludeModuleLangFile')) {
    function IncludeModuleLangFile($path) {}
}
if (!function_exists('GetMessage')) {
    function GetMessage($code) {
        return $code . ': #PATH# #PARENT# #TYPE#';
    }
}

require_once __DIR__ . '/../install/index.php';

$reflection = new ReflectionClass('drdroid_keyrights');
$installer = $reflection->newInstanceWithoutConstructor();
$checkFile = $reflection->getMethod('checkFileTargetPreflight');
$checkFile->setAccessible(true);
$checkDirectory = $reflection->getMethod('checkDirectoryTargetPreflight');
$checkDirectory->setAccessible(true);

$testRoot = sys_get_temp_dir() . '/keyrights-preflight-test-' . bin2hex(random_bytes(8));
if (!mkdir($testRoot, 0700)) {
    fwrite(STDERR, "FAIL: cannot create preflight test directory\n");
    exit(1);
}

$failures = array();
try {
    $result = array('errors' => array(), 'warnings' => array());
    $missingMenu = $testRoot . '/.left.menu.php';
    $arguments = array($missingMenu, &$result);
    $checkFile->invokeArgs($installer, $arguments);
    if (count($result['errors']) !== 0 || file_exists($missingMenu)) {
        $failures[] = 'missing menu file must be handled through a parent-directory probe';
    }

    $result = array('errors' => array(), 'warnings' => array());
    $missingDirectory = $testRoot . '/component/static';
    $arguments = array($missingDirectory, &$result);
    $checkDirectory->invokeArgs($installer, $arguments);
    if (count($result['errors']) !== 0 || file_exists($missingDirectory)) {
        $failures[] = 'missing installer-created directory must not be required in advance';
    }

    $existingFile = $testRoot . '/urlrewrite.php';
    $originalContents = '<?php return array();';
    file_put_contents($existingFile, $originalContents);
    $result = array('errors' => array(), 'warnings' => array());
    $arguments = array($existingFile, &$result);
    $checkFile->invokeArgs($installer, $arguments);
    if (count($result['errors']) !== 0 || file_get_contents($existingFile) !== $originalContents) {
        $failures[] = 'existing writable file check must not alter its contents';
    }

    $conflict = $testRoot . '/conflict';
    mkdir($conflict, 0700);
    $result = array('errors' => array(), 'warnings' => array());
    $arguments = array($conflict, &$result);
    $checkFile->invokeArgs($installer, $arguments);
    if (count($result['errors']) !== 1 || strpos($result['errors'][0], $conflict) === false) {
        $failures[] = 'target type conflict must include the exact path';
    }

    $result = array('errors' => array(), 'warnings' => array());
    $blockedTarget = '/proc/keyrights-preflight-' . bin2hex(random_bytes(4)) . '/.left.menu.php';
    $arguments = array($blockedTarget, &$result);
    $checkFile->invokeArgs($installer, $arguments);
    if (count($result['errors']) === 0 || strpos(implode(' ', $result['errors']), '/proc') === false) {
        $failures[] = 'failed parent-directory probe must report the exact existing parent';
    }
} finally {
    if (isset($existingFile) && is_file($existingFile)) {
        unlink($existingFile);
    }
    if (isset($conflict) && is_dir($conflict)) {
        rmdir($conflict);
    }
    rmdir($testRoot);
}

if (count($failures) > 0) {
    fwrite(STDERR, 'FAIL: ' . implode('; ', $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK: filesystem preflight probes\n");
