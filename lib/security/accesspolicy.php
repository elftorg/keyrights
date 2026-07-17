<?php
namespace Drdroid\Keyrights\Security;

final class AccessPolicy {
    public static function canRemoveGlobalRights($isAdmin) {
        return $isAdmin === true;
    }

    public static function canWriteItem(array $item, $targetSectionId, $accessLevel) {
        $targetSectionId = (int)$targetSectionId;

        return $targetSectionId > 0
            && isset($item['SECTION'])
            && (int)$item['SECTION'] === $targetSectionId
            && (int)$accessLevel >= 2;
    }

    public static function isPublicIpv4($address) {
        return filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
