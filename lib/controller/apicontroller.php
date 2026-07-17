<?php
namespace Drdroid\Keyrights\Controller;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Drdroid\Keyrights\Model\RightManager;
use Drdroid\Keyrights\Model\User;
use Drdroid\Keyrights\Model\History;
use Drdroid\Keyrights\Model\Import;
use Drdroid\Keyrights\Security\AccessPolicy;

class ApiController extends Controller {
    private $isWin1251 = false;
    private $faviconResolve = [];

    private const WRITE_ACTIONS = [
        'crypt/section/save',
        'crypt/section/move',
        'crypt/section/remove',
        'crypt/password/save',
        'crypt/password/move',
        'crypt/password/remove',
        'crypt/rights/list',
        'crypt/rights/save',
        'crypt/rights/remove',
        'api/call-method',
        'crypt/set-owner',
        'api/request-access',
        'exchange/import',
        'exchange/history',
        'exchange/copy',
        'exchange/crypto-migrate',
    ];

    public function __construct() {
        parent::__construct();
        $this->isWin1251 = defined('SITE_CHARSET') && (strtoupper(SITE_CHARSET) == 'WINDOWS-1251');
    }

    public function configureActions() {
        return [
            'read' => [
                'prefilters' => [
                    new Authentication(false),
                    new HttpMethod([HttpMethod::METHOD_GET, HttpMethod::METHOD_POST]),
                ],
            ],
            'write' => [
                'prefilters' => [
                    new Authentication(false),
                    new HttpMethod([HttpMethod::METHOD_POST]),
                    new Csrf(true, 'csrf_token', false),
                ],
            ],
        ];
    }

    public function dispatch($action) {
        $action = trim((string)$action, '/');
        $engineAction = in_array($action, self::WRITE_ACTIONS, true) ? 'write' : 'read';
        $result = $this->run($engineAction, [['route' => $action]]);

        // Legacy action methods terminate with a JSON response for backward
        // compatibility. Filters, however, return a regular controller error.
        if ($result === null && $this->getErrors()->count() > 0) {
            $error = reset($this->getErrors()->toArray());
            $status = in_array($error->getCode(), [
                Authentication::ERROR_INVALID_AUTHENTICATION,
            ], true) ? 401 : 403;
            $this->sendJsonError($error->getMessage(), $status);
        }

        return $result;
    }

    public function readAction($route) {
        $this->route((string)$route);
    }

    public function writeAction($route) {
        $this->route((string)$route);
    }

    private function route($action) {
        switch ($action) {
            case 'crypt/section/list':
                $this->sectionListAction();
                break;
            case 'crypt/section/save':
                $this->sectionSaveAction();
                break;
            case 'crypt/section/move':
                $this->sectionMoveAction();
                break;
            case 'crypt/section/remove':
                $this->sectionRemoveAction();
                break;
            case 'crypt/password/list':
                $this->passwordListAction();
                break;
            case 'crypt/password/list-for-id':
                $this->passwordListForIdAction();
                break;
            case 'crypt/password/save':
                $this->passwordSaveAction();
                break;
            case 'crypt/password/move':
                $this->passwordMoveAction();
                break;
            case 'crypt/password/remove':
                $this->passwordRemoveAction();
                break;
            case 'crypt/rights/list':
                $this->rightsListAction();
                break;
            case 'crypt/rights/save':
                $this->rightsSaveAction();
                break;
            case 'crypt/rights/remove':
                $this->rightsRemoveAction();
                break;
            case 'crypt/set-owner':
                $this->setOwnerAction();
                break;
            case 'api/call-method':
                $this->callMethodAction();
                break;
            case 'api/user':
                $this->userAction();
                break;
            case 'api/favicon':
                $this->faviconAction();
                break;
            case 'api/request-access':
                $this->requestAccessAction();
                break;
            case 'exchange/import':
                $this->importAction();
                break;
            case 'exchange/history':
                $this->historyAction();
                break;
            case 'exchange/copy':
                $this->copyAction();
                break;
            case 'exchange/crypto-migrate':
                $this->cryptoMigrateAction();
                break;
            case 'safari-enter-two':
                $this->enterTwoAction();
                break;
            default:
                $this->sendJsonError('Action not found', 404);
        }
    }

    private function getRequestParams() {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        if (!$data) {
            $data = $_REQUEST;
        }

        if ($this->isWin1251) {
            $data = $this->convertCharset($data, 'UTF-8', 'windows-1251');
        }

        return is_array($data) ? $data : [];
    }

