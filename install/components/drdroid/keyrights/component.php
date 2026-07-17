<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * @var $APPLICATION
 */
if (!\Bitrix\Main\Loader::includeModule('drdroid.keyrights') || !class_exists('CKeyrights')) {
    include_once($_SERVER["DOCUMENT_ROOT"] . BX_PERSONAL_ROOT . "/templates/" . SITE_TEMPLATE_ID . "/header.php");
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/drdroid.keyrights/include.php')) {
        $this->IncludeComponentTemplate('trial');
    } else {
        $this->IncludeComponentTemplate('installed');
    }
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
    return;
}

$dir = $APPLICATION->GetCurDir();
if (strpos($dir, "/keyrights/") === false) {
    echo("<h1>" . GetMessage("KEYRIGHTS_COMPONENT_WRONG_PATH") . "</h1>");
    return false;
}

$arParams['BASE_PATH'] = $_SERVER['DOCUMENT_ROOT'];

$APPLICATION->keyrights = CKeyrights::getInstance($arParams);
$APPLICATION->keyrights->run();
