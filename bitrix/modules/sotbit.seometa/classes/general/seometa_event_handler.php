<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Text\Emoji;
use Bitrix\Main\Context;
use Sotbit\Seometa\Helper\Linker;
use Sotbit\Seometa\Helper\Menu;
use Sotbit\Seometa\Helper\OGraphTWCard;
use Sotbit\Seometa\Link\ReindexWriter;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\Orm\SeometaStatisticsTable;
use Sotbit\Seometa\Orm\SeometaUrlTable;

class CSeoMetaEvents
{
    protected static $lAdmin;
    private static $i = 1;
    private const MODULE_NAME = 'sotbit.seometa';
    private static $idStat = false;
    private static $googleBot = false;
    private static $yandexBot = false;

    static function OnInit(
    ) {
        return [
            "TABSET" => "seometa",
            "GetTabs" => [
                "CSeoMetaEvents",
                "GetTabs"
            ],
            "ShowTab" => [
                "CSeoMetaEvents",
                "ShowTab"
            ],
            "Action" => [
                "CSeoMetaEvents",
                "Action"
            ],
            "Check" => [
                "CSeoMetaEvents",
                "Check"
            ]
        ];
    }

    public static function OnBuildGlobalMenuHandler(
        &$arGlobalMenu,
        &$arModuleMenu
    ) {
        Menu::getAdminMenu($arGlobalMenu, $arModuleMenu);
    }

    static function Action(
        $arArgs
    ) {
        return true;
    }

    static function Check(
        $arArgs
    ) {
        return true;
    }

    static function GetTabs(
        $arArgs
    ) {
        global $APPLICATION;
        if ($APPLICATION->GetGroupRight(self::MODULE_NAME) == "D") {
            return false;
        }

        $arTabs = [
            [
                "DIV" => "url-mode",
                "TAB" => GetMessage('seometa_title'),
                "ICON" => "sale",
                "TITLE" => GetMessage('seometa_list'),
                "SORT" => 5
            ]
        ];

        return $arTabs;
    }

