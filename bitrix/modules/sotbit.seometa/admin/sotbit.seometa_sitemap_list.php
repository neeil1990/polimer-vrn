<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Sotbit\Seometa\Helper\SitemapRuntime;
use Sotbit\Seometa\Orm\SitemapTable;

Loc::loadMessages(__FILE__);

global $APPLICATION;

$moduleId = 'sotbit.seometa';
$errorMgs = '';
$POST_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if (!Loader::includeModule($moduleId) || $POST_RIGHT == "D") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

$sTableID = "b_sotbit_seometa_sitemaps";
$oSort = new CAdminSorting($sTableID, "ID", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

// Sites list
$arSites = [];
$rsSites = CSite::GetList(
    $by1 = "NAME",
    $order1 = "desc",
    ["ACTIVE" => "Y"]
);
while ($arSite = $rsSites->Fetch()) {
    $arSites[$arSite['LID']] = $arSite;
}

if(isset($_REQUEST['ID']) && !is_array($_REQUEST['ID'])) {
    $sitePaths = (new CSeoMetaSitemapLight)->pathMainSitemap($_REQUEST['ID']);
}

if($sitePaths['TYPE'] == 'ERROR') {
    $errorMgs = $sitePaths['MSG'];
}

if ($arID = $lAdmin->GroupAction() && empty($errorMgs)) {
    if ($_REQUEST['action'] == "delete") {
        if(!is_array($_REQUEST['ID'])) {
            $_REQUEST['ID'] = [$_REQUEST['ID']];
        }

        foreach ($_REQUEST['ID'] as $ID) {
           SitemapTable::delete($ID);
        }
    }
}

$map = SitemapTable::getMap();
$rsData = SitemapTable::getList([
    'select' => array_keys($map),
    'filter' => [],
    'order' => [$by => $order],
]);

$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();
$lAdmin->AddHeaders([
    [
        "id" => "ID",
        "content" => Loc::getMessage("SEO_META_SITEMAP_ID"),
        "sort" => "ID",
        "align" => "right",
        "default" => true
    ],
    [
        "id" => "TIMESTAMP_CHANGE",
        "content" => Loc::getMessage("SEO_META_SITEMAP_TIMESTAMP_CHANGE"),
        "sort" => "TIMESTAMP_CHANGE",
        "default" => true
    ],
    [
        "id" => "NAME",
        "content" => Loc::getMessage("SEO_META_SITEMAP_NAME"),
        "sort" => "NAME",
        "default" => true
    ],
    [
        "id" => "SITE_ID",
        "content" => Loc::getMessage("SEO_META_SITEMAP_SITE_ID"),
        "sort" => "SITE_ID",
        "default" => true
    ],
    [
        "id" => "DATE_RUN",
        "content" => Loc::getMessage("SEO_META_SITEMAP_DATE_RUN"),
        "sort" => "DATE_RUN",
        "default" => true
    ],
    [
        "id" => "RUN",
        "content" => Loc::getMessage("SEO_META_SITEMAP_RUN"),
        "sort" => "ID",
        "default" => true
    ]
]);

$lAdmin->NavText($rsData->GetNavPrint(Loc::getMessage("SEO_META_NAV")));
while ($sitemap = $rsData->NavNext()) {
    $id = intval($sitemap['ID']);
    $settings = unserialize($sitemap['SETTINGS']);
    $genAll = (!isset($settings['GENERATE_ALL_CONDITIONS'])) ? 'N' : (($settings['GENERATE_ALL_CONDITIONS'] === 'Y') ? 'Y' : 'N');
    unset($sitemap['SETTINGS'], $settings);
    $typeGenerating[$id] = $genAll;
    $row = &$lAdmin->AddRow($sitemap["ID"], $sitemap);
    $row->AddViewField("ID", '<a href="sotbit.seometa_sitemap_edit.php?ID=' . $id . '&amp;lang=' . LANGUAGE_ID . '" title="' . Loc::getMessage("SEO_META_SITEMAP_EDIT_TITLE") . '">' . $id. '</a>');
    $row->AddViewField('TIMESTAMP_CHANGE', $sitemap['TIMESTAMP_CHANGE']);
    $row->AddViewField('DATE_RUN', '<div id="date_time_run_'.$id.'">'.$sitemap['DATE_RUN'] ?: Loc::getMessage('SEO_META_SITEMAP_DATE_RUN_NEVER')).'</div>';
    $row->AddViewField('SITE_ID', '<a href="site_edit.php?lang=' . LANGUAGE_ID . '&amp;LID=' . $sitemap['SITE_ID'] . '">[' . $sitemap['SITE_ID'] . '] ' . $arSites[$sitemap['SITE_ID']]['NAME'] . '</a>');
    $row->AddViewField("NAME", '<a href="sotbit.seometa_sitemap_edit.php?ID=' . $id . '&amp;lang=' . LANGUAGE_ID . '" title="' . Loc::getMessage("SEO_META_SITEMAP_EDIT_TITLE") . '">' . $sitemap['NAME'] . '</a>');
    $row->AddViewField("RUN", '<input type="button" class="adm-btn-save" value="' . Loc::getMessage('SEO_META_SITEMAP_RUN') . '" onclick="generateSitemap'. ($typeGenerating[$id] != "Y" ? "Light" : "") .'(' . $id . ')" name="save" id="sitemap_run_button_' . $id . '" />');

    $row->AddActions([
        [
            "ICON" => "edit",
            "TEXT" => Loc::getMessage("SEO_META_SITEMAP_EDIT"),
            "ACTION" => $lAdmin->ActionRedirect("sotbit.seometa_sitemap_edit.php?ID=" . $id . "&lang=" . LANGUAGE_ID),
            "DEFAULT" => true
        ],
        [
            "ICON" => "move",
            "TEXT" => Loc::getMessage("SEO_META_SITEMAP_RUN"),
            "ACTION" => 'generateSitemap'. ( $typeGenerating[$id] != "Y" ? "Light" : "" ) .'(' . $id . ');'
        ],
        [
            "ICON" => "delete",
            "TEXT" => Loc::getMessage("SEO_META_SITEMAP_DELETE"),
            "ACTION" => "if(confirm('" . \CUtil::JSEscape(Loc::getMessage('SEO_META_SITEMAP_DELETE_CONFIRM')) . "')) " . $lAdmin->ActionDoGroup($id, "delete")
        ]
    ]);
}

$arDDMenu[] = [
    "TEXT" => Loc::getMessage("SEO_META_SEO_ADD_SITEMAP_CHOOSE_SITE"),
    "ACTION" => false
];

foreach ($arSites as $arRes) {
    $arDDMenu[] = [
        "TEXT" => "[" . $arRes["LID"] . "] " . $arRes["NAME"],
        "LINK" => "sotbit.seometa_sitemap_edit.php?lang=" . LANGUAGE_ID . "&site_id=" . $arRes['LID']
    ];
}

$aContext[] = [
    "TEXT" => Loc::getMessage("SEO_META_SEO_ADD_SITEMAP"),
    "TITLE" => Loc::getMessage("SEO_META_SEO_ADD_SITEMAP_TITLE"),
    "ICON" => "btn_new",
    "MENU" => $arDDMenu
];

$lAdmin->AddAdminContextMenu($aContext);
$lAdmin->AddGroupActionTable(["delete" => Loc::getMessage("MAIN_ADMIN_LIST_DELETE")]);
$lAdmin->CheckListMode();
$APPLICATION->SetTitle(Loc::getMessage("SEO_META_SEO_SITEMAP_TITLE"));
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
if (CCSeoMeta::ReturnDemo() == 2) {
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?=Loc::getMessage("SEO_META_DEMO")?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
}

if (CCSeoMeta::ReturnDemo() == 3 || CCSeoMeta::ReturnDemo() == 0) {
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?=Loc::getMessage("SEO_META_DEMO_END")?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
    return '';
}

$lAdmin->DisplayList();
?>
<script>
    function generateSitemap(ID) {
        const node = BX('sitemap_run');
        if(node.style.display === 'block'){
            const nodeProgress = BX('sitemap_progress');
            const nodeProgressStart = BX('sitemap_progress_start');
            nodeProgress.innerHTML = nodeProgressStart.innerHTML;
        }else{
            node.style.display = 'block';
        }
        const windowPos = BX.GetWindowSize();
        const pos = BX.pos(node);

        if (pos.top > windowPos.scrollTop + windowPos.innerHeight) {
            window.scrollTo(windowPos.scrollLeft, pos.top + 150 - windowPos.innerHeight);
        }
        BX.getSectionList(ID, function (response) {
            BX.runSitemap(ID, 0, '', '', 0, 0, 0, response);
        })
    }

    function generateSitemapLight (ID) {
        const nodeLight = document.querySelector('#sitemap_run');
        if(nodeLight.style.display === 'block'){
            const nodeProgress = document.querySelector('#sitemap_progress');
            const nodeProgressStart = document.querySelector('#sitemap_progress_start');
            nodeProgress.innerHTML = nodeProgressStart.innerHTML;
        }else{
            nodeLight.style.display = 'block';
        }
        BX.ajax.post('/bitrix/admin/sotbit.seometa_sitemap_light_run.php', {
            lang: '<?=LANGUAGE_ID?>',
            action: 'write_sitemap',
            ID: ID,
            offset: 0,
            sitemap_index: 1,
            sessid: BX.bitrix_sessid()
        }, function (data) {
            if(data.search(/errortext/) !== -1) {
                document.querySelector('#show-error-seometa .adm-info-message').innerHTML = data;
                document.querySelector('#show-error-seometa').style.display = 'block';
            } else {
                showStatus(data);
            }
        });
    }

    function showStatus (data) {
        let resData =JSON.parse(data);
        var progressWrap = document.querySelector('#sitemap_progress');
        if(resData['progressbar'] !== undefined && resData['progressbar'] !== ''){
            progressWrap.innerHTML = resData['progressbar'];
        }
        BX.ajax.post('/bitrix/admin/sotbit.seometa_sitemap_light_run.php', {
            lang: '<?=LANGUAGE_ID?>',
            action: 'sitemap_in_progress',
            data: data,
            sessid: BX.bitrix_sessid()
        }, function (data) {
            let result = JSON.parse(data);
            if(result['progressbar'] !== undefined && result['progressbar'] !== '') {
                if(result['DATE_RUN'] !== undefined){
                    document.querySelector('#date_time_run_' + result['ID']).innerHTML = result['DATE_RUN'];
                }
                progressWrap.innerHTML = result['progressbar'];
            }
            if((result['STATUS'] !== undefined && result['STATUS'] == 'finish') || result === undefined) {
                if(result['TYPE'] == 'ERROR') {
                    document.querySelector('#sitemap_run').style.display = 'none';
                    document.querySelector('#show-error-seometa .adm-info-message').innerHTML = result['MSG'];
                    document.querySelector('#show-error-seometa').style.display = 'block';
                }
            } else {
                setTimeout(function () {
                    showStatus(data)
                }, 1000);
            }
        });
    }

    function IsJsonString(str){
        try {
            JSON.parse(str);
        } catch (e){
            return false;
        }
        return true;
    }

    BX.getSectionList = function (ID, callback) {
        let result = [];
        BX.ajax.post('/bitrix/admin/sotbit.seometa_sitemap_run.php', {
            lang: '<?=LANGUAGE_ID?>',
            action: 'get_section_list',
            ID: ID,
            value: 0,
            pid: '',
            NS: '',
            sessid: BX.bitrix_sessid()
        }, function (data) {
            result = JSON.parse(data);
            if(result['TYPE'] == 'ERROR' && result['MSG'] != '') {
                document.querySelector('#sitemap_run').style.display = 'none';
                document.querySelector('#show-error-seometa .adm-info-message').innerHTML = result['MSG'];
                document.querySelector('#show-error-seometa').style.display = 'block';
                return;
            }
            callback(result);
        });
        return result;
    };

    BX.runSitemap = function (ID, value, pid, NS, iteration = 0, currCond = 0, currSection = 0, params = []) {
        let countIterations = 0;

        //change params['conditions'] to params['sections'] if 500 errors
        for (let section in params['conditions']) {
            //uncomment if 500 errors
            //countIterations += params['sections'][section].length;

            //comment if 500 errors
            countIterations++;
        }
        BX.adminPanel.showWait(BX('sitemap_run_button_' + ID));
        if (countIterations > 0){
            if (iteration >= countIterations) {
                BX.adminPanel.closeWait(BX('sitemap_run_button_' + ID));
                return;
            }
        }

        BX.ajax.post('/bitrix/admin/sotbit.seometa_sitemap_run.php', {
            lang: '<?=LANGUAGE_ID?>',
            action: 'sitemap_run',
            currentSection: currSection,
            currentCondition: currCond,
            iteration: iteration,
            countIterations: countIterations,
            params: params,
            ID: ID,
            value: value,
            pid: pid,
            NS: NS,
            sessid: BX.bitrix_sessid()
        }, function (data) {
            iteration++;

            //comment if 500 errors
            currCond++;

            //uncomment if 500 errors
            /*let condId = params['conditions'][currCond];
            if(params['sections'][condId][currSection + 1] === undefined){
                currSection = 0;
                currCond++;
            }else{
                currSection++;
            }*/
            if(IsJsonString(data) !== false){
                result = JSON.parse(data);
                BX('sitemap_progress').innerHTML = '<div class="adm-info-message-wrap adm-info-message-green"><div class="adm-info-message"><div class="adm-info-message-title"><?= Loc::getMessage('SEO_META_SITEMAP_RUN_TITLE') ?></div><?= Loc::getMessage('SEO_META_SITEMAP_RUN_DONE') ?><div class="adm-info-message-icon"></div></div></div>';
                BX('date_time_run_'+result['ID']).innerHTML = result['DATE'];

            }else{
                BX('sitemap_progress').innerHTML = data;
            }


            if (countIterations > 0){
                BX.runSitemap(ID, 0, '', '', iteration, currCond, currSection, params);
            } else {
                BX.adminPanel.closeWait(BX('sitemap_run_button_' + ID));
            }
        });
    };
</script>

<div id="sitemap_run" style="display: none;">
    <div id="sitemap_progress">
        <?=SitemapRuntime::showProgress(Loc::getMessage('SEO_META_SITEMAP_RUN_INIT'), Loc::getMessage('SEO_META_SITEMAP_RUN_TITLE'), 0)?>
    </div>
    <div id="sitemap_progress_start" style="display: none">
        <?=SitemapRuntime::showProgress(Loc::getMessage('SEO_META_SITEMAP_RUN_INIT'), Loc::getMessage('SEO_META_SITEMAP_RUN_TITLE'), 0)?>
    </div>
</div>
<div id="show-error-seometa" style="display: none">
    <div class="adm-info-message-wrap adm-info-message-red" style="position: relative; top: -15px;">
        <div class="adm-info-message">
        </div>
    </div>
</div>
<?
if(!empty($errorMgs)) {
    ShowError($errorMgs);
}

if (isset($_REQUEST['run']) && check_bitrix_sessid()) {
    $ID = intval($_REQUEST['run']);
    if ($ID > 0) {
        ?>
        <script>
            BX.ready(BX.defer(function () {
                generateSitemap<?=($typeGenerating[$ID] != "Y" ? "Light" : "")?>(<?=$ID?>);
            }));
        </script>
        <?
    }
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>
