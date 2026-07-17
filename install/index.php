<?php
IncludeModuleLangFile(__FILE__);

class drdroid_keyrights extends CModule {
    var $MODULE_ID = 'drdroid.keyrights';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $PUBLIC_DIR = "/keyrights/";
    var $IBLOCK_CODE = "keyrights";
    var $HISTORY_IBLOCK_CODE = "keyrights.history";
    var $IBLOCK_TYPE_ID = "keyrights";
    var $installErrors = array();

    public function __construct() {
        $arModuleVersion = array();
        include(__DIR__ . "/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = GetMessage("KEYRIGHTS_MODULE_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("KEYRIGHTS_MODULE_DESCRIPTION");
        $this->PARTNER_NAME = "DrDroid";
        $this->PARTNER_URI = "https://github.com/elftorg/keyrights";
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
        // step1.php is included by Bitrix in the global scope.
        $GLOBALS["reqCheck"] = is_array($reqCheck) ? $reqCheck : array();
        $step = (int)($_REQUEST["step"] ?? 0);

        if ($step == 0 || count($reqCheck["errors"]) > 0) {
            $APPLICATION->IncludeAdminFile(GetMessage("KEYRIGHTS_INSTALL"), $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/drdroid.keyrights/install/step1.php");
        } elseif ($step == 1) {
            $keyphrase = isset($_REQUEST["keyphrase"]) && is_string($_REQUEST["keyphrase"])
                ? $_REQUEST["keyphrase"]
                : '';
            $existingClientKey = \COption::GetOptionString($this->MODULE_ID, "clientPassphrase", '') !== ''
                || \COption::GetOptionString($this->MODULE_ID, "clientPassphraseEncrypted", '') !== '';
            if (!$existingClientKey && strlen($keyphrase) < 16) {
                $GLOBALS["errors"]["keyphrase"] = true;
                $APPLICATION->IncludeAdminFile(GetMessage("KEYRIGHTS_INSTALL"), $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/drdroid.keyrights/install/step1.php");
            } else {
                $option = new \COption();
                $clientKeyCreated = !$existingClientKey;
                $clientSaltCreated = $option->GetOptionString($this->MODULE_ID, "clientKeySalt", '') === '';
                $serverKeyCreated = $option->GetOptionString($this->MODULE_ID, "serverPassphrase", '') === ''
                    && $option->GetOptionString($this->MODULE_ID, "serverPassphraseEncrypted", '') === ''
                    && $option->GetOptionString($this->MODULE_ID, "serverKeySource", '') === '';
                if ($clientKeyCreated) {
                    $protectedClientKey = $this->protectSecret($keyphrase, 'client');
                    if ($protectedClientKey !== false) {
                        $option->SetOptionString($this->MODULE_ID, "clientPassphraseEncrypted", $protectedClientKey);
                        $option->SetOptionString($this->MODULE_ID, "clientKeySource", "bitrix-crypto");
                    } else {
                        $option->SetOptionString($this->MODULE_ID, "clientPassphrase", $keyphrase);
                        $option->SetOptionString($this->MODULE_ID, "clientKeySource", "option");
                    }
                }
                if ($clientSaltCreated) {
                    $option->SetOptionString($this->MODULE_ID, "clientKeySalt", bin2hex(random_bytes(16)));
                }
                if ($serverKeyCreated) {
                    $environmentKey = (string)getenv('KEYRIGHTS_SERVER_KEY');
                    if (strlen($environmentKey) >= 32) {
                        $option->SetOptionString($this->MODULE_ID, "serverKeySource", "environment");
                    } else {
                        $serverKey = bin2hex(random_bytes(32));
                        $protectedServerKey = $this->protectSecret($serverKey, 'server');
                        if ($protectedServerKey !== false) {
                            $option->SetOptionString($this->MODULE_ID, "serverPassphraseEncrypted", $protectedServerKey);
                            $option->SetOptionString($this->MODULE_ID, "serverKeySource", "bitrix-crypto");
                        } else {
                            $option->SetOptionString($this->MODULE_ID, "serverPassphrase", $serverKey);
                            $option->SetOptionString($this->MODULE_ID, "serverKeySource", "option");
                        }
                    }
                }
                try {
                    if (!$this->InstallDB()) {
                        throw new \RuntimeException(implode('; ', (array)$this->installErrors));
                    }
                    if (!$this->InstallIblocks()) {
                        throw new \RuntimeException('Не удалось создать инфоблоки KeyRights');
                    }
                    if (!$this->installFiles()) {
                        throw new \RuntimeException('Не удалось скопировать файлы KeyRights');
                    }
                    if (!$this->installRewrite()) {
                        throw new \RuntimeException('Не удалось добавить правило URL KeyRights');
                    }
                    if (!$this->installMenu()) {
                        throw new \RuntimeException('Не удалось добавить пункт меню KeyRights');
                    }
                    $this->upgradeStoredSecrets();
                } catch (\Throwable $exception) {
                    if ($clientKeyCreated) {
                        $option->RemoveOption($this->MODULE_ID, "clientPassphrase");
                        $option->RemoveOption($this->MODULE_ID, "clientPassphraseEncrypted");
                        $option->RemoveOption($this->MODULE_ID, "clientKeySource");
                    }
                    if ($clientSaltCreated) $option->RemoveOption($this->MODULE_ID, "clientKeySalt");
                    if ($serverKeyCreated) {
                        $option->RemoveOption($this->MODULE_ID, "serverPassphrase");
                        $option->RemoveOption($this->MODULE_ID, "serverPassphraseEncrypted");
                        $option->RemoveOption($this->MODULE_ID, "serverKeySource");
                    }
                    $this->installErrors = array($exception->getMessage());
                    $GLOBALS["errors"] = $this->installErrors;
                    return false;
                }
                RegisterModule("drdroid.keyrights");
                RegisterModuleDependences("iblock", "OnAfterIBlockSectionDelete", $this->MODULE_ID, "CKeyrights", "onIblockSectionDelete");
                RegisterModuleDependences("main", "OnUserDelete", $this->MODULE_ID, "CKeyrights", "onUserDelete");
                $GLOBALS["errors"] = $this->installErrors;
                LocalRedirect($this->PUBLIC_DIR);
            }
        }
        return true;
    }

    public function InstallDB() {
        global $DB, $APPLICATION;
        $this->installErrors = false;

        $dbType = $this->getDatabaseType();
        if (!in_array($dbType, array("mysql", "pgsql"), true)) {
            $this->installErrors = array(GetMessage("KEYRIGHTS_INSTALL_REQERROR_DB"));
        } elseif (!$this->tableExists("dr_kr_item") || !$this->tableExists("dr_kr_right")) {
            $sqlFile = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/drdroid.keyrights/install/db/" . $dbType . "/install.sql";
            $sqlFile = str_replace("//", "/", $sqlFile);
            $this->installErrors = $this->RunSQLBatch($sqlFile);
        }
        if ($this->installErrors === false) {
            $this->installErrors = $this->migrateLegacyDatabase();
        }
        if ($this->installErrors === false) {
            $this->installErrors = $this->ensureDatabaseSchema();
        }
        if ($this->installErrors !== false) {
            $APPLICATION->ThrowException(implode("<br>", $this->installErrors));
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

        $typeRes = $ibType->GetByID($this->IBLOCK_TYPE_ID);
        $type = $typeRes->Fetch();
        if (!$type) {
            $arTypeFields = array(
                "ID" => $this->IBLOCK_TYPE_ID,
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
            if (!$ibType->Add($arTypeFields)) {
                return false;
            }
        }

        $defSite = $site->GetDefSite();

        // 1. Create main keyrights IBlock
        $res = $ib->GetList(array(), array("TYPE" => $this->IBLOCK_TYPE_ID, "CODE" => $this->IBLOCK_CODE));
        $ibData = $res->Fetch();
        if (!$ibData) {
            $arFields = array(
                "ACTIVE" => "Y",
                "NAME" => "Drdroid.Keyrights",
                "CODE" => $this->IBLOCK_CODE,
                "LIST_PAGE_URL" => $this->PUBLIC_DIR,
                "DETAIL_PAGE_URL" => $this->PUBLIC_DIR,
                "IBLOCK_TYPE_ID" => $this->IBLOCK_TYPE_ID,
                "SITE_ID" => array($defSite),
                "SORT" => 100,
                "GROUP_ID" => array("2" => "R"),
                "VERSION" => 2
            );
            $iblockId = $ib->Add($arFields);
            if (!$iblockId) {
                return false;
            }

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
            if (!$ibProp->Add($arPropFields)) {
                return false;
            }
        } else {
            $iblockId = $ibData["ID"];
        }

        // 2. Create history keyrights IBlock
        $res = $ib->GetList(array(), array("TYPE" => $this->IBLOCK_TYPE_ID, "CODE" => $this->HISTORY_IBLOCK_CODE));
        $historyData = $res->Fetch();
        if (!$historyData) {
            $arFields = array(
                "ACTIVE" => "Y",
                "NAME" => "Drdroid.Keyrights.History",
                "CODE" => $this->HISTORY_IBLOCK_CODE,
                "LIST_PAGE_URL" => $this->PUBLIC_DIR,
                "DETAIL_PAGE_URL" => $this->PUBLIC_DIR,
                "IBLOCK_TYPE_ID" => $this->IBLOCK_TYPE_ID,
                "SITE_ID" => array($defSite),
                "SORT" => 200,
                "GROUP_ID" => array("2" => "R"),
                "VERSION" => 2
            );
            $historyIblockId = $ib->Add($arFields);
            if (!$historyIblockId) {
                return false;
            }

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
            if (!$ibProp->Add($arPropFields)) {
                return false;
            }

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
            if (!$ibProp->Add($arPropFields)) {
                return false;
            }
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
        $res = $ib->GetList(array(), array("ID" => $iblockId, "TYPE" => $this->IBLOCK_TYPE_ID, "CODE" => $this->IBLOCK_CODE));
        $ibData = $res->Fetch();
        if ($ibData) {
            $ib->Delete($ibData["ID"]);
        }

        $historyIblockId = \COption::GetOptionString($this->MODULE_ID, "historyIblockId");
        $res = $ib->GetList(array(), array("ID" => $historyIblockId, "TYPE" => $this->IBLOCK_TYPE_ID, "CODE" => $this->HISTORY_IBLOCK_CODE));
        $historyData = $res->Fetch();
        if ($historyData) {
            $ib->Delete($historyData["ID"]);
        }

        $typeRes = $ibType->GetByID($this->IBLOCK_TYPE_ID);
        $type = $typeRes->Fetch();
        if ($type) {
            $res = $ib->GetList(array(), array("TYPE" => $this->IBLOCK_TYPE_ID));
            if (!$res->Fetch()) {
                $ibType->Delete($this->IBLOCK_TYPE_ID);
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

        if (!CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/drdroid.keyrights/install/frontend", $_SERVER["DOCUMENT_ROOT"] . $this->PUBLIC_DIR, true, true)) {
            return false;
        }

        // Copy components selectively to avoid copying node_modules
        $componentSrc = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/drdroid.keyrights/install/components/drdroid/keyrights";
        $componentDst = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/drdroid/keyrights";

        return CopyDirFiles($componentSrc . "/lang", $componentDst . "/lang", true, true)
            && CopyDirFiles($componentSrc . "/templates", $componentDst . "/templates", true, true)
            && CopyDirFiles($componentSrc . "/static", $componentDst . "/static", true, true)
            && copy($componentSrc . "/.description.php", $componentDst . "/.description.php")
            && copy($componentSrc . "/.parameters.php", $componentDst . "/.parameters.php")
            && copy($componentSrc . "/component.php", $componentDst . "/component.php");
    }

    public function uninstallFiles() {
        $baseUrl = rtrim($this->PUBLIC_DIR, "/");
        DeleteDirFilesEx($baseUrl);
        DeleteDirFilesEx("/bitrix/cache/drdroid.keyrights/");
        DeleteDirFilesEx("/bitrix/components/drdroid/keyrights/");
        return true;
    }

    public function checkRequirements() {
        global $DB;
        $arErrors = array();
        $arStatus = array();

        if (!in_array($this->getDatabaseType(), array("mysql", "pgsql"), true)) {
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

        foreach (['openssl'] as $extension) {
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
        $rewrite->Delete(array("CONDITION" => "#^/keyrights/#"));
        $rewrite->Add(array(
            "SITE_ID" => SITE_ID,
            "CONDITION" => "#^/keyrights/#",
            "ID" => "drdroid:keyrights",
            "PATH" => "/keyrights/index.php",
            "RULE" => ""
        ));
        return count($rewrite->GetList(array(
            "SITE_ID" => SITE_ID,
            "CONDITION" => "#^/keyrights/#",
            "ID" => "drdroid:keyrights",
        ))) > 0;
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
        foreach ($arMenu["aMenuLinks"] as $item) {
            if (($item[1] ?? '') === "/keyrights/") {
                return true;
            }
        }
        $arMenu["aMenuLinks"][] = $newMenuItem;
        \CFileMan::SaveMenu(array(SITE_ID, $siteDir . "/.left.menu.php"), $arMenu["aMenuLinks"], $arMenu["sMenuTemplate"]);
        $savedMenu = \CFileMan::GetMenuArray($menuFile);
        foreach ($savedMenu["aMenuLinks"] as $item) {
            if (($item[1] ?? '') === "/keyrights/") {
                return true;
            }
        }
        return false;
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
                \COption::RemoveOption($this->MODULE_ID, "clientPassphraseEncrypted");
                \COption::RemoveOption($this->MODULE_ID, "clientKeySource");
                \COption::RemoveOption($this->MODULE_ID, "clientKeySalt");
                \COption::RemoveOption($this->MODULE_ID, "serverPassphrase");
                \COption::RemoveOption($this->MODULE_ID, "serverPassphraseEncrypted");
                \COption::RemoveOption($this->MODULE_ID, "serverKeySource");
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
            if ($arQueries === false || $arQueries === null) {
                $arQueries = array();
            } elseif (!is_array($arQueries)) {
                $arQueries = array($arQueries);
            }
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
        $tables = array("dr_kr_right", "dr_kr_item");
        $cascade = $this->getDatabaseType() === "pgsql" ? " CASCADE" : "";
        foreach ($tables as $table) {
            $sql = "DROP TABLE IF EXISTS " . $this->quoteIdentifier($table) . $cascade . ";";
            $DB->Query($sql);
        }
        return true;
    }

    /**
     * Copy data from the tables used by releases before 2.0.1. The legacy
     * tables are intentionally left untouched so an administrator can verify
     * the migrated data and keep a rollback source.
     */
    private function migrateLegacyDatabase() {
        global $DB;

        $legacyItemsExist = $this->tableExists("sib_kr_item");
        $legacyRightsExist = $this->tableExists("sib_kr_right");
        if (!$legacyItemsExist && !$legacyRightsExist) {
            return false;
        }
        if (!$legacyItemsExist || !$legacyRightsExist) {
            return array(GetMessage("KEYRIGHTS_INSTALL_LEGACY_TABLES_INCOMPLETE"));
        }

        $itemMap = array();
        $DB->StartTransaction();
        try {
            $itemResult = $DB->Query(
                "SELECT " . $this->quoteIdentifier("id") . ", " .
                $this->quoteIdentifier("entity_id") . ", " .
                $this->quoteIdentifier("section_id") . ", " .
                $this->quoteIdentifier("owner") .
                " FROM " . $this->quoteIdentifier("sib_kr_item"),
                true
            );
            if (!$itemResult) {
                throw new \RuntimeException($DB->GetErrorMessage());
            }

            while ($legacyItem = $itemResult->Fetch()) {
                $legacyId = (int)$this->rowValue($legacyItem, "id");
                $targetId = $this->findMigratedItem($legacyItem);
                if ($targetId === null) {
                    $fields = array(
                        "ENTITY_ID" => $this->nullableInteger($this->rowValue($legacyItem, "entity_id")),
                        "SECTION_ID" => $this->nullableInteger($this->rowValue($legacyItem, "section_id")),
                        "OWNER" => (int)$this->rowValue($legacyItem, "owner"),
                    );
                    if (!$this->rowExists("dr_kr_item", "id", $legacyId)) {
                        $fields["ID"] = $legacyId;
                    }
                    $targetId = $DB->Add("dr_kr_item", $fields);
                    if (!$targetId) {
                        throw new \RuntimeException($DB->GetErrorMessage());
                    }
                    $targetId = (int)$targetId;
                }
                $itemMap[$legacyId] = $targetId;
            }

            $rightResult = $DB->Query(
                "SELECT " . implode(", ", array_map(array($this, "quoteIdentifier"), array(
                    "id", "item_id", "edit", "blocked", "timed", "user", "group"
                ))) . " FROM " . $this->quoteIdentifier("sib_kr_right"),
                true
            );
            if (!$rightResult) {
                throw new \RuntimeException($DB->GetErrorMessage());
            }

            while ($legacyRight = $rightResult->Fetch()) {
                $legacyItemId = (int)$this->rowValue($legacyRight, "item_id");
                if (!isset($itemMap[$legacyItemId])) {
                    throw new \RuntimeException("Legacy right references unknown item " . $legacyItemId);
                }
                $fields = array(
                    "ITEM_ID" => $itemMap[$legacyItemId],
                    "EDIT" => (int)$this->rowValue($legacyRight, "edit"),
                    "BLOCKED" => (int)$this->rowValue($legacyRight, "blocked"),
                    "TIMED" => $this->rowValue($legacyRight, "timed"),
                    "USER" => $this->nullableInteger($this->rowValue($legacyRight, "user")),
                    "GROUP" => $this->nullableInteger($this->rowValue($legacyRight, "group")),
                );
                if ($this->migratedRightExists($fields)) {
                    continue;
                }
                $legacyRightId = (int)$this->rowValue($legacyRight, "id");
                if (!$this->rowExists("dr_kr_right", "id", $legacyRightId)) {
                    $fields["ID"] = $legacyRightId;
                }
                if (!$DB->Add("dr_kr_right", $fields)) {
                    throw new \RuntimeException($DB->GetErrorMessage());
                }
            }

            $this->synchronizePostgresSequences();
            $DB->Commit();
        } catch (\Throwable $exception) {
            $DB->Rollback();
            return array(GetMessage("KEYRIGHTS_INSTALL_LEGACY_MIGRATION_ERROR") . ": " . $exception->getMessage());
        }

        return false;
    }

    private function findMigratedItem(array $legacyItem) {
        global $DB;
        $entityId = $this->nullableInteger($this->rowValue($legacyItem, "entity_id"));
        $sectionId = $this->nullableInteger($this->rowValue($legacyItem, "section_id"));
        if ($entityId !== null) {
            $column = "entity_id";
            $value = $entityId;
        } elseif ($sectionId !== null) {
            $column = "section_id";
            $value = $sectionId;
        } else {
            $column = "id";
            $value = (int)$this->rowValue($legacyItem, "id");
        }
        $sql = "SELECT " . $this->quoteIdentifier("id") . " FROM " .
            $this->quoteIdentifier("dr_kr_item") . " WHERE " .
            $this->quoteIdentifier($column) . " = " . (int)$value;
        $result = $DB->Query($sql, true);
        if (!$result) {
            throw new \RuntimeException($DB->GetErrorMessage());
        }
        $row = $result->Fetch();
        return $row ? (int)$this->rowValue($row, "id") : null;
    }

    private function migratedRightExists(array $fields) {
        global $DB;
        $conditions = array();
        foreach ($fields as $column => $value) {
            $quotedColumn = $this->quoteIdentifier(strtolower($column));
            if ($value === null || $value === "") {
                $conditions[] = $quotedColumn . " IS NULL";
            } else {
                $conditions[] = $quotedColumn . " = '" . $DB->ForSql((string)$value) . "'";
            }
        }
        $sql = "SELECT " . $this->quoteIdentifier("id") . " FROM " .
            $this->quoteIdentifier("dr_kr_right") . " WHERE " . implode(" AND ", $conditions);
        $result = $DB->Query($sql, true);
        if (!$result) {
            throw new \RuntimeException($DB->GetErrorMessage());
        }
        return (bool)$result->Fetch();
    }

    private function rowExists($table, $column, $value) {
        global $DB;
        $sql = "SELECT " . $this->quoteIdentifier($column) . " FROM " .
            $this->quoteIdentifier($table) . " WHERE " . $this->quoteIdentifier($column) .
            " = " . (int)$value;
        $result = $DB->Query($sql, true);
        if (!$result) {
            throw new \RuntimeException($DB->GetErrorMessage());
        }
        return (bool)$result->Fetch();
    }

    private function synchronizePostgresSequences() {
        global $DB;
        if ($this->getDatabaseType() !== "pgsql") {
            return;
        }
        foreach (array("dr_kr_item", "dr_kr_right") as $table) {
            $safeTable = $DB->ForSql($table);
            $sql = "SELECT setval(pg_get_serial_sequence('" . $safeTable . "', 'id'), " .
                "COALESCE(MAX(" . $this->quoteIdentifier("id") . "), 1), COUNT(*) > 0) FROM " .
                $this->quoteIdentifier($table);
            if (!$DB->Query($sql, true)) {
                throw new \RuntimeException($DB->GetErrorMessage());
            }
        }
    }

    private function rowValue(array $row, $column) {
        if (array_key_exists($column, $row)) {
            return $row[$column];
        }
        $upper = strtoupper($column);
        return array_key_exists($upper, $row) ? $row[$upper] : null;
    }

    private function nullableInteger($value) {
        return $value === null || $value === "" ? null : (int)$value;
    }

    private function ensureDatabaseSchema() {
        global $DB;
        $errors = [];
        $indexes = [
            array('dr_kr_item', 'ux_dr_kr_item_entity', array('entity_id'), true),
            array('dr_kr_item', 'ux_dr_kr_item_section', array('section_id'), true),
            array('dr_kr_item', 'ix_dr_kr_item_owner', array('owner'), false),
            array('dr_kr_right', 'ix_dr_kr_right_item_id', array('item_id'), false),
            array('dr_kr_right', 'ix_dr_kr_right_user', array('user'), false),
            array('dr_kr_right', 'ix_dr_kr_right_group', array('group'), false),
            array('dr_kr_right', 'ix_dr_kr_right_timed', array('timed'), false),
        ];

        foreach ($indexes as $indexDefinition) {
            list($table, $index, $columns, $unique) = $indexDefinition;
            if ($this->indexExists($table, $columns)) {
                continue;
            }
            $quotedColumns = array_map(function ($column) {
                return $this->quoteIdentifier($column);
            }, $columns);
            $sql = "CREATE " . ($unique ? "UNIQUE " : "") . "INDEX " .
                $this->quoteIdentifier($index) . " ON " . $this->quoteIdentifier($table) .
                " (" . implode(", ", $quotedColumns) . ")";
            if (!$DB->Query($sql, true)) {
                $errors[] = $DB->GetErrorMessage();
            }
        }

        if (!$this->foreignKeyExists("dr_kr_right", "fk_dr_kr_right_item")) {
            $sql = "ALTER TABLE " . $this->quoteIdentifier("dr_kr_right") .
                " ADD CONSTRAINT " . $this->quoteIdentifier("fk_dr_kr_right_item") .
                " FOREIGN KEY (" . $this->quoteIdentifier("item_id") . ") REFERENCES " .
                $this->quoteIdentifier("dr_kr_item") . " (" . $this->quoteIdentifier("id") . ") ON DELETE CASCADE";
            if (!$DB->Query($sql, true)) {
                $errors[] = $DB->GetErrorMessage();
            }
        }

        return empty($errors) ? false : $errors;
    }

    private function getDatabaseType() {
        global $DB;
        return strtolower((string)($DB->type ?? ""));
    }

    private function protectSecret($secret, $purpose) {
        if (!class_exists('\Bitrix\Main\ORM\Fields\CryptoField')
            || !method_exists('\Bitrix\Main\ORM\Fields\CryptoField', 'getDefaultKey')
            || !class_exists('\Bitrix\Main\Security\Cipher')
        ) {
            return false;
        }
        try {
            $cryptoKey = \Bitrix\Main\ORM\Fields\CryptoField::getDefaultKey();
            if (!is_string($cryptoKey) || $cryptoKey === '') {
                return false;
            }
            $cipher = new \Bitrix\Main\Security\Cipher();
            return base64_encode($cipher->encrypt(
                (string)$secret,
                $cryptoKey . '|drdroid.keyrights|' . (string)$purpose
            ));
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * Convert secrets created by older module versions from plain options to
     * storage encrypted with the Bitrix installation key. The operation is
     * deliberately best-effort: installations without a configured Bitrix
     * crypto key keep the compatible option-based source.
     */
    private function upgradeStoredSecrets() {
        $option = new \COption();
        foreach (array('client', 'server') as $purpose) {
            $name = $purpose . 'Passphrase';
            $sourceName = $purpose . 'KeySource';
            $source = (string)$option->GetOptionString($this->MODULE_ID, $sourceName, 'option');
            if ($source !== '' && $source !== 'option') {
                continue;
            }

            $plain = (string)$option->GetOptionString($this->MODULE_ID, $name, '');
            if ($plain === '') {
                continue;
            }
            $protected = $this->protectSecret($plain, $purpose);
            if ($protected === false) {
                continue;
            }

            $option->SetOptionString($this->MODULE_ID, $name . 'Encrypted', $protected);
            $option->SetOptionString($this->MODULE_ID, $sourceName, 'bitrix-crypto');
            $option->RemoveOption($this->MODULE_ID, $name);
        }
    }

    private function quoteIdentifier($identifier) {
        global $DB;
        if (method_exists($DB, "quote")) {
            return $DB->quote($identifier);
        }
        return "`" . str_replace("`", "", $identifier) . "`";
    }

    private function tableExists($table) {
        global $DB;
        return method_exists($DB, "TableExists") && $DB->TableExists($table);
    }

    private function indexExists($table, array $columns) {
        global $DB;
        return method_exists($DB, "IndexExists") && $DB->IndexExists($table, $columns, true);
    }

    private function foreignKeyExists($table, $constraint) {
        global $DB;
        $dbType = $this->getDatabaseType();
        $table = $DB->ForSql($table);
        $constraint = $DB->ForSql($constraint);
        if ($dbType === "pgsql") {
            $sql = "SELECT 1 FROM pg_constraint c " .
                "INNER JOIN pg_class t ON t.oid = c.conrelid " .
                "WHERE t.relname = '{$table}' AND c.conname = '{$constraint}'";
        } else {
            $sql = "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS " .
                "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}' " .
                "AND CONSTRAINT_NAME = '{$constraint}'";
        }
        $result = $DB->Query($sql, true);
        return $result && $result->Fetch();
    }
}