    private function sendJsonOk($data = null) {
        $this->sendJson(['result' => 'ok', 'data' => $data]);
    }

    private function sendJsonError($errorMsg, $code = 200) {
        if ($code != 200) {
            http_response_code($code);
        }
        $this->sendJson(['result' => 'error', 'error' => $errorMsg]);
    }

    private function sendJson($data) {
        global $APPLICATION;

        if ($this->isWin1251) {
            $data = $this->convertCharset($data, 'windows-1251', 'UTF-8');
        }

        // Remove any HTML from /bitrix/header.php and PHP warnings produced
        // while handling the action. The response must contain JSON only.
        if (is_object($APPLICATION)) {
            $APPLICATION->RestartBuffer();
        } elseif (ob_get_level() > 0) {
            ob_clean();
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            http_response_code(500);
            $json = '{"result":"error","error":"Unable to encode JSON response"}';
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $json;
        die();
    }

    private function convertCharset($value, $from, $to) {
        global $APPLICATION;
        if (is_array($value)) {
            foreach ($value as $key => $subVal) {
                $value[$key] = $this->convertCharset($subVal, $from, $to);
            }
        } elseif (is_string($value)) {
            return $APPLICATION->ConvertCharset($value, $from, $to);
        }
        return $value;
    }

    // ----------------------------------------------------
    // ACTIONS
    // ----------------------------------------------------

    private function sectionListAction() {
        $model = new RightManager();
        $this->sendJsonOk($model->getSectionsTree(true));
    }

    private function sectionSaveAction() {
        $params = $this->getRequestParams();
        $sectionId = isset($params['ID']) ? (int)$params['ID'] : 0;
        $parentId = isset($params['IBLOCK_SECTION_ID']) ? (int)$params['IBLOCK_SECTION_ID'] : 0;

        if (!is_string($params['NAME'] ?? null) || trim($params['NAME']) === '' || strlen($params['NAME']) > 255) {
            $this->sendJsonError('Некорректное название раздела', 400);
        }
        if (isset($params['DESCRIPTION']) && (!is_string($params['DESCRIPTION']) || strlen($params['DESCRIPTION']) > 65535)) {
            $this->sendJsonError('Некорректное описание раздела', 400);
        }

        $model = new RightManager();
        if ($sectionId) {
            $section = reset($model->getSection(['ID' => $sectionId]));
            if (!$section || $sectionId !== (int)$section['ID']) {
                $this->sendJsonError('Раздел не найден', 404);
            }
            $rightParams = ['SECTION' => $sectionId];
        } else {
            $rightParams = ['SECTION' => $parentId];
        }

        if ($parentId) {
            $parent = reset($model->getSection(['ID' => $parentId]));
            if (!$parent || $parentId !== (int)$parent['ID']) {
                $this->sendJsonError('Родительский раздел не найден', 404);
            }
            if ($sectionId && !$model->isValidSectionMove($sectionId, $parentId)) {
                $this->sendJsonError('Нельзя переместить раздел внутрь самого себя', 400);
            }
        }

        $accessLevel = $model->checkRightsLevel([$rightParams]);

        if ($accessLevel < RightManager::ACCESS_CAN_WRITE) {
            $this->sendJsonError('Не хватает прав для сохранения раздела');
        }

        if ($sectionId) {
            $model->updateSection($params);
        } else {
            $sectionId = $model->addSection($params);
        }
        $section = $model->getSection(['ID' => $sectionId]);

        $this->sendJsonOk([
            'result' => $sectionId,
            'section' => $section[0],
        ]);
    }

    private function sectionMoveAction() {
        $params = $this->getRequestParams();
        $sectionId   = (int)($params['id'] ?? 0);
        $newParentId = (int)($params['idNewParentFolder'] ?? 0);

        if ($sectionId <= 0 || $newParentId < 0) {
            $this->sendJsonError('Некорректный раздел', 400);
        }

        $rightParams = [
            ['SECTION' => $sectionId],
            ['SECTION' => $newParentId],
        ];
        $model = new RightManager();
        if (!reset($model->getSection(['ID' => $sectionId]))) {
            $this->sendJsonError('Раздел не найден', 404);
        }
        if ($newParentId > 0 && !reset($model->getSection(['ID' => $newParentId]))) {
            $this->sendJsonError('Родительский раздел не найден', 404);
        }
        if (!$model->isValidSectionMove($sectionId, $newParentId)) {
            $this->sendJsonError('Нельзя переместить раздел внутрь самого себя', 400);
        }
        $accessLevel = $model->checkRightsLevel($rightParams);

        if ($accessLevel < RightManager::ACCESS_CAN_WRITE) {
            $this->sendJsonError('Не хватает прав для сохранения раздела');
        }

        $result = $model->updateSection([
            'ID'      => $sectionId,
            'SECTION' => $newParentId,
        ]);

        if ($result) {
            $this->sendJsonOk();
        } else {
            $this->sendJsonError('error');
        }
    }

    private function sectionRemoveAction() {
        $params = $this->getRequestParams();
        $sectionId = (int)($params['sectionId'] ?? 0);
        $model = new RightManager();

        $section = reset($model->getSection(['ID' => $sectionId]));
        if (!$section || $sectionId !== (int)$section['ID']) {
            $this->sendJsonError('Такого раздела не существует', 404);
        }

        $accessLevel = $model->checkRightsLevel([['SECTION' => $sectionId]]);
        if ($accessLevel < RightManager::ACCESS_CAN_WRITE) {
            $this->sendJsonError('Не хватает прав для удаления раздела');
        }

        $model->deleteSectionRights($sectionId);
        $model->deleteSection(['ID' => $sectionId]);
        $this->sendJsonOk();
    }

    private function passwordListAction() {
        $model = new RightManager();
        $list = $model->getItem(['DECRYPT' => false]);
        $allowed = $model->checkEntitiesRights($list);
        if (empty($allowed)) {
            $this->sendJsonOk([]);
        }

        $levels = [];
        foreach ($allowed as $item) {
            $levels[(int)$item['ID']] = $item;
        }
        $result = $model->getItem(['ID' => array_keys($levels)]);
        foreach ($result as &$item) {
            $access = $levels[(int)$item['ID']];
            foreach (['level', 'CAN_READ', 'CAN_WRITE', 'CAN_OWN'] as $field) {
                if (array_key_exists($field, $access)) {
                    $item[$field] = $access[$field];
                }
            }
        }
        unset($item);
        $this->sendJsonOk($result);
    }

    private function passwordListForIdAction() {
        $userModel = new User();
        if (!$userModel->isAdmin()) {
            $this->sendJsonError('Not authorized for this action', 403);
        }

        $params = $this->getRequestParams();
        $forId = isset($params['forId']) ? (int)$params['forId'] : 0;
        $isGroup = isset($params['isGroup']) ? (bool)$params['isGroup'] : false;

        $model = new RightManager();
        $list = $model->getItem();
        $result = $model->checkEntitiesRights($list, $forId, $isGroup);

        $this->sendJsonOk($result);
    }

    private function passwordSaveAction() {
        $params = $this->getRequestParams();
        $params['SECTION'] = intval($params['SECTION'] ?? 0);
        if (!$params['SECTION']) {
            $this->sendJsonError('Не указан раздел для пароля');
        }

        $model = new RightManager();
        $targetSection = reset($model->getSection(['ID' => $params['SECTION']]));
        if (!$targetSection || (int)$targetSection['ID'] !== $params['SECTION']) {
            $this->sendJsonError('Раздел не найден', 404);
        }

        $entity = null;
        if (!empty($params['ID'])) {
            $entity = reset($model->getItem(['ID' => (int)$params['ID']]));
            if (!$entity || (int)$entity['ID'] !== (int)$params['ID']) {
                $this->sendJsonError('Пароль не найден', 404);
            }
            // The section and ID must describe the same existing item. This
            // closes the update IDOR where an arbitrary writable section was
            // combined with another user's item ID.
            if ((int)$entity['SECTION'] !== $params['SECTION']) {
                $this->sendJsonError('Нельзя изменить пароль в другом разделе', 403);
            }
        }

        if (!is_string($params['NAME'] ?? null) || trim($params['NAME']) === '' || strlen($params['NAME']) > 255) {
            $this->sendJsonError('Некорректное название записи', 400);
        }
        if (!is_string($params['CRYPTED'] ?? null) || strlen($params['CRYPTED']) > 1048576) {
            $this->sendJsonError('Некорректный шифротекст', 400);
        }
        if (isset($params['COLOR']) && (!is_string($params['COLOR']) || strlen($params['COLOR']) > 64)) {
            $this->sendJsonError('Некорректный цвет записи', 400);
        }

        $accessLevel = $model->checkRightsLevel([$entity ?: ['SECTION' => $params['SECTION']]]);
        if (!AccessPolicy::canWriteItem($entity ?: ['SECTION' => $params['SECTION']], $params['SECTION'], $accessLevel)) {
            $this->sendJsonError('Не хватает прав для сохранения пароля');
        }

        $fields = [
            'NAME' => $params['NAME'],
            'SECTION' => $params['SECTION'],
            'PREVIEW_TEXT' => $params['COLOR'],
            'PROPERTY_VALUES' => ['CRYPTED' => $params['CRYPTED']],
        ];

        if ($entity) {
            $fields['ID'] = $params['ID'];
            $result = $model->updateItem($fields);

            if ($result) {
                $historyModel = new History();
                $historyModel->addHistory($params['ID'], History::CHANGE);
            }
        } else {
            $result = $model->addItem($fields);
        }

        if (false !== $result) {
            $itemId = !empty($fields['ID']) ? $fields['ID'] : $result;
            $resultData = $model->getItem(['ID' => $itemId]);
            $item = reset($resultData);

            $this->sendJsonOk([
                'result' => $result,
                'DATE_CREATE' => $item['DATE_CREATE'],
                'TIMESTAMP_X' => $item['TIMESTAMP_X'],
            ]);
        } else {
            $this->sendJsonError('error');
        }
    }

    private function passwordMoveAction() {
        $params = $this->getRequestParams();
        $entityId    = (int)($params['entityId'] ?? 0);
        $oldParentId = (int)($params['idOldFolder'] ?? 0);
        $newParentId = (int)($params['idNewFolder'] ?? 0);
        if (($entityId <= 0) || ($oldParentId <= 0) || ($newParentId <= 0)) {
            $this->sendJsonError('Не переданы обязательные параметры');
        }

        $model = new RightManager();
        $entityList = $model->getItem(['ID' => $entityId]);
        $entity = reset($entityList);

        if (!$entity) {
            $this->sendJsonError('Пароль не найден');
        }

        if ((int)$entity['SECTION'] !== $oldParentId) {
            $this->sendJsonError('Запись не принадлежит исходному разделу', 403);
        }
        if (!$model->isValidSectionMove($oldParentId, $newParentId)) {
            $this->sendJsonError('Некорректная иерархия перемещения', 400);
        }

        $accessLevel = [
            $model->checkRightsLevel([$entity]),
            $model->checkRightsLevel([['SECTION' => $oldParentId]]),
            $model->checkRightsLevel([['SECTION' => $newParentId]])
        ];

        foreach ($accessLevel as $al) {
            if ($al < RightManager::ACCESS_CAN_WRITE) {
                $this->sendJsonError('Не хватает прав для сохранения пароля');
            }
        }

        $result = $model->updateItem([
            'ID'      => $entityId,
            'SECTION' => $newParentId,
        ]);

        if ($result) {
            $this->sendJsonOk();
        } else {
            $this->sendJsonError('error');
        }
    }

    private function passwordRemoveAction() {
        $params = $this->getRequestParams();
        $entityId = (int)($params['entityId'] ?? 0);
        $model = new RightManager();

        $entity = reset($model->getItem(['ID' => $entityId]));
        if (!$entity || (int)$entity['ID'] !== $entityId) {
            $this->sendJsonError('Такого пароля не существует', 404);
        }

        $accessLevel = $model->checkRightsLevel([$entity]);
        if ($accessLevel < RightManager::ACCESS_CAN_WRITE) {
            $this->sendJsonError('Не хватает прав для удаления пароля');
        }

        if ($model->deleteItem(['ID' => $entityId])) {
            $model->deleteItemRights($entityId);
            $this->sendJsonOk();
        } else {
            $this->sendJsonError('Такого пароля не существует');
        }
    }

    private function rightsListAction() {
        $params = $this->getRequestParams();
        $sectionId = isset($params['section']) && is_numeric($params['section']) ? (int)$params['section'] : false;
        $itemId = isset($params['item']) ? (int)$params['item'] : false;

        $where = [];
        if ($itemId) {
            $where['entity_id = ?'] = $itemId;
        } elseif (false !== $sectionId) {
            $where['section_id = ?'] = $sectionId;
        }

        $model = new RightManager();
        $item = $model->getRight($where);
        $accessTarget = $itemId
            ? reset($model->getItem(['ID' => $itemId]))
            : ['SECTION' => (int)$sectionId];
        if ($itemId && !$accessTarget) {
            $this->sendJsonError('Пароль не найден', 404);
        }
        if (!$itemId) {
            $section = reset($model->getSection(['ID' => (int)$sectionId]));
            if (!$section || (int)$section['ID'] !== (int)$sectionId) {
                $this->sendJsonError('Раздел не найден', 404);
            }
            $accessTarget = ['SECTION' => (int)$sectionId];
        }
        if (!$accessTarget || $model->checkRightsLevel([$accessTarget]) < RightManager::ACCESS_CAN_WRITE) {
            $this->sendJsonError('Недостаточно прав для просмотра разрешений', 403);
        }

        if (!$item) {
            $owner = $itemId ? (int)$accessTarget['CREATED_BY'] : (int)$section['CREATED_BY'];
            $item = $model->createRightRow($sectionId, $itemId, $owner);
            if (!$item) {
                $this->sendJsonError('Не удалось создать объект прав', 500);
            }
        } elseif ($itemId) {
            $historyModel = new History();
            $historyModel->addHistory($itemId, History::WATCH);
        }

        $result = $item;
        foreach ($result['rights'] as &$right) {
            if (!$right['timed']) {
                continue;
            }
            $timed = new \DateTime($right['timed']);
            $right['timed'] = $timed->format('c');
        }
        unset($right);

        $this->sendJsonOk($result);
    }

    private function rightsSaveAction() {
        $params = $this->getRequestParams();
        $entityId = isset($params['entityId']) ? (int)$params['entityId'] : false;
        $sectionId = isset($params['sectionId']) && is_numeric($params['sectionId']) ? (int)$params['sectionId'] : false;
        $rights = isset($params['rights']) ? $params['rights'] : [];

        if (false === $sectionId) {
            $this->sendJsonError('Не переданы обязательные параметры');
        }

        $model = new RightManager();
        $accessTarget = $entityId
            ? reset($model->getItem(['ID' => $entityId]))
            : ['SECTION' => $sectionId];
        if (!$entityId && !reset($model->getSection(['ID' => $sectionId]))) {
            $this->sendJsonError('Раздел не найден', 404);
        }
        if ($entityId && (!$accessTarget || (int)$accessTarget['SECTION'] !== $sectionId)) {
            $this->sendJsonError('Объект не принадлежит указанному разделу', 403);
        }
        $accessLevel = $model->checkRightsLevel([$accessTarget]);

        if ($accessLevel < RightManager::ACCESS_CAN_WRITE) {
            $this->sendJsonError('Не хватает прав для сохранения прав');
        }

        $model->saveEntityRights($sectionId, $entityId, $rights);
        $this->sendJsonOk();
    }

    private function rightsRemoveAction() {
        $params = $this->getRequestParams();

        $userModel = new User();
        if (!AccessPolicy::canRemoveGlobalRights($userModel->isAdmin())) {
            $this->sendJsonError('Только администратор может удалять права глобально', 403);
        }

        if (isset($params['data']) && is_array($params['data']) && count($params['data']) <= 100) {
            $data = [];
            foreach ($params['data'] as $element) {
                if (!is_array($element) || (int)($element['id'] ?? 0) <= 0) {
                    $this->sendJsonError('Некорректный субъект права', 400);
                }
                $data[] = [
                    'id' => (int)$element['id'],
                    'isGroup' => !empty($element['isGroup']),
                ];
            }
            (new RightManager())->deleteAll($data);
            $this->sendJsonOk();
        }
        $this->sendJsonError('Не переданы субъекты прав', 400);
    }

    private function setOwnerAction() {
        $params = $this->getRequestParams();
        $entityId = isset($params['entityId']) ? (int)$params['entityId'] : false;
        $sectionId = (int)($params['sectionId'] ?? 0);
        $newOwner = (int)($params['owner'] ?? 0);

        if ($sectionId <= 0) {
            $this->sendJsonError('Не переданы обязательные параметры');
        }

        $model = new RightManager();
        $accessTarget = $entityId
            ? reset($model->getItem(['ID' => $entityId]))
            : ['SECTION' => $sectionId];
        if (!$entityId && !reset($model->getSection(['ID' => $sectionId]))) {
            $this->sendJsonError('Раздел не найден', 404);
        }
        if ($entityId && (!$accessTarget || (int)$accessTarget['SECTION'] !== $sectionId)) {
            $this->sendJsonError('Объект не принадлежит указанному разделу', 403);
        }
        $accessLevel = $model->checkRightsLevel([$accessTarget]);
        if ($accessLevel == RightManager::ACCESS_CAN_OWN) {
            if ($newOwner <= 0 || !(new User())->getUserById($newOwner)) {
                $this->sendJsonError('Пользователь-владелец не найден', 400);
            }
            if (!$model->setOwner($sectionId, $entityId, $newOwner)) {
                $this->sendJsonError('Не удалось изменить владельца', 500);
            }
            $this->sendJsonOk();
        } else {
            $this->sendJsonError('Не хватает прав для изменения владельца');
        }
    }

    private function callMethodAction() {
        $params = $this->getRequestParams();
        $method = isset($params['method']) && is_string($params['method']) ? trim($params['method']) : '';
        $methodParams = isset($params['params']) ? $params['params'] : [];

        $result = false;
        if ($method == 'user.current') {
            $userModel = new User();
            $result = $userModel->getCurrentUser();
            if ($result) {
                $result = array_intersect_key($result, array_flip(['ID', 'ACTIVE', 'EMAIL', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'UF_DEPARTMENT']));
            }
        } elseif ($method == 'user.admin') {
            $userModel = new User();
            $result = $userModel->isAdmin();
        } elseif ($method == 'user.get') {
            $userModel = new User();
            $result = $userModel->getUserList($methodParams);
        } elseif ($method == 'department.get') {
            $userModel = new User();
            $result = $userModel->getDepartments();
        } elseif ($method == 'entity.item.get') {
            $model = new RightManager();
            $result = $model->secureGetItem($methodParams);
        }

        if ($method === '') {
            $this->sendJsonError('Не указан метод', 400);
        }

        $this->sendJson([
            'result' => ($result === false) ? 'error' : 'ok',
            'data'   => ($result === false) ? false : $result
        ]);
    }

    private function userAction() {
        $userModel = new User();
        $result = $userModel->getUserData();
        if ($result) {
            $this->sendJsonOk($result);
        } else {
            $this->sendJsonError('error');
        }
    }

    private function faviconAction() {
        $params = $this->getRequestParams();
        $url = isset($params['url']) ? $params['url'] : '';
        $this->sendFavicon($url);
    }

    private function requestAccessAction() {
        $params = $this->getRequestParams();
        $sectionId = (int)($params['sectionId'] ?? 0);

        $model = new RightManager();
        $sectionList = $model->getSection(['ID' => $sectionId]);
        $section = reset($sectionList);

        if (!$section) {
            $this->sendJsonError('SECTION_NOT_FOUND');
        }

        if (!\Bitrix\Main\Loader::includeModule('im')) {
            $this->sendJsonError('IM_NOT_INSTALLED');
        }

        $itemRow = $model->getRight(['section_id = ?' => $section['ID']]);
        $userModel = new User();
        $user = $userModel->getCurrentUser();

        if (!$itemRow || empty($itemRow['owner'])) {
            $this->sendJsonError('Владелец раздела не найден', 409);
        }
        if (!$user || (int)$itemRow['owner'] === (int)$user['ID']) {
            $this->sendJsonError('Нельзя отправить запрос самому себе', 400);
        }

        $result = \CIMMessage::Add([
            'TITLE' => 'Запрос на предоставление доступа к разделу',
            'TO_USER_ID' => $itemRow['owner'],
            'FROM_USER_ID' => $user['ID'],
            'MESSAGE_TYPE' => IM_MESSAGE_SYSTEM,
            'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
            'NOTIFY_MODULE' => 'drdroid.keyrights',
            'MESSAGE' => 'KeyRights: пользователь запрашивает доступ к паролям в разделе ' .
                '[url=/keyrights/#?section=' . $section['ID'] . ']' . $section['NAME'] . '[/url].',
        ]);

        $this->sendJsonOk($result);
    }

    private function importAction() {
        $params = $this->getRequestParams();

        $importer = new Import();
        if (!empty($params['data']) && is_array($params['data'])) {
            $result = $importer->import($params['data']);
            $this->sendJson($result);
        } elseif (!empty($params['step'])) {
            $result = $importer->continueImport();
            $this->sendJson($result);
        } else {
            $this->sendJsonError('error');
        }
    }

    private function historyAction() {
        $params = $this->getRequestParams();
        if (!empty($params['dateFrom']) && !empty($params['dateUntil'])) {
            $data = [
                'dateFrom' => $params['dateFrom'],
                'dateUntil' => $params['dateUntil'],
            ];

            $historian = new History();
            $result = $historian->export($data);

            $this->sendJsonOk($result);
        } else {
            $this->sendJsonError('error');
        }
    }

    private function copyAction() {
        $params = $this->getRequestParams();

        if (!empty($params['item_id'])) {
            $model = new RightManager();
            $entity = reset($model->getItem(['ID' => (int)$params['item_id']]));
            if (!$entity || $model->checkRightsLevel([$entity]) < RightManager::ACCESS_CAN_READ) {
                $this->sendJsonError('Недостаточно прав для копирования записи', 403);
            }
            $historyModel = new History();
            $result = $historyModel->addHistory($params['item_id'], History::COPY);
            if ($result) {
                $this->sendJsonOk();
                return;
            }
        }

        $this->sendJsonError('error');
    }

    private function cryptoMigrateAction() {
        if (!(new User())->isAdmin()) {
            $this->sendJsonError('Только администратор может запускать миграцию шифротекста', 403);
        }

        $params = $this->getRequestParams();
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $this->sendJsonOk((new RightManager())->migrateLegacyItems($limit));
    }

    private function enterTwoAction() {
        header('Location: /');
        die();
    }

    // ----------------------------------------------------
    // FAVICON HELPER
    // ----------------------------------------------------

    private function sendFavicon($rawUrl) {
        $favUrl = $this->getFaviconUrl($rawUrl);
        if (!$favUrl) {
            $this->sendDefaultFavicon();
        }

        $cached = $this->getCachedFavicon($favUrl);
        if ($cached) {
            $this->sendFaviconData($cached);
        }

        $favicon = $this->downloadFavicon($favUrl);
        if ($this->testFaviconResponse($favicon)) {
            $this->saveCachedFavicon($favUrl, $favicon);
            $this->sendFaviconData($favicon);
        } else {
            $this->saveCachedFavicon($favUrl, false);
            $this->sendDefaultFavicon();
        }
    }

    private function sendFaviconData($data) {
        while (@ob_end_clean()) {}
        header('Content-Description: File Transfer');
        header('Content-Type: image/x-icon');
        header('Content-Transfer-Encoding: binary');
        header('Expires: ' . date('r', time() + 2592000));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($data));
        echo $data;
        die();
    }

