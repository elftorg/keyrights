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

}
