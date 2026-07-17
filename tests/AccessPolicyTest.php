<?php

require_once __DIR__ . '/../lib/security/accesspolicy.php';

use Drdroid\Keyrights\Security\AccessPolicy;

$assertions = 0;

$assert = static function ($condition, $message) use (&$assertions) {
    $assertions++;
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
};

$assert(!AccessPolicy::canRemoveGlobalRights(false), 'non-admin cannot remove global rights');
$assert(!AccessPolicy::canRemoveGlobalRights(1), 'truthy non-boolean admin flag is not trusted');
$assert(AccessPolicy::canRemoveGlobalRights(true), 'admin can remove global rights');
$assert(!AccessPolicy::canWriteItem(['SECTION' => 20], 21, 2), 'item cannot be updated through another section');
$assert(!AccessPolicy::canWriteItem(['SECTION' => 20], 20, 1), 'read access cannot update an item');
$assert(AccessPolicy::canWriteItem(['SECTION' => 20], 20, 2), 'write access updates only the matching item');
$assert(!AccessPolicy::isPublicIpv4('127.0.0.1'), 'loopback is blocked');
$assert(!AccessPolicy::isPublicIpv4('10.0.0.1'), 'private IPv4 is blocked');
$assert(AccessPolicy::isPublicIpv4('8.8.8.8'), 'public IPv4 is allowed');

fwrite(STDOUT, "OK: {$assertions} access-policy assertions\n");
