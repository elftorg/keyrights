<?php
namespace Drdroid\Keyrights\Model;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use CIBlockSection;
use CIBlockElement;
use Drdroid\Keyrights\Orm\ItemTable;
use Drdroid\Keyrights\Orm\RightTable;
use Drdroid\Keyrights\Helper\Crypt;

class RightManager {

    const ACCESS_NO        = 0;
    const ACCESS_CAN_READ  = 1;
    const ACCESS_CAN_WRITE = 2;
    const ACCESS_CAN_OWN   = 3;

    private $iblockId;
    private $ibs;
    private $ibe;

    public function __construct() {
        $this->iblockId = Option::get('drdroid.keyrights', 'iblockId', '-1');
        $this->ibs = new CIBlockSection();
        $this->ibe = new CIBlockElement();
    }

    public static function getIblockId() {
        return Option::get('drdroid.keyrights', 'iblockId', '-1');
    }

    // ----------------------------------------------------
    // SECTIONS
    // ----------------------------------------------------

    public function addSection($params) {
        $userModel = new User();
        $fields = [
            "IBLOCK_ID"         => $this->iblockId,
            "CREATED_BY"        => $userModel->getUserId(),
            "NAME"              => $params['NAME'],
            "IBLOCK_SECTION_ID" => (int)($params['IBLOCK_SECTION_ID'] ?? $params['SECTION'] ?? 0),
            "DESCRIPTION"       => (string)($params['DESCRIPTION'] ?? ''),
        ];

        return $this->ibs->Add($fields);
    }