    static function ShowTab(
        $divName,
        $arArgs,
        $bVarsFromForm
    ) {
        if ($divName == "url-mode") {
            define('B_ADMIN_SUBCONDITIONS', 1);
            define('B_ADMIN_SUBCONDITIONS_LIST', false);
            ?>
            <tr id="tr_COUPONS">
                <td colspan="2">
                    <?
                    require($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/sotbit.seometa/admin/templates/sub_list.php');
                    ?>
                </td>
            </tr>
            <?
        }
    }

    static function requestStrToUpper($queryValues)
    {
        foreach ($queryValues as $key => $queryValue) {
            if(is_array($queryValue)){
                if(is_string($key)){
                    $queryValues[mb_strtoupper($key)] = self::requestStrToUpper($queryValue);
                    unset($queryValues[$key]);
                }else{
                    $queryValues[$key] = self::requestStrToUpper($queryValue);
                }
            }else{
                if (is_string($key)) {
                    $queryValues[mb_strtoupper($key)] = mb_strtoupper($queryValue);
                    unset($queryValues[$key]);
                } else {
                    $queryValues[$key] = mb_strtoupper($queryValue);
                }
            }
        }
        return $queryValues;
    }

    static function checkRequestExceptions($excludeParams, $queryValues): int
    {
        foreach ($excludeParams as $key => $excludeParam) {
            if(is_array($excludeParam)){
                return self::checkRequestExceptions($excludeParam, $queryValues[$key]);
            }
            elseif ($excludeParam && ($queryValues[$key] == $excludeParam) || ($queryValues[$key] && !$excludeParam)) {
                return 1;
            }
        }
        return 0;
    }

    static function PageStart() {
        global $APPLICATION, $PAGEN_1;

        //off autocompozite
        //\Bitrix\Main\Data\StaticHtmlCache::getInstance()->markNonCacheable();

        if (mb_strpos($APPLICATION->GetCurPage(false), '/bitrix') === 0) {
            return;
        }

        $excludeParams = Option::get(self::MODULE_NAME,
            'PARAMS_EXCEPTION_SETTINGS',
            '',
            SITE_ID
        );
        if ($excludeParams = explode(";", $excludeParams)) {
            foreach ($excludeParams as $key => $excludeParam) {
                $value = explode('=', $excludeParam);
                $preg = preg_match('/\[(.*?)\]/', $value[0], $matches);
                if(!empty($matches)){
                    $value[0] = str_replace($matches[0], '', $value[0]);
                    if($matches[1]){
                        $excludeParams[mb_strtoupper($value[0])][mb_strtoupper($matches[1])] = mb_strtoupper($value[1]) ?: '';
                    }else{
                        $excludeParams[mb_strtoupper($value[0])][] = mb_strtoupper($value[1]) ?: '';
                    }
                }else{
                    $excludeParams[mb_strtoupper($value[0])] = mb_strtoupper($value[1]) ?: '';
                }
                unset($excludeParams[$key]);
            }
        }

        $context = Context::getCurrent();
        if ($context->getRequest()->isAjaxRequest() && Option::get(self::MODULE_NAME,
                'RETURN_AJAX_' . SITE_ID,
                'N',
                SITE_ID) == 'Y'
        ) {
            return;
        }

        if (
            !$context->getRequest()->getQueryList()->isEmpty()
            && method_exists($context->getRequest()->getQueryList(), 'getValues')
        ) {
            $queryValues = $context->getRequest()->getQueryList()->getValues();
            $queryValues = self::requestStrToUpper($queryValues);

            $endScrypt = self::checkRequestExceptions($excludeParams, $queryValues);
            if($endScrypt){
                return;
            }
        }

        $server = $context->getServer();
        $server_array = $server->toArray();
        $url_parts = explode("?", $context->getRequest()->getRequestUri());
        $url_parts[0] = rawurlencode(rawurldecode($url_parts[0]));
        $url_parts[0] = str_replace('%2F', '/', $url_parts[0]);
        $str = Option::get("sotbit.seometa",
            'PAGENAV_' . SITE_ID,
            '',
            SITE_ID
        );

        if ($str != '') {
            $preg = str_replace('/', '\/', $str);
            $preg = '/' . str_replace('%N%', '\d', $preg) . '/';
            preg_match($preg, $url_parts[0], $matches);
            if ($matches) {
                $exploted_pagen = explode('%N%', $str);
                $n = str_replace($exploted_pagen[0], '', $matches[0]);
                $n = str_replace($exploted_pagen[1], '', $n);
                $_REQUEST['PAGEN_1'] = (int)$n;
                $url_parts[0] = str_replace($matches[0], '', $url_parts[0]);
            }

            if (isset($_REQUEST['PAGEN_1'])) {
                $n = $_REQUEST['PAGEN_1'];
                $pagen = str_replace('%N%', $n, $str);
                $url_parts[1] = '';
                unset($_GET['PAGEN_1']);
                foreach ($_GET as $i => $p) {
                    $r[] = $i . '=' . $p;
                }

                $r[] = $pagen;
                $url_parts[1] = implode('&', $r);
                $PAGEN_1 = $n;
            }
        }
        if (
            !($instance = SeometaUrlTable::getByNewUrl($url_parts[0], SITE_ID))
            && !($instance = SeometaUrlTable::getByNewUrl($context->getRequest()->getRequestUri(), SITE_ID))
        ) {
            $instance = SeometaUrlTable::getByRealUrl($url_parts[0], SITE_ID);
            if (!$instance) {
                $instance = SeometaUrlTable::getByRealUrl($context->getRequest()->getRequestUri(), SITE_ID);
            }

            if ($instance && SITE_ID == $instance['SITE_ID'] && CSeoMetaEvents::$i) {
                CSeoMetaEvents::$i = 0;
                if (isset($pagen)) {
                    $instance['NEW_URL'] = $instance['NEW_URL'] . $pagen;
                    $url_parts[1] = '';
                }

                LocalRedirect(
                    $instance['NEW_URL'] . ($url_parts[1] != '' ? "?" . $url_parts[1] : ''),
                    false,
                    '301 Moved Permanently'
                );
            }
        }

        if ($instance && ($instance['NEW_URL'] != $instance['REAL_URL']) && SITE_ID == $instance['SITE_ID']) {
            $url_parts_query = explode("&", $url_parts[1]);
            $urlPartsCHPU = explode("?", $instance['REAL_URL']);
            if ($urlPartsCHPU[1]) {
                $urlPartsCHPU = explode("&", $urlPartsCHPU[1]);
                if ($urlPartsCHPU) {
                    $url_parts_query = array_merge($urlPartsCHPU);
                }
            }

            foreach ($url_parts_query as $item) {
                $items = explode('=', $item);
                $_GET[$items[0]] = $items[1];
            }

            if (!isset($pagen)) {
                $_SERVER['REQUEST_URI'] = $instance['REAL_URL'];
                $server_array['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
                $server->set($server_array);
                $userAgent = $context->getServer()->getUserAgent();
                $context->initialize(
                    new Bitrix\Main\HttpRequest(
                        $server,
                        $_GET,
                        [],
                        [],
                        $_COOKIE
                    ),
                    $context->getResponse(),
                    $server
                );
                $APPLICATION->sDocPath2 = GetPagePath(false, true);
                $APPLICATION->sDirPath = GetDirPath($APPLICATION->sDocPath2);
                $protocol =  ($context->getRequest()->isHttps() ? 'https' : 'http') . '://';
                $url = $protocol .  $server->getServerName() . $instance['NEW_URL'];
                if(Option::get("sotbit.seometa",'INC_STATISTIC','N',SITE_ID) == 'Y') {
                    self::getStatus($url, $instance, $userAgent);
                }
            } else {
                $url_parts[0] .= $pagen;
                $_SERVER['REQUEST_URI'] = $instance['REAL_URL'];
                $server_array['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
                $server->set($server_array);
                $userAgent = $context->getServer()->getUserAgent();
                $context->initialize(
                    new Bitrix\Main\HttpRequest(
                        $server,
                        $_GET,
                        [],
                        [],
                        $_COOKIE
                    ),
                    $context->getResponse(),
                    $server
                );
                $APPLICATION->sDocPath2 = GetPagePath(false, true);
                $APPLICATION->sDirPath = GetDirPath($APPLICATION->sDocPath2);
                $protocol =  ($context->getRequest()->isHttps() ? 'https' : 'http') . '://';
                $url = $protocol .  $server->getServerName() . $instance['NEW_URL'] . $pagen;
                if(Option::get("sotbit.seometa",'INC_STATISTIC','N',SITE_ID) == 'Y') {
                    self::getStatus($url, $instance, $userAgent);
                }
                $APPLICATION->SetCurPage($url_parts[0]);
            }

            CSeoMetaEvents::$i = 0;
        }
    }

    /**
     * @param string $url
     * @param array $instance
     * @param string|null $userAgent
     */
    protected static function getStatus(string $url, array $instance, ?string $userAgent): void
    {
        if ($_REQUEST['SEOMETA_STATUS_CODE'] !== "Y") {
            $httpClient = new \Bitrix\Main\Web\HttpClient;
            $httpClient->setRedirect(false);
            $httpClient->post($url, ["SEOMETA_STATUS_CODE" => "Y"]);
            $status = $httpClient->getStatus();
        }
        if ($status) {
            self::checkStat($url, $instance['CONDITION_ID'], $userAgent, $instance['IN_SITEMAP'], $status);
        }
    }

    protected static function checkStat($url, $condId, $userAgent, $inSiteMap, $status)
    {
        $currentDate = new \Bitrix\Main\Type\DateTime();
        $stat = SeometaStatisticsTable::getList([
            'select' => ['ID', 'PAGE_STATUS', 'ROBOTS_INFO', 'LAST_DATE_CHECK'],
            'filter' => ['URL' => $url],
            'order' => ['ID']
        ])->fetch();

        self::$googleBot = (stripos($userAgent, 'googlebot') !== false
            || stripos($userAgent, 'adsbot-google') !== false
            || stripos($userAgent, 'mediapartners-google') !== false);
        self::$yandexBot = stripos($userAgent, 'yandex') !== false;

        if($stat){
            $lastDateCheck = strtotime($currentDate->toString()) - strtotime($stat['LAST_DATE_CHECK']);
            $lastDateCheckSettings = Option::get("sotbit.seometa",'PERIOD_STATISTIC','86400',SITE_ID);
            if(($stat['PAGE_STATUS'] != $status) || self::$googleBot || self::$yandexBot || $lastDateCheck > $lastDateCheckSettings){
                $arFields = [
                    'PAGE_STATUS' => $status,
                    'LAST_DATE_CHECK' => $currentDate,
                ];
                $arFields['ROBOTS_INFO'] = self::getBotsFields(self::$googleBot, self::$yandexBot, $currentDate, $stat['ROBOTS_INFO']);
                self::$idStat = $stat['ID'];
                SeometaStatisticsTable::update($stat['ID'], $arFields);
            }
        }else{
            $arFields = [
                'DATE_CREATE' => $currentDate,
                'LAST_DATE_CHECK' => $currentDate,
                'CONDITION_ID' => $condId,
                'URL' => $url,
                'IN_SITEMAP' => $inSiteMap,
                'PAGE_STATUS' => $status,
                'SITE_ID' => SITE_ID,
            ];

            $arFields['ROBOTS_INFO'] = self::getBotsFields(self::$googleBot, self::$yandexBot, $currentDate);
            $res = SeometaStatisticsTable::add($arFields);
            self::$idStat = $res->getId();
        }
    }

    /**
     * @param bool $googleBot
     * @param bool $yandexBot
     * @param \Bitrix\Main\Type\DateTime $currentDate
     * @return string
     */
    protected static function getBotsFields(bool $googleBot,  bool $yandexBot, \Bitrix\Main\Type\DateTime $currentDate, $robot = ''): string
    {
        if(!($robot = unserialize($robot))){
            $robot = [];
        }
        if ($googleBot) {
            $robot['GoogleBot']['CHECK'] = 'Y';
            $robot['GoogleBot']['TIME_CHECK'] = $currentDate->toString();
        }
        if ($yandexBot) {
            $robot['YandexBot']['CHECK'] = 'Y';
            $robot['YandexBot']['TIME_CHECK'] = $currentDate->toString();
        }
        return $robot ? serialize($robot) : 'N';
    }

    static function EpilogAfter()
    {
        if(($_REQUEST && ($_REQUEST['SEOMETA_STATUS_AGENT'] === 'Y' && $_REQUEST['SEOMETA_STATUS_CODE'] === 'Y')) || self::$googleBot || self::$yandexBot){
            global $APPLICATION, $sotbitSeoMetaTitle, $sotbitSeoMetaKeywords, $sotbitSeoMetaDescription;

            $currentTitle = Emoji::encode($APPLICATION->GetPageProperty("title"));
            $currentKeywords = Emoji::encode($APPLICATION->GetPageProperty("keywords"));
            $currentDescription = Emoji::encode($APPLICATION->GetPageProperty("description"));
            $currentRobots = Emoji::encode($APPLICATION->GetPageProperty("robots"));

            $seoResult['META_TITLE']['COINCIDENCE'] = $sotbitSeoMetaTitle == $currentTitle ? 'Y' : 'N';
            $seoResult['META_TITLE']['CONTENT'] = $currentTitle;
            $seoResult['META_KEYWORDS']['COINCIDENCE'] = $sotbitSeoMetaKeywords == $currentKeywords ? 'Y' : 'N';
            $seoResult['META_KEYWORDS']['CONTENT'] = $currentKeywords;
            $seoResult['META_DESCRIPTION']['COINCIDENCE'] = $sotbitSeoMetaDescription == $currentDescription ? 'Y' : 'N';
            $seoResult['META_DESCRIPTION']['CONTENT'] = $currentDescription;
            $NO_INDEX = $currentRobots == 'index, follow' ? 'Y' : 'N';

            $arFields = [
                'META_TITLE' => serialize($seoResult['META_TITLE']),
                'META_KEYWORDS' => serialize($seoResult['META_KEYWORDS']),
                'META_DESCRIPTION' => serialize($seoResult['META_DESCRIPTION']),
                'NO_INDEX' => $NO_INDEX,
            ];

            $_REQUEST['SEOMETA_STAT_ID'] = self::$idStat ?: $_REQUEST['SEOMETA_STAT_ID'];

            SeometaStatisticsTable::update($_REQUEST['SEOMETA_STAT_ID'], $arFields);
        }
    }

    public static function OnReindexHandler(
        $NS,
        $oCallback,
        $callback_method
    ) {
        self::clearTable();
        $writer = ReindexWriter::getInstance($oCallback, $callback_method);
        $link = Linker::getInstance();
        $rsData = ConditionTable::getList([
            'filter' => [
                'ACTIVE' => 'Y',
                'SEARCH' => 'Y'
            ]
        ]);
        while ($condition = $rsData->fetch()) {
            $link->Generate($writer,
                $condition['ID']);
        }

        $data = $writer->getData();

        return !empty($data);
    }

    /**
     * clear table b_search_content by module_id = sotbit.seometa
     * */
    private static function clearTable(
    ) {
        $DB = CDatabase::GetModuleConnection('search');
        $DB->Query("DELETE FROM b_search_content WHERE ITEM_ID LIKE 'seometa%'");
    }

    public static function OnAfterIndexAddHandler(
        $ID,
        $arFields
    ) {
        if ($arFields['MODULE_ID'] == 'sotbit.seometa') {
            $connection = Application::getConnection();
            $connection->query('UPDATE `b_search_content` SET `MODULE_ID` = "iblock" WHERE `MODULE_ID` = "sotbit.seometa"');
        }
    }

    public static function ChangeContent(
        &$content
    ) {
        global $APPLICATION;
        $seoData = new OGraphTWCard();
        if (
            $_POST['AJAX'] != 'Y'
            && mb_strpos($APPLICATION->GetCurPage(false), '/bitrix') === false
            && $data = $seoData->getData()
        ) {
            foreach ($data as $name => $value) {
                $count = 0;
                $searchPattern = "/<meta\s+?property=[\"|']" . $name . "[\"|']\scontent=[\"|'][\s\S]*?[\"|']\s*\/?>/";
                $content = preg_replace(
                    $searchPattern,
                    $seoData->createMeta($name, $value),
                    $content,
                    -1,
                    $count
                );
                if ($count === 0) {
                    $content = preg_replace(
                        '/<\/head>/',
                        $seoData->createMeta($name, $value) . '</head>',
                        $content
                    );
                }
            }
        }
    }
}
