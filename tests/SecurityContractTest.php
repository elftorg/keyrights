<?php

$root = dirname(__DIR__);
$files = [
    'api' => $root . '/lib/controller/apicontroller.php',
    'rights' => $root . '/lib/model/rightmanager.php',
    'user' => $root . '/lib/model/user.php',
    'crypt' => $root . '/install/components/drdroid/keyrights/static/js/helpers/crypt.js',
    'csv' => $root . '/install/components/drdroid/keyrights/static/js/helpers/csv.js',
    'random' => $root . '/install/components/drdroid/keyrights/static/js/helpers/helpers.js',
    'aes' => $root . '/install/components/drdroid/keyrights/static/js/libs/aes.js',
    'import' => $root . '/lib/model/import.php',
    'installerForm' => $root . '/install/step1.php',
    'ci' => $root . '/.github/workflows/keyrights.yml',
    'release' => $root . '/.github/workflows/release.yml',
];

$source = [];
foreach ($files as $name => $path) {
    $source[$name] = file_get_contents($path);
    if ($source[$name] === false) {
        fwrite(STDERR, "FAIL: cannot read {$path}\n");
        exit(1);
    }
}

$checks = [
    'authentication' => strpos($source['api'], 'new Authentication()') !== false,
    'filtered tree' => strpos($source['api'], 'getAccessibleSectionsTree') !== false,
    'directory ACL' => strpos($source['api'], 'currentUserCanManageRights') !== false,
    'key API removed' => strpos($source['api'], 'entity.item.get') === false,
    'ACL validation' => strpos($source['rights'], 'validateRights') !== false,
    'ACL duplicates' => strpos($source['rights'], 'Duplicate right subject') !== false,
    'unsafe unserialize removed' => strpos($source['rights'], 'unserialize(') === false,
    'rate limiting' => strpos($source['api'], 'enforceRateLimit') !== false,
    'user existence' => strpos($source['user'], 'if (!is_array($user)') !== false,
    'global key removed' => strpos($source['crypt'], '__keyrightsClientKey') === false,
    'PBKDF2' => strpos($source['crypt'], 'PBKDF2') !== false,
    'CSV injection' => strpos($source['csv'], "[=+\\-@]") !== false,
    'password CSPRNG' => strpos($source['random'], '.getRandomValues') !== false,
    'nonce CSPRNG' => strpos($source['aes'], '.getRandomValues') !== false,
    'import size limit' => strpos($source['import'], 'MAX_SESSION_BYTES') !== false,
    'import TTL' => strpos($source['import'], 'SESSION_TTL') !== false,
    'encrypted import state' => strpos($source['import'], 'Crypt::encrypt(self::SESSION_PAYLOAD_PREFIX') !== false,
    'request cookies excluded' => strpos($source['api'], '$_REQUEST') === false,
    'invalid JSON rejected' => strpos($source['api'], 'json_last_error() !== JSON_ERROR_NONE') !== false,
    'JSON hardening headers' => strpos($source['api'], 'X-Content-Type-Options: nosniff') !== false,
    'deprecated mcrypt removed' => strpos(file_get_contents($root . '/lib/helper/crypt.php'), 'mcrypt_decrypt') === false,
    'strict ACL subject comparison' => strpos($source['rights'], 'in_array($group, $departmentIds, true)') !== false,
    'installer JSON encoding' => strpos($source['installerForm'], 'json_encode') !== false,
    'pinned checkout' => preg_match('/uses:\s+actions\/checkout@[0-9a-f]{40}/', $source['ci']) === 1,
    'pinned setup-node' => preg_match('/uses:\s+actions\/setup-node@[0-9a-f]{40}/', $source['release']) === 1,
    'pinned artifact' => preg_match('/uses:\s+actions\/upload-artifact@[0-9a-f]{40}/', $source['release']) === 1,
];

if (in_array(false, $checks, true)) {
    $failed = array_keys(array_filter($checks, function ($passed) {
        return !$passed;
    }));
    fwrite(STDERR, 'FAIL: security contract changed: ' . implode(', ', $failed) . "\n");
    exit(1);
}

fwrite(STDOUT, 'OK: security contract (' . count($checks) . " checks)\n");
