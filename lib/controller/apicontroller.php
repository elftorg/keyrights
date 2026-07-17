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
                    new Authentication(),
                    new HttpMethod([HttpMethod::METHOD_GET, HttpMethod::METHOD_POST]),
                ],
            ],
            'write' => [
                'prefilters' => [
                    new Authentication(),
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
        $this->executeRoute((string)$route);
    }

    public function writeAction($route) {
        $this->executeRoute((string)$route);
    }

    private function executeRoute($route) {
        try {
            $this->route($route);
        } catch (\Throwable $exception) {
            AddMessage2Log($exception->getMessage(), 'drdroid.keyrights');
            $this->sendJsonError('Внутренняя ошибка KeyRights', 500);
        }
    }

    private function route($action) {
        $this->enforceRateLimit($action);
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

            default:
                $this->sendJsonError('Action not found', 404);
        }
    }

    private function enforceRateLimit($action) {
        global $USER;
        $userId = is_object($USER) ? (int)$USER->GetID() : 0;
        if ($userId <= 0) {
            return;
        }
        $limit = in_array($action, self::WRITE_ACTIONS, true) ? 60 : 180;
        if ($action === 'exchange/crypto-migrate') $limit = 5;
        if ($action === 'exchange/import') $limit = 10;

        $bucket = (int)floor(time() / 60);
        $cacheId = 'api-' . hash('sha256', $userId . '|' . $action . '|' . $bucket);
        $cacheDir = '/drdroid.keyrights/rate-limit';
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $count = 0;
        if ($cache->initCache(70, $cacheId, $cacheDir)) {
            $count = (int)$cache->getVars();
            $cache->clean($cacheId, $cacheDir);
        }
        $count++;
        $cache->startDataCache(70, $cacheId, $cacheDir);
        $cache->endDataCache($count);
        if ($count > $limit) {
            $this->sendJsonError('Слишком много запросов. Повторите попытку позже', 429);
        }
    }

    private function getRequestParams() {
        $maxBodyBytes = 10485760;
        $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
        if ($contentLength > $maxBodyBytes) {
            $this->sendJsonError('Тело запроса превышает допустимый размер', 413);
        }
        $body = file_get_contents('php://input', false, null, 0, $maxBodyBytes + 1);
        if (is_string($body) && strlen($body) > $maxBodyBytes) {
            $this->sendJsonError('Тело запроса превышает допустимый размер', 413);
        }
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
        if (is_string($body) && trim($body) !== '' && strpos($contentType, 'application/json') !== false) {
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                $this->sendJsonError('Некорректное JSON-тело запроса', 400);
            }
        } elseif ($method === 'GET') {
            $data = $_GET;
        } else {
            $data = $_POST;
        }

        if ($this->isWin1251) {
            $data = $this->convertCharset($data, 'UTF-8', 'windows-1251');
        }

        return is_array($data) ? $data : [];
    }

    private function sendJsonOk($data = null) {
        $this->sendJson(['result' => 'ok', 'data' => $data]);
    }

    private function sendJsonError($errorMsg, $code = 400) {
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
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
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
        $params = $this->getRequestParams();
        $forId = isset($params['forId']) ? (int)$params['forId'] : false;
        $isGroup = !empty($params['isGroup']);
        if ($forId && !(new User())->isAdmin()) {
            $this->sendJsonError('Not authorized for this action', 403);
        }
        $model = new RightManager();
        $this->sendJsonOk($model->getAccessibleSectionsTree(true, $forId, $isGroup));
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
        $sourceParentId = null;
        if ($sectionId) {
            $section = reset($model->getSection(['ID' => $sectionId]));
            if (!$section || $sectionId !== (int)$section['ID']) {
                $this->sendJsonError('Раздел не найден', 404);
            }
            if (!isset($params['IBLOCK_SECTION_ID'])) {
                $parentId = (int)$section['SECTION'];
            }
            $sourceParentId = (int)$section['SECTION'];
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

        $targets = $sectionId ? [['SECTION' => $sectionId]] : [['SECTION' => $parentId]];
        if ($sectionId && $parentId !== $sourceParentId) {
            $targets[] = ['SECTION' => $parentId];
        }
        foreach ($targets as $target) {
            if ($model->checkRightsLevel([$target]) < RightManager::ACCESS_CAN_WRITE) {
                $this->sendJsonError('Не хватает прав для сохранения раздела', 403);
            }
        }

        if ($sectionId) {
            $params['SECTION'] = $parentId;
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
        foreach ([['SECTION' => $sectionId], ['SECTION' => $newParentId]] as $target) {
            if ($model->checkRightsLevel([$target]) < RightManager::ACCESS_CAN_WRITE) {
                $this->sendJsonError('Не хватает прав для сохранения раздела', 403);
            }
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

        $subtreeIds = $model->getSectionSubtreeIds($sectionId);
        $accessLevel = $model->checkRightsLevel(array_map(function ($id) {
            return ['SECTION' => (int)$id];
        }, $subtreeIds));
        if ($accessLevel < RightManager::ACCESS_CAN_WRITE) {
            $this->sendJsonError('Не хватает прав для удаления раздела или одного из вложенных разделов', 403);
        }

        $entityIds = [];
        foreach ($model->getItem(['DECRYPT' => false]) as $item) {
            if (in_array((int)$item['SECTION'], $subtreeIds, true)) {
                $entityIds[] = (int)$item['ID'];
            }
        }
        if (!$model->deleteSection(['ID' => $sectionId])) {
            $this->sendJsonError('Не удалось удалить раздел', 500);
        }
        $model->deleteSectionRightsByIds($subtreeIds, $entityIds);
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
        $list = $model->getItem(['DECRYPT' => false]);
        $allowed = $model->checkEntitiesRights($list, $forId, $isGroup);
        $result = [];
        if (!empty($allowed)) {
            $levels = [];
            foreach ($allowed as $item) $levels[(int)$item['ID']] = $item;
            $result = $model->getItem(['ID' => array_keys($levels)]);
            foreach ($result as &$item) {
                $access = $levels[(int)$item['ID']];
                foreach (['level', 'CAN_READ', 'CAN_WRITE', 'CAN_OWN'] as $field) {
                    if (array_key_exists($field, $access)) $item[$field] = $access[$field];
                }
            }
            unset($item);
        }

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
            $this->sendJsonError('Не хватает прав для сохранения пароля', 403);
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
        if (!reset($model->getSection(['ID' => $newParentId]))) {
            $this->sendJsonError('Целевой раздел не найден', 404);
        }

        $accessLevel = [
            $model->checkRightsLevel([$entity]),
            $model->checkRightsLevel([['SECTION' => $oldParentId]]),
            $model->checkRightsLevel([['SECTION' => $newParentId]])
        ];

        foreach ($accessLevel as $al) {
            if ($al < RightManager::ACCESS_CAN_WRITE) {
                $this->sendJsonError('Не хватает прав для сохранения пароля', 403);
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
            $this->sendJsonError('Не хватает прав для удаления пароля', 403);
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
            $this->sendJsonError('Не хватает прав для сохранения прав', 403);
        }

        try {
            if (!$model->saveEntityRights($sectionId, $entityId, $rights)) {
                $this->sendJsonError('Не удалось сохранить права', 500);
            }
        } catch (\InvalidArgumentException $exception) {
            $this->sendJsonError($exception->getMessage(), 400);
        }
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
            $owner = $newOwner > 0 ? (new User())->getUserById($newOwner) : false;
            if (!$owner || ($owner['ACTIVE'] ?? 'N') !== 'Y') {
                $this->sendJsonError('Пользователь-владелец не найден', 400);
            }
            if (!$model->setOwner($sectionId, $entityId, $newOwner)) {
                $this->sendJsonError('Не удалось изменить владельца', 500);
            }
            $this->sendJsonOk();
        } else {
            $this->sendJsonError('Не хватает прав для изменения владельца', 403);
        }
    }

    private function callMethodAction() {
        $params = $this->getRequestParams();
        $method = isset($params['method']) && is_string($params['method']) ? trim($params['method']) : '';
        $methodParams = isset($params['params']) ? $params['params'] : [];
        if ($method === '') {
            $this->sendJsonError('Не указан метод', 400);
        }

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
            if (!(new RightManager())->currentUserCanManageRights()) {
                $this->sendJsonError('Access denied', 403);
            }
            $userModel = new User();
            $result = $userModel->getUserList($methodParams);
        } elseif ($method == 'department.get') {
            if (!(new RightManager())->currentUserCanManageRights()) {
                $this->sendJsonError('Access denied', 403);
            }
            $userModel = new User();
            $result = $userModel->getDepartments();
        } else {
            $this->sendJsonError('Метод не поддерживается', 404);
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
        if ($model->checkRightsLevel([['SECTION' => (int)$section['ID']]]) >= RightManager::ACCESS_CAN_READ) {
            $this->sendJsonError('Доступ к разделу уже предоставлен', 409);
        }

        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheId = 'request-' . (int)$user['ID'] . '-' . (int)$section['ID'];
        $cacheDir = '/drdroid.keyrights/request-access';
        if ($cache->initCache(300, $cacheId, $cacheDir)) {
            $this->sendJsonError('Повторный запрос можно отправить через 5 минут', 429);
        }

        $safeSectionName = str_replace(['[', ']'], ['&#91;', '&#93;'], (string)$section['NAME']);

        $result = \CIMMessage::Add([
            'TITLE' => 'Запрос на предоставление доступа к разделу',
            'TO_USER_ID' => $itemRow['owner'],
            'FROM_USER_ID' => $user['ID'],
            'MESSAGE_TYPE' => IM_MESSAGE_SYSTEM,
            'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
            'NOTIFY_MODULE' => 'drdroid.keyrights',
            'MESSAGE' => 'KeyRights: пользователь запрашивает доступ к паролям в разделе ' .
                '[url=/keyrights/#?section=' . $section['ID'] . ']' . $safeSectionName . '[/url].',
        ]);

        if (!$result) {
            $this->sendJsonError('Не удалось отправить запрос владельцу', 500);
        }
        $cache->startDataCache(300, $cacheId, $cacheDir);
        $cache->endDataCache(['sent' => true]);

        $this->sendJsonOk($result);
    }

    private function importAction() {
        if (!(new User())->isAdmin()) {
            $this->sendJsonError('Только администратор может выполнять импорт', 403);
        }
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
        if (!(new User())->isAdmin()) {
            $this->sendJsonError('Только администратор может выгружать историю', 403);
        }
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
        if (isset($params['limit']) && (!is_numeric($params['limit']) || (int)$params['limit'] < 1 || (int)$params['limit'] > 500)) {
            $this->sendJsonError('Параметр limit должен быть числом от 1 до 500', 400);
        }
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $this->sendJsonOk((new RightManager())->migrateLegacyItems($limit));
    }

}