    private function sendDefaultFavicon() {
        $defaultPath = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/drdroid/keyrights/static/images/no-icon.ico';
        if (is_file($defaultPath)) {
            $data = file_get_contents($defaultPath);
            if ($data !== false) {
                $this->sendFaviconData($data);
            }
        }

        while (@ob_end_clean()) {}
        http_response_code(204);
        header('Cache-Control: public, max-age=86400');
        die();
    }

    private function downloadFavicon($url) {
        if (!function_exists('curl_init')) {
            return false;
        }

        $curlObj = curl_init();
        curl_setopt($curlObj, CURLOPT_URL, $url);
        curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlObj, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($curlObj, CURLOPT_TIMEOUT, 3);
        curl_setopt($curlObj, CURLOPT_CONNECTTIMEOUT, 2);
        // Redirects are deliberately disabled. Every redirect target would
        // need to go through the same DNS/private-network validation.
        curl_setopt($curlObj, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curlObj, CURLOPT_MAXFILESIZE, 1024 * 15);
        curl_setopt($curlObj, CURLOPT_USERAGENT, 'KeyRights favicon/2.0');

        if (isset($this->faviconResolve[$url])) {
            curl_setopt($curlObj, CURLOPT_RESOLVE, $this->faviconResolve[$url]);
        }

        if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTP') && defined('CURLPROTO_HTTPS')) {
            curl_setopt($curlObj, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
            curl_setopt($curlObj, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        }

        if (stripos($url, 'https://') === 0) {
            curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curlObj, CURLOPT_SSL_VERIFYHOST, 2);
        }

        curl_setopt($curlObj, CURLOPT_NOPROGRESS, false);
        curl_setopt($curlObj, CURLOPT_PROGRESSFUNCTION, function($resource, $downloadSize, $downloaded, $uploadSize, $uploaded) {
            return ($downloaded > (1024 * 15)) ? 1 : 0;
        });

        $favicon = curl_exec($curlObj);
        $status  = curl_getinfo($curlObj, CURLINFO_HTTP_CODE);
        curl_close($curlObj);

        if ($status < 200 || $status >= 300) return false;
        return $favicon;
    }

