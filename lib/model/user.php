<?php
namespace Drdroid\Keyrights\Model;

use CUser;
use CFile;
use Bitrix\Main\Config\Option;
use CIBlock;
use CIBlockSection;

class User {

    private static $departments = null;

    private $selectFields = [
        'ACTIVE',
        'EMAIL',
        'ID',
        'IS_ONLINE',
        'LAST_NAME',
        'LOGIN',
        'NAME',
        'PERSONAL_PHOTO',
        'SECOND_NAME',
        'TITLE',
    ];

    public static function getUserListById($userIdList = []) {
        $userIdList = array_values(array_unique(array_filter(array_map('intval', (array)$userIdList))));
        if (empty($userIdList)) {
            return [];
        }
        $order = "ID";
        $direction = "desc";
        $bxUser = new CUser();
        $res = $bxUser->GetList($order, $direction, ['ID' => implode('|', $userIdList)]);
        $userList = [];

        while ($user = $res->Fetch()) {
            $userList[$user['ID']] = $user;
        }

        return $userList;
    }

    public function getUserList($params = []) {
        $bxUser = new CUser();
        $field = strtoupper((string)($params['SORT'] ?? 'ID'));
        if (!in_array($field, $this->selectFields, true)) {
            $field = 'ID';
        }
        $order = strtoupper((string)($params['ORDER'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $res = $bxUser->GetList($field, $order, [], [
            'FIELDS' => $this->selectFields,
            'SELECT' => ['UF_DEPARTMENT']
        ]);
        $users = [];

        $departmentList = $this->getDepartments();

        $fileIds = [];
        while ($user = $res->Fetch()) {
            if (!empty($user['PERSONAL_PHOTO'])) {
                $fileIds[] = $user['PERSONAL_PHOTO'];
            }
            $user['UF_DEPARTMENT'] = $this->getAllDepartments($user['UF_DEPARTMENT'], $departmentList);
            $users[] = $user;
        }

        if (count($fileIds)) {
            $bxFile = new CFile();

            $files = [];
            foreach ($fileIds as $file) {
                $files[$file] = $bxFile->ResizeImageGet($file, ['width' => 26, 'height' => 26], BX_RESIZE_IMAGE_EXACT);
                $files[$file] = $files[$file]['src'];
            }

            foreach ($users as $ind => $user) {
                if (!empty($user['PERSONAL_PHOTO']) && isset($files[$user['PERSONAL_PHOTO']])) {
                    $users[$ind]['PERSONAL_PHOTO'] = $files[$user['PERSONAL_PHOTO']];
                } else {
                    $users[$ind]['PERSONAL_PHOTO'] = false;
                }
            }
        }

        return $users;
    }

    public function getCurrentUser() {
        global $USER;
        $userId = $USER->GetID();
        if (!$userId) {
            return false;
        }

        return $this->getUserById($userId);
    }

    public function getUserById($id) {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }
        $order = "ID";
        $direction = "desc";
        $bxUser = new CUser();
        $userRes = $bxUser->GetList($order, $direction, ['ID' => $id], [
            'FIELDS' => $this->selectFields,
            'SELECT' => ['UF_DEPARTMENT']
        ]);
        $user = $userRes->Fetch();

        if (!is_array($user) || (int)$user['ID'] !== (int)$id) {
            return false;
        }

        $departmentList = $this->getDepartments();
        $user['UF_DEPARTMENT'] = $this->getAllDepartments($user['UF_DEPARTMENT'], $departmentList);

        return $user;
    }

    public function getUserId() {
        global $USER;
        return $USER->GetID();
    }

    public function isAdmin($id = false) {
        if ($id) {
            if (false === array_search('1', CUser::GetUserGroup($id))) {
                return false;
            } else {
                return true;
            }
        }
        global $USER;
        return $USER->IsAdmin();
    }

    public function getUserData($id = false) {
        if (!$id) {
            $userData = $this->getCurrentUser();
        } else {
            $userData = $this->getUserById($id);
        }
        if (!$userData) {
            return false;
        }
        $userData['admin'] = $this->isAdmin($id);

        $departmentList = $this->getDepartments();
        $userData['UF_DEPARTMENT'] = $this->getAllDepartments($userData['UF_DEPARTMENT'], $departmentList);
        unset($userData['PASSWORD'], $userData['CHECKWORD'], $userData['CHECKWORD_TIME']);

        return $userData;
    }

    public function getDepartments() {
        if (is_null(self::$departments)) {
            self::$departments = $this->getDepartmentList();
        }

        return self::$departments;
    }

    private function getDepartmentList() {
        $iblockId = (int)Option::get('intranet', 'iblock_structure', '0');
        if (!$iblockId) {
            $bxIblock = new CIBlock();
            $res = $bxIblock->GetList([], ['CODE' => 'departments']);
            $iblock = $res->Fetch();
            $iblockId = is_array($iblock) ? $iblock['ID'] : 0;
        }

        if (!$iblockId) {
            return [];
        }

        $ibs = new CIBlockSection();
        $res = $ibs->GetList(['ID' => 'DESC'], ['IBLOCK_ID' => $iblockId]);

        $departments = [];
        while ($dep = $res->Fetch()) {
            $departments[] = [
                'ID' => $dep['ID'],
                'NAME' => $dep['NAME'],
                'PARENT' => $dep['IBLOCK_SECTION_ID']
            ];
        }
        return $departments;
    }

    private function getAllDepartments($userDeps, $depList) {
        $depListById = [];
        $allUserDeps = [];

        foreach ($depList as $dep) {
            $depListById[$dep['ID']] = $dep;
        }

        foreach ($depListById as $id => $dep) {
            $depListById[$id]['PARENT_LIST'] = $this->recursiveGetParents($dep['PARENT'], $depListById);
        }

        if ($userDeps) {
            foreach ($userDeps as $userDep) {
                if (isset($depListById[$userDep])) {
                    $allUserDeps[] = $userDep;
                    $allUserDeps = array_merge($allUserDeps, $depListById[$userDep]['PARENT_LIST']);
                }
            }
        }

        return array_values(array_unique($allUserDeps));
    }

    private function recursiveGetParents($parentId, $depList) {
        if (!$parentId) {
            return [];
        }

        $parentList = [(int)$parentId];

        if (isset($depList[$parentId]) && $depList[$parentId]['PARENT']) {
            $parentList = array_merge($parentList, $this->recursiveGetParents($depList[$parentId]['PARENT'], $depList));
        }

        return $parentList;
    }
}