    public function getSection($params = []) {
        $sort = !empty($params['SORT']) ? $params['SORT'] : ['ID' => 'ASC'];
        $filter = ['IBLOCK_ID' => $this->iblockId, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'];
        if (isset($params['ID'])) {
            $filter['ID'] = (int)$params['ID'];
        }
        $select = ['ID', 'IBLOCK_SECTION_ID', 'NAME', 'DATE_CREATE', 'TIMESTAMP_X', 'DESCRIPTION', 'CREATED_BY'];

        $res = $this->ibs->GetList($sort, $filter, false, $select);

        $result = [];
        while ($section = $res->Fetch()) {
            $section['SECTION']  = $section['IBLOCK_SECTION_ID'] ? $section['IBLOCK_SECTION_ID'] : 0;
            $section['DATE_CREATE'] = date('c', strtotime($section['DATE_CREATE']));
            $section['TIMESTAMP_X'] = date('c', strtotime($section['TIMESTAMP_X']));
            $result[] = $section;
        }
        return $result;
    }

    public function getSectionHierarchy() {
        $sectionList = $this->getSection();
        $hierarchy = [];
        foreach ($sectionList as $section) {
            $hierarchy[$section['ID']] = [
                'parent' => $section['SECTION'] ? (int)$section['SECTION'] : 0,
                'owner' => (int)$section['CREATED_BY'],
            ];
        }

        return $hierarchy;
    }

    /**
     * Return only folders visible to the selected subject. Ancestors required
     * to render the tree are represented by anonymous placeholders. ACL rows
     * are exposed only for folders the subject is allowed to administer.
     */
    public function getAccessibleSectionsTree($includeRoot = false, $forId = false, $isGroup = false) {
        $sections = $this->getSection();
        $hierarchy = [];
        $byId = [];
        foreach ($sections as $section) {
            $id = (int)$section['ID'];
            $byId[$id] = $section;
            $hierarchy[$id] = [
                'parent' => (int)$section['SECTION'],
                'owner' => (int)$section['CREATED_BY'],
            ];
        }

        $userModel = new User();
        $user = $userModel->getUserData($isGroup ? false : $forId);
        if ($forId && $isGroup) {
            $user = ['ID' => -1, 'admin' => false, 'UF_DEPARTMENT' => [(int)$forId]];
        }
        if (!$user) {
            return [];
        }

        $rights = $this->getRightsList();
        $passes = [];
        foreach ($sections as $section) {
            $passes[] = ['ID' => -((int)$section['ID'] + 1), 'SECTION' => (int)$section['ID']];
        }
        $checked = $this->doRecursiveCheckEntitiesRights($hierarchy, $rights, $user, $passes);
        $levels = [];
        foreach ($checked as $pass) {
            $levels[(int)$pass['SECTION']] = (int)$pass['level'];
        }

        $included = [];
        foreach (array_keys($levels) as $id) {
            $included[$id] = true;
            $visited = [];
            $parent = isset($hierarchy[$id]) ? (int)$hierarchy[$id]['parent'] : 0;
            while ($parent > 0 && isset($hierarchy[$parent]) && !isset($visited[$parent])) {
                $visited[$parent] = true;
                $included[$parent] = true;
                $parent = (int)$hierarchy[$parent]['parent'];
            }
        }

        $preparedRights = $this->prepareRights($rights);
        $result = [];
        foreach ($sections as $section) {
            $id = (int)$section['ID'];
            if (!isset($included[$id])) {
                continue;
            }
            if (!isset($levels[$id])) {
                $result[] = [
                    'ID' => $id,
                    'SECTION' => (int)$section['SECTION'],
                    'NAME' => '…',
                    'DESCRIPTION' => '',
                    'CREATED_BY' => 0,
                    'OWNER' => 0,
                    'RIGHTS' => [],
                    'IS_PLACEHOLDER' => true,
                ];
                continue;
            }

            $level = $levels[$id];
            $anchor = $preparedRights['section'][$id] ?? null;
            $section['OWNER'] = $anchor ? (int)$anchor['owner'] : (int)$section['CREATED_BY'];
            $section['RIGHTS'] = $level >= self::ACCESS_CAN_WRITE && $anchor ? $anchor['rights'] : [];
            $section['CAN_READ'] = $level >= self::ACCESS_CAN_READ;
            $section['CAN_WRITE'] = $level >= self::ACCESS_CAN_WRITE;
            $section['CAN_OWN'] = $level >= self::ACCESS_CAN_OWN;
            $result[] = $section;
        }

        if ($includeRoot) {
            $result[] = [
                'ID' => 0,
                'SECTION' => false,
                'NAME' => 'Корневая папка',
                'CREATED_BY' => $user['admin'] ? 1 : 0,
                'OWNER' => $user['admin'] ? 1 : 0,
                'DESCRIPTION' => '',
                'RIGHTS' => [],
                'CAN_READ' => true,
                'CAN_WRITE' => (bool)$user['admin'],
                'CAN_OWN' => (bool)$user['admin'],
            ];
        }

        return $result;
    }

    public function updateSection($params) {
        $id = (int)$params['ID'];

        $existing = $this->getSection(['ID' => $id]);
        if (!$existing) {
            return false;
        }

        $fields = [];
        if (!empty($params['NAME'])) {
            $fields['NAME'] = $params['NAME'];
        }
        if (isset($params['SECTION'])) {
            $fields['IBLOCK_SECTION_ID'] = $params['SECTION'];
        }
        if (isset($params['DESCRIPTION'])) {
            $fields['DESCRIPTION'] = $params['DESCRIPTION'];
        }

        return $this->ibs->Update($id, $fields);
    }

    public function isValidSectionMove($sectionId, $newParentId) {
        $sectionId = (int)$sectionId;
        $newParentId = (int)$newParentId;
        if ($sectionId <= 0 || $newParentId === $sectionId || $newParentId < 0) {
            return false;
        }
        if ($newParentId === 0) {
            return true;
        }

        $visited = [];
        $currentId = $newParentId;
        while ($currentId > 0 && !isset($visited[$currentId])) {
            if ($currentId === $sectionId) {
                return false;
            }
            $visited[$currentId] = true;
            $current = reset($this->getSection(['ID' => $currentId]));
            if (!$current) {
                return false;
            }
            $currentId = (int)$current['SECTION'];
        }

        return $currentId === 0;
    }

    public function deleteSection($params) {
        $id = (int)$params['ID'];

        $res = $this->ibs->GetList([], ['IBLOCK_ID' => $this->iblockId, 'ID' => $id]);
        $section = $res->Fetch();

        if ($section) {
            return (bool)$this->ibs->Delete($id);
        }

        return false;
    }

    // ----------------------------------------------------
    // ITEMS (PASSWORDS)
    // ----------------------------------------------------

    public function addItem($params) {
        $userModel = new User();

        $data = [
            'CRYPTED' => ["VALUE" => ["TYPE" => "TEXT", "TEXT" => Crypt::encrypt($params['PROPERTY_VALUES']['CRYPTED'])]]
        ];
        $fields = [
            "IBLOCK_ID"         => $this->iblockId,
            "CREATED_BY"        => $userModel->getUserId(),
            "NAME"              => $params['NAME'],
            "IBLOCK_SECTION_ID" => (int)$params['SECTION'],
            "PROPERTY_VALUES"   => $data,
        ];

        return $this->ibe->Add($fields);
    }

    public function getItem($params = []) {
        $sort = !empty($params['SORT']) ? $params['SORT'] : ['ID' => 'ASC'];
        $filter = [
            'ACTIVE' => 'Y',
            'GLOBAL_ACTIVE' => 'Y',
            'SECTION_ACTIVE' => 'Y',
            'SECTION_GLOBAL_ACTIVE' => 'Y',
            'IBLOCK_ID' => $this->iblockId
        ];

        $decrypt = !array_key_exists('DECRYPT', $params) || $params['DECRYPT'] !== false;
        if (is_array($params['ID'] ?? null)) {
            $filter['@ID'] = array_map('intval', $params['ID']);
        } elseif (!empty($params['ID'])) {
            $filter['ID'] = (int)$params['ID'];
        }

        $res = $this->ibe->GetList($sort, $filter, false, false, ['*', 'PROPERTY_CRYPTED', 'PREVIEW_TEXT']);

        $result = [];
        $fields = array_flip($this->getItemFields());
        while ($entity = $res->Fetch()) {
            $entity['SECTION']  = $entity['IBLOCK_SECTION_ID'];
            $entity['DATE_CREATE'] = date('c', strtotime($entity['DATE_CREATE']));
            $entity['TIMESTAMP_X'] = date('c', strtotime($entity['TIMESTAMP_X']));
            $entity['COLOR'] = $entity['PREVIEW_TEXT'];
            
            if ($decrypt) {
                $cryptedText = $this->extractHtmlPropertyText(
                    isset($entity['PROPERTY_CRYPTED_VALUE'])
                        ? $entity['PROPERTY_CRYPTED_VALUE']
                        : ''
                );
                $entity['CRYPTED'] = $cryptedText !== '' ? Crypt::decrypt($cryptedText) : '';
            } else {
                // Permission checks need only IDs and sections. Do not
                $entity['CRYPTED'] = '';
            }
            
            unset($entity['PROPERTY_CRYPTED_VALUE']);
            $entity = array_intersect_key($entity, $fields);
            $result[] = $entity;
        }

        return $result;
    }

    public function updateItem($params) {
        $id = (int)$params['ID'];

        $existing = $this->ibe->GetList([], ['IBLOCK_ID' => $this->iblockId, 'ID' => $id]);
        if (!$existing->Fetch()) {
            return false;
        }

        $fields = [
            "IBLOCK_SECTION_ID" => (int)$params['SECTION'],
        ];

        if (!empty($params['NAME'])) {
            $fields['NAME'] = $params['NAME'];
        }
        if (isset($params['PREVIEW_TEXT'])) {
            $fields['PREVIEW_TEXT'] = $params['PREVIEW_TEXT'];
        }

        if (!empty($params['PROPERTY_VALUES'])) {
            $data = [
                'CRYPTED' => ["VALUE" => ["TYPE" => "TEXT", "TEXT" => Crypt::encrypt($params['PROPERTY_VALUES']['CRYPTED'])]]
            ];
            $fields['PROPERTY_VALUES'] = $data;
        }

        return $this->ibe->Update($id, $fields);
    }

    public function deleteItem($params) {
        $id = (int)$params['ID'];

        $res = $this->ibe->GetList([], ['IBLOCK_ID' => $this->iblockId, 'ID' => $id]);
        $element = $res->Fetch();

        if ($element) {
            $this->ibe->Delete($id);
            return true;
        }

        return false;
    }

    /** Update the owner of exactly one access anchor. */
    public function setOwner($sectionId, $entityId, $ownerId) {
        $filter = $entityId
            ? ['=ENTITY_ID' => (int)$entityId]
            : ['=SECTION_ID' => (int)$sectionId];
        $row = ItemTable::getList([
            'select' => ['ID'],
            'filter' => $filter,
            'limit' => 1,
        ])->fetch();

        if (!$row) {
            return false;
        }

        return ItemTable::update((int)$row['ID'], ['OWNER' => (int)$ownerId])->isSuccess();
    }

    public function getPassCount() {
        return $this->ibe->GetList([], ['IBLOCK_ID' => $this->iblockId], []);
    }

    /**
     * Migrate a bounded batch of legacy Blowfish envelopes to v2.
     * The operation is intentionally explicit and admin-only at the API
     * boundary; reads never rewrite data implicitly.
     */
    public function migrateLegacyItems($limit = 100) {
        $limit = max(1, min(500, (int)$limit));
        $res = $this->ibe->GetList(
            ['ID' => 'ASC'],
            ['IBLOCK_ID' => $this->iblockId, 'ACTIVE' => 'Y'],
            false,
            ['nTopCount' => $limit],
            ['ID', 'PROPERTY_CRYPTED']
        );

        $migrated = 0;
        $skipped = 0;
        $errors = [];
        while ($row = $res->Fetch()) {
            $value = $this->extractHtmlPropertyText($row['PROPERTY_CRYPTED_VALUE'] ?? '');
            if (!Crypt::isLegacy($value)) {
                $skipped++;
                continue;
            }

            try {
                $newValue = Crypt::migrate($value);
                if (!$this->ibe->Update((int)$row['ID'], [
                    'PROPERTY_VALUES' => [
                        'CRYPTED' => ['VALUE' => ['TYPE' => 'TEXT', 'TEXT' => $newValue]],
                    ],
                ])) {
                    throw new \RuntimeException((string)$this->ibe->LAST_ERROR);
                }
                $migrated++;
            } catch (\Throwable $e) {
                $errors[] = ['id' => (int)$row['ID'], 'message' => $e->getMessage()];
            }
        }

        return [
            'migrated' => $migrated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    private function getItemFields() {
        return [
            'ID',
            'SECTION',
            'NAME',
            'TIMESTAMP_X',
            'DATE_CREATE',
            'CREATED_BY',
            'CRYPTED',
            'COLOR'
        ];
    }

    private function extractHtmlPropertyText($value) {
        for ($depth = 0; $depth < 3; $depth++) {
            if (is_array($value)) {
                if (array_key_exists('TEXT', $value)) {
                    $value = $value['TEXT'];
                    continue;
                }

                if (array_key_exists('VALUE', $value)) {
                    $value = $value['VALUE'];
                    continue;
                }

                return '';
            }

            if (!is_string($value) || $value === '') {
                return '';
            }

            $legacyText = $this->extractLegacySerializedText($value);
            if ($legacyText !== null) {
                $value = $legacyText;
                continue;
            }

            return $value;
        }

        return is_scalar($value) ? (string)$value : '';
    }

    /**
     * Read only the string value of the TEXT field from a legacy serialized
     * Bitrix HTML property. This deliberately does not invoke PHP's generic
     * deserializer, instantiate objects, or interpret references and types.
     */
    private function extractLegacySerializedText($value) {
        if (!is_string($value) || strlen($value) > 2097152) {
            return null;
        }
        // A zero-width lookahead is intentional: TYPE => TEXT and the next
        // TEXT key overlap in PHP's serialized representation.
        if (!preg_match_all('/(?=((?:^|[;{])s:4:"TEXT";s:(\d+):"))/', $value, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        // A normal Bitrix HTML property also contains TYPE => TEXT before the
        // actual TEXT field. Walk backwards so that value is not mistaken for
        // the field we need.
        for ($index = count($matches[1]) - 1; $index >= 0; $index--) {
            $length = (int)$matches[2][$index][0];
            if ($length < 0 || $length > 1048576) {
                continue;
            }
            $start = $matches[1][$index][1] + strlen($matches[1][$index][0]);
            $text = substr($value, $start, $length);
            if (strlen($text) === $length && substr($value, $start + $length, 2) === '";') {
                return $text;
            }
        }

        return null;
    }

    // ----------------------------------------------------
    // PERMISSIONS LOGIC
    // ----------------------------------------------------

    public function getRightsList() {
        $items = [];
        $res = ItemTable::getList([
            'select' => ['ID', 'ENTITY_ID', 'SECTION_ID', 'OWNER'],
            'order' => ['ID' => 'ASC'],
        ]);
        while ($row = $res->fetch()) {
            $itemId = (int)$row['ID'];
            $items[$itemId] = [
                'id' => $itemId,
                'entity_id' => $row['ENTITY_ID'] ? (int)$row['ENTITY_ID'] : null,
                'section_id' => $row['SECTION_ID'] !== null ? (int)$row['SECTION_ID'] : null,
                'owner' => (int)$row['OWNER'],
                'rights' => [],
            ];
        }

        if (empty($items)) {
            return [];
        }

        $rightRes = RightTable::getList([
            'select' => ['ID', 'ITEM_ID', 'EDIT', 'BLOCKED', 'TIMED', 'USER', 'GROUP'],
            'order' => ['ID' => 'ASC'],
        ]);
        while ($right = $rightRes->fetch()) {
            $itemId = (int)$right['ITEM_ID'];
            if (isset($items[$itemId])) {
                $timed = $right['TIMED'];
                if ($timed instanceof \DateTimeInterface) {
                    $timed = $timed->format('Y-m-d H:i:s');
                }
                $items[$itemId]['rights'][] = [
                    'id' => (int)$right['ID'],
                    'item_id' => $itemId,
                    'edit' => (int)$right['EDIT'],
                    'blocked' => (int)$right['BLOCKED'],
                    'timed' => $timed,
                    'user' => $right['USER'] ? (int)$right['USER'] : null,
                    'group' => $right['GROUP'] ? (int)$right['GROUP'] : null,
                ];
            }
        }

        return $items;
    }

    public function getRight($where = []) {
        $filter = [];
        foreach ($where as $fieldExpr => $value) {
            $field = strtoupper(trim(explode('=', $fieldExpr)[0]));
            if ($field === 'ENTITY_ID') {
                $filter['=ENTITY_ID'] = $value === null ? null : (int)$value;
            } elseif ($field === 'SECTION_ID') {
                $filter['=SECTION_ID'] = $value === null ? null : (int)$value;
            } elseif ($field === 'ID') {
                $filter['=ID'] = (int)$value;
            }
        }

        $row = ItemTable::getList([
            'select' => ['ID', 'ENTITY_ID', 'SECTION_ID', 'OWNER'],
            'filter' => $filter,
            'limit' => 1,
        ])->fetch();
        if (!$row) {
            return null;
        }

        return $this->loadRightRow($row);
    }

    public function createRightRow($sectionId, $elementId, $owner) {
        $result = ItemTable::add([
            'SECTION_ID' => $sectionId ? (int)$sectionId : null,
            'ENTITY_ID' => $elementId ? (int)$elementId : null,
            'OWNER' => (int)$owner,
        ]);

        if ($result->isSuccess()) {
            $lastId = (int)$result->getId();
            return [
                'id' => $lastId,
                'section_id' => $sectionId ? (int)$sectionId : null,
                'entity_id' => $elementId ? (int)$elementId : null,
                'owner' => (int)$owner,
                'rights' => []
            ];
        }
        // A concurrent request may have created the row. Return that row
        // instead of creating duplicate permission anchors.
        return $this->getRight($elementId ? ['entity_id = ?' => $elementId] : ['section_id = ?' => $sectionId]);
    }

    public function deleteItemRights($entityId) {
        $entityId = intval($entityId);

        $connection = Application::getConnection();
        $connection->startTransaction();
        try {
            $res = ItemTable::getList(['select' => ['ID'], 'filter' => ['=ENTITY_ID' => $entityId]]);
            while ($row = $res->fetch()) {
                $this->deleteRightsByItemId((int)$row['ID']);
                ItemTable::delete((int)$row['ID']);
            }
            $connection->commitTransaction();
        } catch (\Throwable $e) {
            $connection->rollbackTransaction();
            throw $e;
        }
    }

    public function getSectionSubtreeIds($sectionId) {
        $sectionId = (int)$sectionId;
        $hierarchy = $this->getSectionHierarchy();
        $result = [$sectionId => $sectionId];
        do {
            $added = false;
            foreach ($hierarchy as $id => $section) {
                if (!isset($result[$id]) && isset($result[(int)$section['parent']])) {
                    $result[$id] = (int)$id;
                    $added = true;
                }
            }
        } while ($added);
        return array_values($result);
    }

    public function deleteSectionRightsByIds(array $sectionIds, array $entityIds = []) {
        $sectionIds = array_values(array_unique(array_filter(array_map('intval', $sectionIds))));
        $entityIds = array_values(array_unique(array_filter(array_map('intval', $entityIds))));
        if (empty($sectionIds) && empty($entityIds)) {
            return true;
        }
        $connection = Application::getConnection();
        $connection->startTransaction();
        try {
            $filter = ['LOGIC' => 'OR'];
            if (!empty($sectionIds)) $filter[] = ['@SECTION_ID' => $sectionIds];
            if (!empty($entityIds)) $filter[] = ['@ENTITY_ID' => $entityIds];
            $res = ItemTable::getList(['select' => ['ID'], 'filter' => $filter]);
            while ($row = $res->fetch()) {
                $this->deleteRightsByItemId((int)$row['ID']);
                $result = ItemTable::delete((int)$row['ID']);
                if (!$result->isSuccess()) {
                    throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
                }
            }
            $connection->commitTransaction();
            return true;
        } catch (\Throwable $e) {
            $connection->rollbackTransaction();
            throw $e;
        }
    }

    public function checkRightsLevel($passData) {
        $result = $this->checkEntitiesRights($passData);
        if (count($result) !== count($passData) || empty($result)) {
            return self::ACCESS_NO;
        }
        return min(array_map(function ($item) {
            return (int)$item['level'];
        }, $result));
    }

    public function checkEntitiesRights($passData, $forId = false, $isGroup = false) {
        $userModel = new User();
        $itemList = $this->getRightsList();
        $sectionTree = $this->getSectionHierarchy();
        $user = $userModel->getUserData($isGroup ? false : $forId);
        if ($forId && $isGroup) {
            $user = [
                'ID' => -1,
                'admin' => false,
                'UF_DEPARTMENT' => [(int)$forId]
            ];
        }
        return $this->doRecursiveCheckEntitiesRights($sectionTree, $itemList, $user, $passData);
    }

    public function doRecursiveCheckEntitiesRights($sectionsTree, $rights, $user, $passData) {
        $filteredData = [];
        if (!$user) return $filteredData;
        $prepared = $this->prepareRights($rights);

        foreach ($passData as $i => $pass) {
            $hasAccess = false;

            if ($user['admin']) {
                $hasAccess = self::ACCESS_CAN_OWN;
            }

            // Проверка прав конкретно на этот пароль
            if (($hasAccess === false) && isset($prepared['item'][$pass['ID']])) {
                $hasAccess = $this->checkOneRight($prepared['item'][$pass['ID']], $user);
            }

            // Рекурсивно на папку
            if ($hasAccess === false) {
                $currentSectionId = (int)$pass['SECTION'];
                while ($currentSectionId > 0) {
                    if (isset($prepared['section'][$currentSectionId])) {
                        $hasAccess = $this->checkOneRight($prepared['section'][$currentSectionId], $user);
                        if ($hasAccess !== false) break;
                    }

                    $currentSectionId = isset($sectionsTree[$currentSectionId]) ? (int)$sectionsTree[$currentSectionId]['parent'] : 0;
                }
            }

            $passData[$i]['level'] = $hasAccess;
            if ($hasAccess) {
                if ($hasAccess >= self::ACCESS_CAN_READ ) $passData[$i]['CAN_READ']  = true;
                if ($hasAccess >= self::ACCESS_CAN_WRITE) $passData[$i]['CAN_WRITE'] = true;
                if ($hasAccess >= self::ACCESS_CAN_OWN  ) $passData[$i]['CAN_OWN']   = true;
                $filteredData[] = $passData[$i];
            }
        }

        return $filteredData;
    }

    protected function checkOneRight($right, $user) {
        $userId = (int)$user['ID'];
        if ((int)$right['owner'] === $userId) return self::ACCESS_CAN_OWN;
        $rights = $right['rights'];
        if (empty($rights)) return false;

        // Explicit deny wins among duplicate legacy rows. New writes reject
        // duplicates, but old installations may still contain them.
        $userLevel = false;
        foreach ($rights as $oneRight) {
            if (!$oneRight['user']) continue;
            if (!$this->checkRightTime($oneRight['timed'])) continue;

            if ((int)$oneRight['user'] === $userId) {
                if ($oneRight['blocked']) return self::ACCESS_NO;
                $level = $oneRight['edit'] ? self::ACCESS_CAN_WRITE : self::ACCESS_CAN_READ;
                $userLevel = $userLevel === false ? $level : max($userLevel, $level);
            }
        }
        if ($userLevel !== false) return $userLevel;

        // right to group
        $rightByGroup = false;
        $departmentIds = array_map('intval', (array)($user['UF_DEPARTMENT'] ?? []));
        foreach ($rights as $oneRight) {
            if (!$oneRight['group']) {
                continue;
            }

            if (!$this->checkRightTime($oneRight['timed'])) {
                continue;
            }

            $group = (int)$oneRight['group'];

            // Если разрешено хоть одной группе, в которой состоит пользователь
            if (in_array($group, $departmentIds, true)) {
                if ($oneRight['blocked']) {
                    return self::ACCESS_NO;
                }
                $currentRight = $oneRight['edit'] ? self::ACCESS_CAN_WRITE : self::ACCESS_CAN_READ;
                $rightByGroup = $rightByGroup === false ? $currentRight : max($rightByGroup, $currentRight);
            }
        }

        return $rightByGroup;
    }

    protected function prepareRights($rights) {
        $prepared = [
            'section' => [],
            'item' => [],
        ];

        foreach ($rights as $item) {
            if ($item['entity_id']) {
                $prepared['item'][$item['entity_id']] = $item;
            } elseif ($item['section_id'] !== null) {
                $prepared['section'][$item['section_id']] = $item;
            }
        }

        return $prepared;
    }

    protected function checkRightTime($time) {
        if (!$time) {
            return true;
        }

        return strtotime($time) >= time();
    }

    public function changeOwnerToCurrentUser($usersId = []) {
        if (!empty($usersId)) {
            $userModel = new User();
            $currentUser = $userModel->getCurrentUser();
            if (!$currentUser) {
                return false;
            }

            $res = ItemTable::getList([
                'select' => ['ID'],
                'filter' => ['@OWNER' => array_map('intval', $usersId)],
            ]);
            while ($row = $res->fetch()) {
                $result = ItemTable::update((int)$row['ID'], ['OWNER' => (int)$currentUser['ID']]);
                if (!$result->isSuccess()) {
                    throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
                }
            }
            return true;
        }

        return false;
    }

    public function saveEntityRights($sectionId, $entityId, $rights) {
        $item = $this->getRight($entityId
            ? ['entity_id = ?' => (int)$entityId]
            : ['section_id = ?' => (int)$sectionId]);
        
        if (!$item) {
            return false;
        }
        $itemId = $item['id'];

        $rights = $this->validateRights($rights);

        $connection = Application::getConnection();
        $connection->startTransaction();
        try {
            $this->deleteRightsByItemId((int)$itemId);

            foreach ($rights as $newRight) {
                if (!is_array($newRight)) {
                    throw new \InvalidArgumentException('Invalid right row');
                }
                $timed = null;
                if (!empty($newRight['timed'])) {
                    $timed = \Bitrix\Main\Type\DateTime::createFromTimestamp((int)$newRight['timed']);
                }
                $result = RightTable::add([
                    'ITEM_ID' => (int)$itemId,
                    'EDIT' => !empty($newRight['edit']) ? 1 : 0,
                    'BLOCKED' => !empty($newRight['blocked']) ? 1 : 0,
                    'TIMED' => $timed,
                    'USER' => !empty($newRight['user']) ? (int)$newRight['user'] : null,
                    'GROUP' => !empty($newRight['group']) ? (int)$newRight['group'] : null,
                ]);
                if (!$result->isSuccess()) {
                    throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
                }
            }

            $connection->commitTransaction();
            return true;
        } catch (\Throwable $e) {
            $connection->rollbackTransaction();
            throw $e;
        }
    }

    public function currentUserCanManageRights() {
        return $this->currentUserHasAccess(self::ACCESS_CAN_WRITE);
    }

    public function currentUserCanUseVault() {
        return $this->currentUserHasAccess(self::ACCESS_CAN_READ);
    }

    private function currentUserHasAccess($minimumLevel) {
        $userModel = new User();
        if ($userModel->isAdmin()) {
            return true;
        }
        $passes = [];
        foreach ($this->getSection() as $section) {
            $passes[] = ['ID' => -((int)$section['ID'] + 1), 'SECTION' => (int)$section['ID']];
        }
        foreach ($this->getItem(['DECRYPT' => false]) as $item) {
            $passes[] = $item;
        }
        foreach ($this->checkEntitiesRights($passes) as $checked) {
            if ((int)$checked['level'] >= (int)$minimumLevel) {
                return true;
            }
        }
        return false;
    }

    private function validateRights($rights) {
        if (!is_array($rights) || count($rights) > 500) {
            throw new \InvalidArgumentException('Invalid rights collection');
        }

        $users = [];
        $groups = [];
        $seen = [];
        $validated = [];
        foreach ($rights as $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException('Invalid right row');
            }
            $userId = (int)($row['user'] ?? 0);
            $groupId = (int)($row['group'] ?? 0);
            if (($userId > 0) === ($groupId > 0)) {
                throw new \InvalidArgumentException('A right must contain exactly one subject');
            }
            $subject = $userId > 0 ? 'u:' . $userId : 'g:' . $groupId;
            if (isset($seen[$subject])) {
                throw new \InvalidArgumentException('Duplicate right subject');
            }
            $seen[$subject] = true;
            if ($userId > 0) $users[$userId] = $userId;
            if ($groupId > 0) $groups[$groupId] = $groupId;

            $timestamp = null;
            if (!empty($row['timed'])) {
                $timestamp = strtotime((string)$row['timed']);
                if ($timestamp === false || $timestamp <= time()) {
                    throw new \InvalidArgumentException('Invalid right expiration time');
                }
            }
            $validated[] = [
                'edit' => !empty($row['edit']) ? 1 : 0,
                'blocked' => !empty($row['blocked']) ? 1 : 0,
                'timed' => $timestamp,
                'user' => $userId > 0 ? $userId : null,
                'group' => $groupId > 0 ? $groupId : null,
            ];
        }

        $knownUsers = User::getUserListById(array_values($users));
        foreach ($users as $id) {
            if (!isset($knownUsers[$id]) || ($knownUsers[$id]['ACTIVE'] ?? 'N') !== 'Y') {
                throw new \InvalidArgumentException('Unknown or inactive user');
            }
        }
        if (!empty($groups)) {
            $knownGroups = [];
            foreach ((new User())->getDepartments() as $department) {
                $knownGroups[(int)$department['ID']] = true;
            }
            foreach ($groups as $id) {
                if (!isset($knownGroups[$id])) {
                    throw new \InvalidArgumentException('Unknown department');
                }
            }
        }

        return $validated;
    }

    public function deleteAll($elements = []) {
        $usersId = [];
        $groupsId = [];

        foreach ($elements as $element) {
            if ($element['isGroup']) {
                $groupsId[] = (int)$element['id'];
            } else {
                $usersId[] = (int)$element['id'];
            }
        }

        $connection = Application::getConnection();
        $connection->startTransaction();
        try {
            if (!empty($usersId)) {
                if (!$this->changeOwnerToCurrentUser($usersId)) {
                    throw new \RuntimeException('Current user is not available');
                }
                $this->deleteRightsBySubjects('USER', $usersId);
            }

            if (!empty($groupsId)) {
                $this->deleteRightsBySubjects('GROUP', $groupsId);
            }

            $connection->commitTransaction();
            return true;
        } catch (\Throwable $e) {
            $connection->rollbackTransaction();
            throw $e;
        }
    }

    private function deleteRightsBySubjects($field, array $ids) {
        $filter = ['@' . $field => array_map('intval', $ids)];
        $res = RightTable::getList(['select' => ['ID'], 'filter' => $filter]);
        while ($row = $res->fetch()) {
            $result = RightTable::delete((int)$row['ID']);
            if (!$result->isSuccess()) {
                throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
            }
        }
    }

    private function loadRightRow(array $row) {
        $itemId = (int)$row['ID'];
        $result = [
            'id' => $itemId,
            'entity_id' => $row['ENTITY_ID'] ? (int)$row['ENTITY_ID'] : null,
            'section_id' => $row['SECTION_ID'] !== null ? (int)$row['SECTION_ID'] : null,
            'owner' => (int)$row['OWNER'],
            'rights' => [],
        ];
        $rights = RightTable::getList([
            'select' => ['ID', 'ITEM_ID', 'EDIT', 'BLOCKED', 'TIMED', 'USER', 'GROUP'],
            'filter' => ['=ITEM_ID' => $itemId],
            'order' => ['ID' => 'ASC'],
        ]);
        while ($right = $rights->fetch()) {
            $timed = $right['TIMED'];
            if ($timed instanceof \DateTimeInterface) {
                $timed = $timed->format('Y-m-d H:i:s');
            }
            $result['rights'][] = [
                'id' => (int)$right['ID'],
                'item_id' => $itemId,
                'edit' => (int)$right['EDIT'],
                'blocked' => (int)$right['BLOCKED'],
                'timed' => $timed,
                'user' => $right['USER'] ? (int)$right['USER'] : null,
                'group' => $right['GROUP'] ? (int)$right['GROUP'] : null,
            ];
        }

        return $result;
    }

    private function deleteRightsByItemId($itemId) {
        $res = RightTable::getList([
            'select' => ['ID'],
            'filter' => ['=ITEM_ID' => (int)$itemId],
        ]);
        while ($row = $res->fetch()) {
            $result = RightTable::delete((int)$row['ID']);
            if (!$result->isSuccess()) {
                throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
            }
        }
    }
}