    private function getFaviconUrl($rawUrl) {
        if (!is_string($rawUrl) || strlen($rawUrl) > 2048) {
            return false;
        }

        $rawUrl = trim($rawUrl);
        if ($rawUrl === '') {
            return false;
        }
        if (strpos($rawUrl, '://') === false) {
            $rawUrl = 'http://' . $rawUrl;
        }

        $parts = parse_url($rawUrl);

        if (!is_array($parts) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        $scheme = strtolower((string)($parts['scheme'] ?? 'http'));
        if ($scheme !== 'http' && $scheme !== 'https') {
            return false;
        }

        $host = strtolower(rtrim((string)$parts['host'], '.'));
        $portNumber = !empty($parts['port']) ? (int)$parts['port'] : ($scheme === 'https' ? 443 : 80);
        if ($portNumber !== 80 && $portNumber !== 443) {
            return false;
        }

        // Resolve once, reject every private/reserved address, and pin curl
        // to the validated public addresses to prevent DNS rebinding.
        $addresses = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            ? [$host]
            : $this->resolvePublicIpv4($host);
        if (empty($addresses)) {
            return false;
        }
        foreach ($addresses as $address) {
            if (!AccessPolicy::isPublicIpv4($address)) {
                return false;
            }
        }

        $port = ':' . $portNumber;
        $faviconUrl = $scheme . '://' . $host . $port . '/favicon.ico';
        $this->faviconResolve[$faviconUrl] = array_map(
            static function ($address) use ($host, $portNumber) {
                return $host . ':' . $portNumber . ':' . $address;
            },
            $addresses
        );

        return $faviconUrl;
    }

    private function resolvePublicIpv4($host) {
        if (!preg_match('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i', $host)) {
            return [];
        }
        if ($host === 'localhost' || substr($host, -6) === '.local' || substr($host, -9) === '.internal') {
            return [];
        }

        $records = @dns_get_record($host, DNS_A);
        if (!is_array($records)) {
            return [];
        }

        $addresses = [];
        foreach ($records as $record) {
            if (!empty($record['ip'])) {
                $addresses[] = $record['ip'];
            }
        }

        return array_values(array_unique($addresses));
    }

    private function getCachedFavicon($favUrl) {
        $fn = $this->prepareFaviconFilename($favUrl);
        $fullPath = $this->getCacheFolder() . $fn;

        if (!file_exists($fullPath)) {
            return false;
        }

        $stat = filemtime($fullPath);
        if ($stat && ($stat + 2592000 > time())) {
            $fh = fopen($fullPath, 'r');
            $data = fread($fh, 1024 * 15);
            fclose($fh);
            return $data;
        }

        return false;
    }

    private function prepareFaviconFilename($favUrl) {
        return hash('sha256', strtolower((string)$favUrl)) . '.ico';
    }

    private function testFaviconResponse($favicon) {
        if (!$favicon) return false;
        $text = strtolower($favicon);

        if (function_exists('getimagesizefromstring')) {
            $info = @getimagesizefromstring($favicon);
        } else {
            $fn = tempnam(sys_get_temp_dir(), 'keyrights-favicon-');
            if ($fn === false || file_put_contents($fn, $favicon) === false) {
                return false;
            }
            $info = @getimagesize($fn);
            @unlink($fn);
        }

        if (!is_array($info)) {
            return false;
        } elseif (!$info[0] || !$info[1]) {
            return false;
        } else {
            return (strpos($text, '<body') === false) && (strpos($text, ' error') === false) && (strpos($text, '<a href') === false);
        }
    }

    private function saveCachedFavicon($favUrl, $fileData) {
        if (!$fileData || strlen($fileData) > 1024 * 15) {
            return;
        }

        $this->prepareCacheFolder();
        $fn = $this->prepareFaviconFilename($favUrl);

        $fullPath = $this->getCacheFolder() . $fn;
        $fileHandler = fopen($fullPath, 'w');
        fwrite($fileHandler, $fileData);
        fclose($fileHandler);
    }

    private function prepareCacheFolder() {
        $folder = $this->getCacheFolder();
        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }
    }

    private function getCacheFolder() {
        return $_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache/drdroid.keyrights/favicon/';
    }
}
