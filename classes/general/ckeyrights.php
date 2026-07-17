<?php
/**
 * класс модуля
 * класс комбайн
 *    обрабатывает хуки битрикса
 *    маршрутизирует API запросы без Zend Framework
 */
use Bitrix\Main\Config\Option;
use Drdroid\Keyrights\Orm\ItemTable;
use Drdroid\Keyrights\Orm\RightTable;

class CKeyrights {
    private $_params;
    private $baseUrl = '/keyrights';

    const MODULE_ID = "drdroid.keyrights";

    public static function getInstance($params = array()) {
        static $_instance;
        if (!isset($_instance)) {
            $_instance = new self($params);
        }
        return $_instance;
    }

    private function __clone() {
        // do nothing
    }

    private function __wakeup() {
        // do nothing
    }

    final private function __construct($params) {
        if (!isset($params['BASE_PATH']) || empty($params['BASE_PATH'])) {
            $params['BASE_PATH'] = dirname(__FILE__);
        }

        $this->_params = $params;
    }

    public function run() {
        \Bitrix\Main\Loader::includeModule('iblock');

        // Determine base URL (usually /keyrights)
        $path = isset($_SERVER['REAL_FILE_PATH']) ? (string)$_SERVER['REAL_FILE_PATH'] : '';
        if (empty($path)) {
            $path = strlen($_SERVER['PHP_SELF']) < strlen($_SERVER['SCRIPT_NAME']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        }
        $baseUrl = rtrim(str_replace('\\', '/', dirname($path)), '/');
        $this->baseUrl = $baseUrl !== '' && $baseUrl !== '.' ? $baseUrl : '/';

        // Get relative route. A dedicated endpoint may pass it explicitly so
        // routing does not require mutation of request-wide server variables.
        if (isset($this->_params['ROUTE'])) {
            $route = trim((string)$this->_params['ROUTE'], '/');
        } else {
            $requestUri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '/';
            $requestPath = parse_url($requestUri, PHP_URL_PATH);
            $requestPath = is_string($requestPath) ? $requestPath : '/';
            if ($this->baseUrl !== '/'
                && ($requestPath === $this->baseUrl || strpos($requestPath, $this->baseUrl . '/') === 0)
            ) {
                $requestPath = substr($requestPath, strlen($this->baseUrl));
            }
            $route = trim($requestPath, '/');
        }

        // Check if this is an API call
        $apiPrefixes = ['crypt', 'api', 'exchange'];
        $isApi = false;
        foreach ($apiPrefixes as $prefix) {
            if ($route === $prefix || strpos($route, $prefix . '/') === 0) {
                $isApi = true;
                break;
            }
        }

        if ($isApi) {
            global $APPLICATION;

            // /keyrights/* is routed through keyrights/index.php, which includes
            // the site header before the component. Drop that HTML before
            // returning JSON, otherwise superagent tries to parse HTML + JSON.
            if (is_object($APPLICATION)) {
                $APPLICATION->RestartBuffer();
            }

            try {
                $controller = new \Drdroid\Keyrights\Controller\ApiController();
                $controller->dispatch($route);
            } catch (\Throwable $exception) {
                // Never leak a Bitrix/PHP exception page into the JSON API:
                // superagent would report it as “Parser is unable to parse
                // the response”. Keep the detail in the server log only.
                if (function_exists('AddMessage2Log')) {
                    AddMessage2Log($exception->getMessage(), self::MODULE_ID);
                }
                if (is_object($APPLICATION)) {
                    $APPLICATION->RestartBuffer();
                }
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'result' => 'error',
                    'error' => 'Internal KeyRights error',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            die();
        } else {
            $this->renderLayout();
        }
    }

    private function renderLayout() {
        global $APPLICATION, $USER;
        if (!is_object($USER) || !$USER->IsAuthorized()) {
            LocalRedirect('/auth/?backurl=' . urlencode($this->getSafeBackUrl()));
            die();
        }

        \Bitrix\Main\UI\Extension::load([
            'main.popup',
            'ui.buttons',
            'ui.forms',
            'ui.dialogs.messagebox',
        ]);

        $userModel = new \Drdroid\Keyrights\Model\User();
        $userData = $userModel->getUserData();

        $translations = self::getTranslations();

        $canUseVault = (new \Drdroid\Keyrights\Model\RightManager())->currentUserCanUseVault();
        $key = $canUseVault ? \Drdroid\Keyrights\Helper\Crypt::getClientPassphrase() : '';
        $keySalt = Option::get('drdroid.keyrights', 'clientKeySalt', '');
        if (!preg_match('/^[a-f0-9]{32}$/', $keySalt)) {
            $keySalt = bin2hex(random_bytes(16));
            Option::set('drdroid.keyrights', 'clientKeySalt', $keySalt);
        }

        $isWin1251 = defined('SITE_CHARSET') && (strtoupper(SITE_CHARSET) == 'WINDOWS-1251');
        
        if ($isWin1251) {
            $userDataUtf8 = $this->convertCharsetRecursive($userData, 'windows-1251', 'UTF-8');
            $translationsUtf8 = $this->convertCharsetRecursive($translations, 'windows-1251', 'UTF-8');
        } else {
            $userDataUtf8 = $userData;
            $translationsUtf8 = $translations;
        }

        $jsonFlags = JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT;

        $userDataJson = json_encode($userDataUtf8, $jsonFlags);
        $translationsJson = json_encode($translationsUtf8, $jsonFlags);
        $keyJson = json_encode($key, $jsonFlags);
        $keySaltJson = json_encode($keySalt, $jsonFlags);
        $sessId = bitrix_sessid();
        $sessIdJson = json_encode($sessId, $jsonFlags);
        $baseUrlJson = json_encode(rtrim($this->baseUrl, '/') . '/', $jsonFlags);
        $isAdmin = !empty($userData['admin']) ? 1 : 0;

        if ($userDataJson === false) {
            $userDataJson = '{}';
        }
        if ($translationsJson === false) {
            $translationsJson = '{}';
        }

        $script = <<<HTML
<script type="text/javascript" data-skip-moving="true">
    window.CONST = window.CONST || {};
    CONST.ACCESS = {
        NO: 0,
        CAN_READ: 1,
        CAN_WRITE: 2,
        CAN_OWN: 3
    };

    CONST.BX24 = {
        ENTITY: 'keyrights',
        USER_PASS_NAME: 'passPhrase',
        USER_PASS_ENTITY: 'keyrights.user'
    };

    CONST.backend = 'bitrix';
    CONST.appDomain = "";
    CONST.baseUrl = {$baseUrlJson};
    CONST.apiUrl = '/keyrights/api.php';
        CONST.isAdmin = {$isAdmin};
    CONST.staticPath = '/bitrix/components/drdroid/keyrights/static/';
    CONST.translator = {$translationsJson};
    CONST.key = {$keyJson};
    CONST.keySalt = {$keySaltJson};
    CONST.csrfToken = {$sessIdJson};

    window.userData = {$userDataJson};

    window.String._ = function(messageId) {
        if (window.CONST && window.CONST.translator && window.CONST.translator[messageId]) {
            return window.CONST.translator[messageId];
        }
        return messageId;
    };
</script>
HTML;

        $staticPath = '/bitrix/components/drdroid/keyrights/static';
        $staticRoot = $_SERVER['DOCUMENT_ROOT'] . $staticPath;
        $assetVersion = 0;
        foreach (['/css/style.css', '/js/libs/aes.js', '/js/libs/papaparse.js', '/js/bundle.js'] as $assetFile) {
            $absoluteAssetFile = $staticRoot . $assetFile;
            if (is_file($absoluteAssetFile)) {
                $assetVersion = max($assetVersion, (int)filemtime($absoluteAssetFile));
            }
        }

        // The public page preloads CSS before /bitrix/header.php so the
        // component's legacy Bootstrap does not override the portal template.
        // Keep a fallback for custom component placements.
        if (($this->_params['CSS_PRELOADED'] ?? 'N') !== 'Y') {
            echo '<link rel="stylesheet" href="' . $staticPath . '/css/style.css?v=' . $assetVersion . '">';
        }

        echo $script;
        echo '<div id="keyrights" class="standalone"></div>';
        echo '<script defer src="' . $staticPath . '/js/libs/aes.js?v=' . $assetVersion . '"></script>';
        echo '<script defer src="' . $staticPath . '/js/libs/papaparse.js?v=' . $assetVersion . '"></script>';
        echo '<script defer src="' . $staticPath . '/js/bundle.js?v=' . $assetVersion . '"></script>';
    }

    private function getSafeBackUrl() {
        $fallback = '/keyrights/';
        $requestUri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])
            ? $_SERVER['REQUEST_URI']
            : $fallback;
        if (preg_match('/[\x00-\x1F\x7F]/', $requestUri)) {
            return $fallback;
        }
        $parts = parse_url($requestUri);
        if (!is_array($parts)
            || isset($parts['scheme'])
            || isset($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port'])
        ) {
            return $fallback;
        }
        $path = isset($parts['path']) && is_string($parts['path']) ? $parts['path'] : '';
        if (preg_match('#^/keyrights(?:/|$)#', $path) !== 1) {
            return $fallback;
        }
        $query = isset($parts['query']) && is_string($parts['query']) && $parts['query'] !== ''
            ? '?' . $parts['query']
            : '';
        return $path . $query;
    }

    public static function getTranslations() {
        $language = defined('LANGUAGE_ID') ? strtolower((string)LANGUAGE_ID) : 'ru';
        if ($language === 'uk') {
            $language = 'ua';
        }
        if (!in_array($language, ['ru', 'ua', 'en'], true)) {
            $language = 'en';
        }

        $translations = [];
        $languages = array_values(array_unique(['ru', $language]));

        foreach ($languages as $lang) {
            $files = [
                $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/drdroid.keyrights/lang/' . $lang . '/messages.php',
            ];

            foreach ($files as $file) {
                if (is_file($file)) {
                    $messages = require($file);
                    if (is_array($messages)) {
                        $translations = array_merge($translations, $messages);
                        break;
                    }
                }
            }
        }

        return $translations;
    }

    private function convertCharsetRecursive($value, $from, $to) {
        global $APPLICATION;
        if (is_array($value)) {
            foreach ($value as $key => $subVal) {
                $value[$key] = $this->convertCharsetRecursive($subVal, $from, $to);
            }
        } elseif (is_string($value)) {
            return $APPLICATION->ConvertCharset($value, $from, $to);
        }
        return $value;
    }

    public static function onIblockSectionDelete($arFields) {
        static $departmentIblockId;
        if (is_null($departmentIblockId)) {
            $departmentIblockId = (int)Option::get('intranet', 'iblock_structure', '0');
            if (!$departmentIblockId) {
                $bxIblock = new CIBlock();
                $res = $bxIblock->GetList(array(), array('CODE' => 'departments'));
                $iblock = $res->Fetch();
                $departmentIblockId = is_array($iblock) ? $iblock['ID'] : 0;
            }
        }

        if ($departmentIblockId && $arFields['IBLOCK_ID'] == $departmentIblockId) {
            $rights = RightTable::getList([
                'select' => ['ID'],
                'filter' => ['=GROUP' => (int)$arFields['ID'], '=USER' => null],
            ]);
            while ($right = $rights->fetch()) {
                RightTable::delete((int)$right['ID']);
            }
        }
    }

    public static function onUserDelete($userId) {
        $rights = RightTable::getList([
            'select' => ['ID'],
            'filter' => ['=USER' => (int)$userId, '=GROUP' => null],
        ]);
        while ($right = $rights->fetch()) {
            RightTable::delete((int)$right['ID']);
        }

        $items = ItemTable::getList([
            'select' => ['ID'],
            'filter' => ['=OWNER' => (int)$userId],
        ]);
        while ($item = $items->fetch()) {
            ItemTable::update((int)$item['ID'], ['OWNER' => 1]);
        }

        // потом выкосить этого юзера из авторов разделов и элементов нашего инфоблока
        \Bitrix\Main\Loader::includeModule('iblock');
        $passIblockId = Option::get(CKeyrights::MODULE_ID, 'iblockId', '-1');

        if (empty($passIblockId)) {
            return;
        }

        $ibe = new CIBlockElement();
        $elRes = $ibe->GetList(array(), array('CREATED_BY' => $userId, 'IBLOCK_ID' => $passIblockId));
        while ($arRes = $elRes->Fetch()) {
            $ibe->Update($arRes['ID'], array('CREATED_BY' => 1));
        }

        $ibs = new CIBlockSection();
        $sectionRes = $ibs->GetList([], [
            'IBLOCK_ID' => (int)$passIblockId,
            'CREATED_BY' => (int)$userId,
        ], false, ['ID']);
        while ($section = $sectionRes->Fetch()) {
            $ibs->Update((int)$section['ID'], ['CREATED_BY' => 1]);
        }
    }
}
