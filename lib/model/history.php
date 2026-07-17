<?php
namespace Drdroid\Keyrights\Model;

use Bitrix\Main\Config\Option;
use CIBlockElement;

class History {
    const WATCH = "watch";
    const CHANGE = "change";
    const COPY = "copy";

    private $iblockIdItem;
    private $iblockIdHistory;
    private $ibe;

    public function __construct() {
        $this->iblockIdItem = $this->getIblockIdItem();
        $this->iblockIdHistory = $this->getIblockIdHistory();

        $this->ibe = new CIBlockElement();
    }

    public function getIblockIdItem() {
        return Option::get('drdroid.keyrights', 'iblockId', '-1');
    }

    public function getIblockIdHistory() {
        return Option::get('drdroid.keyrights', 'historyIblockId', '-1');
    }

    public function addHistory($id, $action) {
        if (!empty($id) && !empty($action)) {
            $userModel = new User();

            $data = [
                'ITEM_ID' => ["VALUE" => $id],
                'ACTION' => ["VALUE" => $action],
            ];

            $res = $this->ibe->GetList([], ['IBLOCK_ID' => $this->iblockIdItem, 'ID' => $id], false, false, ['NAME']);
            $arItem = $res->Fetch();
            $name = is_array($arItem) ? $arItem['NAME'] : 'Удаленный элемент #' . $id;

            $fields = [
                "IBLOCK_ID" => $this->iblockIdHistory,
                "CREATED_BY" => $userModel->getUserId(),
                "NAME" => $name,
                "PROPERTY_VALUES" => $data,
            ];

            return $this->ibe->Add($fields);
        }
        return false;
    }

    public function export($data = []) {
        $userModel = new User();
        if (!$userModel->isAdmin()) {
            return [
                'result' => 'error',
                'message' => 'Only admin can perform export history',
            ];
        }

        $dateRange = $this->parseDateRange($data);
        if ($dateRange === null) {
            return [
                'result' => 'error',
                'message' => 'Некорректный диапазон дат. Используйте формат ДД.ММ.ГГГГ',
            ];
        }

        $filter = [
            'IBLOCK_ID' => $this->iblockIdHistory,
            '>=DATE_CREATE' => ConvertTimeStamp($dateRange['from'], "FULL"),
            '<=DATE_CREATE' => ConvertTimeStamp($dateRange['until'], "FULL"),
        ];
        $res = $this->ibe->GetList([], $filter, false, false, ['IBLOCK_ID', 'ID', 'DATE_CREATE', 'CREATED_BY', 'PROPERTY_ACTION', 'PROPERTY_ITEM_ID']);

        $users = [];
        $items = [];
        $result = [];
        while ($element = $res->Fetch()) {
            $result[] = [
                'date' => $element['DATE_CREATE'],
                'user' => $element['CREATED_BY'],
                'name' => $element['PROPERTY_ITEM_ID_VALUE'],
                'action' => $element['PROPERTY_ACTION_VALUE'],
            ];
            $users[$element['CREATED_BY']] = true;
            $items[$element['PROPERTY_ITEM_ID_VALUE']] = true;
        }

        $userList = User::getUserListById(array_keys($users));
        $userNameList = [];
        foreach ($userList as $user) {
            $userNameList[$user['ID']] = '[' . $user['ID'] . '] ' . $user['NAME'] . ' ' . $user['LAST_NAME'];
        }

        $filter = [
            'IBLOCK_ID' => $this->iblockIdItem,
            'ID' => array_keys($items)
        ];

        $itemProps = [];
        if (!empty($items)) {
            $itemRes = $this->ibe->GetList([], $filter, false, false, ['IBLOCK_ID', 'ID', 'NAME']);
            while ($entity = $itemRes->Fetch()) {
                $itemProps[$entity['ID']] = '[' . $entity['ID'] . '] ' . $entity['NAME'];
            }
        }

        foreach ($result as $key => $row) {
            if (isset($userNameList[$row['user']])) {
                $row['user'] = $userNameList[$row['user']];
            } else {
                $row['user'] = '';
            }

            if (isset($itemProps[$row['name']])) {
                $row['name'] = $itemProps[$row['name']];
            } else {
                $row['name'] = '';
            }
            $result[$key] = $row;
        }

        return $result;
    }

    private function parseDateRange($data) {
        if (!is_array($data)) {
            return null;
        }
        $from = $this->parseDate($data['dateFrom'] ?? null, false);
        $until = $this->parseDate($data['dateUntil'] ?? null, true);
        if ($from === null || $until === null || $from > $until) {
            return null;
        }
        return ['from' => $from, 'until' => $until];
    }

    private function parseDate($value, $endOfDay) {
        if (!is_string($value) || preg_match('/^\d{2}\.\d{2}\.\d{4}$/D', $value) !== 1) {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!d.m.Y', $value);
        $errors = \DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }
        if ($endOfDay) {
            $date = $date->setTime(23, 59, 59);
        }
        return $date->getTimestamp();
    }
}
