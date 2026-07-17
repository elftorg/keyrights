<?php
IncludeModuleLangFile(__FILE__);

class drdroid_keyrights extends CModule {
    var $MODULE_ID = 'drdroid.keyrights';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $_1457208652 = "/keyrights/";
    var $_1054782213 = "keyrights";
    var $_439305423 = "keyrights.history";
    var $_1512689717 = "keyrights";
    var $_1633177470 = array();

    public function __construct() {
        $arModuleVersion = array();
        include(__DIR__ . "/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = GetMessage("KEYRIGHTS_MODULE_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("KEYRIGHTS_MODULE_DESCRIPTION");
        $this->PARTNER_NAME = "Drdroid";
        $this->PARTNER_URI = "";
    }

    public function DoInstall() {
        global $APPLICATION;
        $GLOBALS["errors"] = array();
        if (IsModuleInstalled("drdroid.keyrights")) {
            return false;
        }
        if (!check_bitrix_sessid()) {
            return false;
        }

        $reqCheck = $this->checkRequirements();
        $step = (int)($_REQUEST["step"] ?? 0);

        if ($step == 0 || count($reqCheck["errors"]) > 0) {
            $APPLICATION->IncludeAdminFile(GetMessage("KEYRIGHTS_INSTALL"), $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/drdroid.keyrights/install/step1.php");
        } elseif ($step == 1) {
            if (empty($_REQUEST["keyphrase"])) {
                $GLOBALS["errors"]["keyphrase"] = true;
                $APPLICATION->IncludeAdminFile(GetMessage("KEYRIGHTS_INSTALL"), $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/drdroid.keyrights/install/step1.php");
            } else {
                $option = new \COption();
                $option->SetOptionString($this->MODULE_ID, "clientPassphrase", (string)$_REQUEST["keyphrase"]);
                $serverPass = $option->GetOptionString($this->MODULE_ID, "serverPassphrase", '');
                if (empty($serverPass)) {
                    $option->SetOptionString($this->MODULE_ID, "serverPassphrase", randString(50));
                }
                $this->InstallDB();
                $this->InstallIblocks();
                $this->installFiles();
                $this->installRewrite();
                $this->installMenu();
                RegisterModule("drdroid.keyrights");
                RegisterModuleDependences("iblock", "OnAfterIBlockSectionDelete", $this->MODULE_ID, "CKeyrights", "onIblockSectionDelete");
                RegisterModuleDependences("main", "OnUserDelete", $this->MODULE_ID, "CKeyrights", "onUserDelete");
                $GLOBALS["errors"] = $this->_1633177470;
                LocalRedirect($this->_1457208652);
            }
        }
        return true;
    }

    public function InstallDB() {
        global $DB, $APPLICATION;
        $this->_1633177470 = false;

        $tableResult = $DB->Query("SHOW TABLES LIKE 'sib_kr_item'", true);
        if (!$tableResult || !$tableResult->Fetch()) {
            $sqlFile = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/drdroid.keyrights/install/db/" . strtolower($DB->type) . "/install.sql";
            $sqlFile = str_replace("//", "/", $sqlFile);
            $this->_1633177470 = $this->RunSQLBatch($sqlFile);
        }
        if ($this->_1633177470 === false) {
            $this->_1633177470 = $this->ensureDatabaseSchema();
        }
        if ($this->_1633177470 !== false) {
            $APPLICATION->ThrowException(implode("<br>", $this->_1633177470));
            return false;
        }
        return true;
    }

    public function InstallIblocks() {
        if (!CModule::IncludeModule("iblock")) {
            return false;
        }
        $ibType = new \CIBlockType();
        $ib = new \CIBlock();
        $site = new \CSite();
        $ibProp = new \CIBlockProperty();

        $typeRes = $ibType->GetByID($this->_1512689717);
        $type = $typeRes->Fetch();
        if (!$type) {
            $arTypeFields = array(
                "ID" => $this->_1512689717,
                "SECTIONS" => "Y",
                "IN_RSS" => "N",
                "SORT" => 1000,
                "LANG" => array(
                    "ru" => array(
                        "NAME" => "Drdroid.Keyrights",
                        "SECTION_NAME" => GetMessage("KEYRIGHTS_IBLOCK_SECTION_NAME"),
                        "ELEMENT_NAME" => GetMessage("KEYRIGHTS_IBLOCK_ELEMENT_NAME")
                    ),
                    "en" => array(
                        "NAME" => "Drdroid.Keyrights",
                        "SECTION_NAME" => "Section",
                        "ELEMENT_NAME" => "Element"
                    )
                )
            );
            $ibType->Add($arTypeFields);
        }

        $defSite = $site->GetDefSite();

        // 1. Create main keyrights IBlock
        $res = $ib->GetList(array(), array("TYPE" => $this->_1512689717, "CODE" => $this->_1054782213));
        $ibData = $res->Fetch();
        if (!$ibData) {
            $arFields = array(
                "ACTIVE" => "Y",
                "NAME" => "Drdroid.Keyrights",
                "CODE" => $this->_1054782213,
                "LIST_PAGE_URL" => $this->_1457208652,
                "DETAIL_PAGE_URL" => $this->_1457208652,
                "IBLOCK_TYPE_ID" => $this->_1512689717,
                "SITE_ID" => array($defSite),
                "SORT" => 100,
                "GROUP_ID" => array("2" => "R"),
                "VERSION" => 2
            );
            $iblockId = $ib->Add($arFields);

            $arPropFields = array(
                "IBLOCK_ID" => $iblockId,
                "ACTIVE" => "Y",
                "NAME" => GetMessage("KEYRIGHTS_IBLOCK_NAME"),
                "SORT" => 10,
                "CODE" => "CRYPTED",
                "PROPERTY_TYPE" => "S",
                "USER_TYPE" => "HTML",
                "MULTIPLE" => "N",
                "IS_REQUIRED" => "N"
            );
            $ibProp->Add($arPropFields);
        } else {
            $iblockId = $ibData["ID"];
        }

        // 2. Create history keyrights IBlock
        $res = $ib->GetList(array(), array("TYPE" => $this->_1512689717, "CODE" => $this->_439305423));
        $historyData = $res->Fetch();
        if (!$historyData) {
            $arFields = array(
                "ACTIVE" => "Y",
                "NAME" => "Drdroid.Keyrights.History",
                "CODE" => $this->_439305423,
                "LIST_PAGE_URL" => $this->_1457208652,
                "DETAIL_PAGE_URL" => $this->_1457208652,
                "IBLOCK_TYPE_ID" => $this->_1512689717,
                "SITE_ID" => array($defSite),
                "SORT" => 200,
                "GROUP_ID" => array("2" => "R"),
                "VERSION" => 2
            );
            $historyIblockId = $ib->Add($arFields);

            $arPropFields = array(
                "IBLOCK_ID" => $historyIblockId,
                "ACTIVE" => "Y",
                "NAME" => "Password",
                "SORT" => 10,
                "CODE" => "ITEM_ID",
                "PROPERTY_TYPE" => "N",
                "MULTIPLE" => "N",
                "IS_REQUIRED" => "Y"
            );
            $ibProp->Add($arPropFields);

            $arPropFields = array(
                "IBLOCK_ID" => $historyIblockId,
                "ACTIVE" => "Y",
                "NAME" => "Action",
                "SORT" => 20,
                "CODE" => "ACTION",
                "PROPERTY_TYPE" => "S",
                "MULTIPLE" => "N",
                "IS_REQUIRED" => "Y"
            );
            $ibProp->Add($arPropFields);
        } else {
            $historyIblockId = $historyData["ID"];
        }

        \COption::SetOptionString($this->MODULE_ID, "iblockId", $iblockId);
        \COption::SetOptionString($this->MODULE_ID, "historyIblockId", $historyIblockId);
        return true;
    }

    public function UnInstallIblocks() {
        if (!CModule::IncludeModule("iblock")) {
            return false;
        }
        $ibType = new \CIBlockType();
        $ib = new \CIBlock();

        $iblockId = \COption::GetOptionString($this->MODULE_ID, "iblockId");
        $res = $ib->GetList(array(), array("ID" => $iblockId, "TYPE" => $this->_1512689717, "CODE" => $this->_1054782213));
        $ibData = $res->Fetch();
        if ($ibData) {
            $ib->Delete($ibData["ID"]);
        }

        $historyIblockId = \COption::GetOptionString($this->MODULE_ID, "historyIblockId");
        $res = $ib->GetList(array(), array("ID" => $historyIblockId, "TYPE" => $this->_1512689717, "CODE" => $this->_439305423));
        $historyData = $res->Fetch();
        if ($historyData) {
            $ib->Delete($historyData["ID"]);
        }

        $typeRes = $ibType->GetByID($this->_1512689717);
        $type = $typeRes->Fetch();
        if ($type) {
            $res = $ib->GetList(array(), array("TYPE" => $this->_1512689717));
            if (!$res->Fetch()) {
                $ibType->Delete($this->_1512689717);
            }
        }
        \COption::RemoveOption($this->MODULE_ID, "iblockId");
        \COption::RemoveOption($this->MODULE_ID, "historyIblockId");
        return true;
    }

    public function installFiles() {
        DeleteDirFilesEx("/bitrix/cache/keyrights/");
        // Remove only the obsolete Zend application owned by this module;
        // the active component and user customizations are updated in place.
        DeleteDirFilesEx("/bitrix/components/drdroid/keyrights/application/");
        
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/drdroid.keyrights/install/frontend", $_SERVER["DOCUMENT_ROOT"] . $this->_1457208652, true, true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/drdroid.keyrights/install/components", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components", true, true);
        return true;
    }

    public function uninstallFiles() {
        $baseUrl = $this->_1457208652;
        if ($baseUrl[strlen($baseUrl) - 1] == "/") {
            $baseUrl = substr($baseUrl, 0, strlen($baseUrl) - 1);
        }
        DeleteDirFilesEx($baseUrl);
        DeleteDirFilesEx("/bitrix/cache/drdroid.keyrights/");
        DeleteDirFilesEx("/bitrix/components/drdroid/keyrights/");
        return true;
    }

    public function checkRequirements() {
        global $DB;
        $arErrors = array();
        $arStatus = array();

        if (empty($DB->type) || (strtolower($DB->type) !== "mysql")) {
            $arErrors["db"] = GetMessage("KEYRIGHTS_INSTALL_REQERROR_DB");
            $arStatus[] = "err";
        } else {
            $arStatus[] = "ok";
        }

        if (!CheckVersion(SM_VERSION, "20.5.400")) {
            $arErrors["bx"] = GetMessage("KEYRIGHTS_INSTALL_REQERROR_BX");
            $arStatus[] = "err";
        } else {
            $arStatus[] = "ok";
        }

        if (defined('PHP_VERSION_ID') && PHP_VERSION_ID < 80200) {
            $arErrors["php"] = GetMessage("KEYRIGHTS_INSTALL_REQERROR_PHP");
            $arStatus[] = "err";
        } else {
            $arStatus[] = "ok";
        }

        foreach (['openssl', 'curl'] as $extension) {
            if (!extension_loaded($extension)) {
                $arErrors[$extension] = GetMessage("KEYRIGHTS_INSTALL_REQERROR_" . strtoupper($extension));
                $arStatus[] = "err";
            } else {
                $arStatus[] = "ok";
            }
        }

        $module = new \CModule();
        if (!$module->IncludeModule("iblock")) {
            $arErrors["iblock"] = GetMessage("KEYRIGHTS_INSTALL_REQERROR_IBLOCK");
            $arStatus[] = "err";
        } else {
            $arStatus[] = "ok";
        }

        $docRoot = \CSite::GetSiteDocRoot(SITE_ID);
        if (!is_writable($docRoot . "/urlrewrite.php")) {
            $arErrors["rewrite"] = GetMessage("KEYRIGHTS_INSTALL_REQERROR_REWRITE");
            $arStatus[] = "err";
        } else {
            $arStatus[] = "ok";
        }

        return array(
            "errors" => $arErrors,
            "status" => $arStatus
        );
    }

    public function installRewrite() {
        $rewrite = new \CUrlRewriter();
        $rewrite->Add(array(
            "SITE_ID" => SITE_ID,
            "CONDITION" => "#^/keyrights/#",
            "ID" => "drdroid:keyrights",
            "PATH" => "/keyrights/index.php",
            "RULE" => ""
        ));
    }

    public function uninstallRewrite() {
        $rewrite = new \CUrlRewriter();
        $rewrite->Delete(array("CONDITION" => "#^/keyrights/#"));
    }

    public function installMenu() {
        $res = \CSite::GetList($sort = "sort", $order = "desc", array("LID" => SITE_ID));
        $site = $res->Fetch();
        $siteDir = !empty($site["DIR"]) ? $site["DIR"] : "/";
        $menuFile = \CSite::GetSiteDocRoot(SITE_ID) . $siteDir . "/.left.menu.php";
        $menuFile = str_replace("//", "/", $menuFile);

        $arMenu = \CFileMan::GetMenuArray($menuFile);
        $newMenuItem = array(
            "KeyRights",
            "/keyrights/",
            array(),
            array(),
            ""
        );
        $arMenu["aMenuLinks"][] = $newMenuItem;
        \CFileMan::SaveMenu(array(SITE_ID, $siteDir . "/.left.menu.php"), $arMenu["aMenuLinks"], $arMenu["sMenuTemplate"]);
    }

    public function uninstallMenu() {
        $res = \CSite::GetList($sort = "sort", $order = "desc", array("LID" => SITE_ID));
        $site = $res->Fetch();
        $siteDir = !empty($site["DIR"]) ? $site["DIR"] : "/";
        $menuFile = \CSite::GetSiteDocRoot(SITE_ID) . $siteDir . "/.left.menu.php";
        $menuFile = str_replace("//", "/", $menuFile);

        $arMenu = \CFileMan::GetMenuArray($menuFile);
        foreach ($arMenu["aMenuLinks"] as $index => $item) {
            if ($item[1] == "/keyrights/") {
                array_splice($arMenu["aMenuLinks"], $index, 1);
                break;
            }
        }
        \CFileMan::SaveMenu(array(SITE_ID, $siteDir . "/.left.menu.php"), $arMenu["aMenuLinks"], $arMenu["sMenuTemplate"]);
    }

    public function DoUninstall() {
        global $APPLICATION;
        $GLOBALS["errors"] = array();
        if (!IsModuleInstalled("drdroid.keyrights")) {
            return false;
        }
        if (!check_bitrix_sessid()) {
            return false;
        }

        $step = (int)($_REQUEST["step"] ?? 1);
        if ($step !== 2) {
            $APPLICATION->IncludeAdminFile(GetMessage("KEYRIGHTS_UNINSTALL"), $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/drdroid.keyrights/install/unstep1.php");
        } else {
            $moduleParams = isset($_REQUEST['module']) && is_array($_REQUEST['module'])
                ? $_REQUEST['module']
                : [];
            // The checkbox means “save tables/data”. Preserve the complete
            // decryptable dataset when it is selected, including its keys.
            $keepData = !empty($moduleParams['deleteTables']);
            if (!$keepData) {
                $this->UnInstallDB();
                $this->UnInstallIblocks();
                \COption::RemoveOption($this->MODULE_ID, "clientPassphrase");
                \COption::RemoveOption($this->MODULE_ID, "serverPassphrase");
            }
            $this->uninstallFiles();
            $this->uninstallRewrite();
            $this->uninstallMenu();
            UnRegisterModuleDependences("iblock", "OnAfterIBlockSectionDelete", $this->MODULE_ID, "CKeyrights", "onIblockSectionDelete");
            UnRegisterModuleDependences("main", "OnUserDelete", $this->MODULE_ID, "CKeyrights", "onUserDelete");
            UnRegisterModule("drdroid.keyrights");
            LocalRedirect("/bitrix/admin/partner_modules.php");
        }
        return true;
    }

    public function RunSQLBatch($filePath, $bReturnQuery = false, $bConvertCharset = false) {
        global $DB, $APPLICATION;
        if (!file_exists($filePath) || !is_file($filePath)) {
            return array("File " . $filePath . " is not found.");
        }
        $arErrors = array();
        $handle = @fopen($filePath, "rb");
        if ($handle) {
            $content = fread($handle, filesize($filePath));
            if ($bConvertCharset && strtoupper(LANG_CHARSET) == strtoupper("windows-1251")) {
                $content = $APPLICATION->ConvertCharset($content, "UTF-8", "windows-1251");
            }
            fclose($handle);
            $arQueries = $DB->ParseSqlBatch($content, $bReturnQuery);
            for ($i = 0; $i < count($arQueries); $i++) {
                if ($bReturnQuery) {
                    $arErrors[] = $arQueries[$i];
                } else {
                    $sql = str_replace("\r\n", "\n", $arQueries[$i]);
                    if (!$DB->Query($sql, true)) {
                        $arErrors[] = "<hr><pre>Query:\n" . $sql . "\n\nError:\n<font color=red>" . $DB->GetErrorMessage() . "</font></pre>";
                    }
                }
            }
        }
        if (count($arErrors) > 0) {
            return $arErrors;
        }
        return false;
    }

    public function UnInstallDB() {
        global $DB;
        $tables = array("kr_item", "kr_right");
        foreach ($tables as $table) {
            $sql = "DROP TABLE IF EXISTS `sib_" . $table . "`;";
            $DB->Query($sql);
        }
        return true;
    }

    private function ensureDatabaseSchema() {
        global $DB;
        $errors = [];
        $indexes = [
            ['sib_kr_item', 'ux_sib_kr_item_entity', 'UNIQUE KEY `ux_sib_kr_item_entity` (`entity_id`)'],
            ['sib_kr_item', 'ux_sib_kr_item_section', 'UNIQUE KEY `ux_sib_kr_item_section` (`section_id`)'],
            ['sib_kr_item', 'ix_sib_kr_item_owner', 'KEY `ix_sib_kr_item_owner` (`owner`)'],
            ['sib_kr_right', 'ix_sib_kr_right_user', 'KEY `ix_sib_kr_right_user` (`user`)'],
            ['sib_kr_right', 'ix_sib_kr_right_group', 'KEY `ix_sib_kr_right_group` (`group`)'],
            ['sib_kr_right', 'ix_sib_kr_right_timed', 'KEY `ix_sib_kr_right_timed` (`timed`)'],
        ];
        $legacyIndexes = [
            'ux_sib_kr_item_entity' => ['sib_kr_item', 'entity_id'],
            'ux_sib_kr_item_section' => ['sib_kr_item', 'section_id'],
        ];

        foreach ($indexes as [$table, $index, $definition]) {
            $exists = $DB->Query(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = '" . $DB->ForSql($index) . "'",
                true
            );
            if ($exists && $exists->Fetch()) {
                continue;
            }
            if (isset($legacyIndexes[$index])) {
                [$legacyTable, $legacyName] = $legacyIndexes[$index];
                $legacy = $DB->Query(
                    "SHOW INDEX FROM `{$legacyTable}` WHERE Key_name = '" . $DB->ForSql($legacyName) . "'",
                    true
                );
                if ($legacy && $legacy->Fetch()) {
                    $DB->Query("ALTER TABLE `{$legacyTable}` DROP INDEX `{$legacyName}`", true);
                }
            }
            if (!$DB->Query("ALTER TABLE `{$table}` ADD {$definition}", true)) {
                $errors[] = $DB->GetErrorMessage();
            }
        }

        $createResult = $DB->Query("SHOW CREATE TABLE `sib_kr_right`", true);
        $create = $createResult ? $createResult->Fetch() : false;
        $createSql = is_array($create) ? implode(' ', $create) : '';
        if (stripos($createSql, 'fk_sib_kr_right_item') === false) {
            $sql = "ALTER TABLE `sib_kr_right` ADD CONSTRAINT `fk_sib_kr_right_item` " .
                "FOREIGN KEY (`item_id`) REFERENCES `sib_kr_item` (`id`) ON DELETE CASCADE";
            if (!$DB->Query($sql, true)) {
                $errors[] = $DB->GetErrorMessage();
            }
        }

        return empty($errors) ? false : $errors;
    }
}
