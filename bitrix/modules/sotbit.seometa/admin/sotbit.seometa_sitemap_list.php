<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Seo\SitemapRuntime;
use Sotbit\Seometa\SitemapTable;

$id_module = 'sotbit.seometa';
Loader::includeModule($id_module);

Loc::loadMessages(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("sotbit.seometa");
if($POST_RIGHT == "D")
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

//$CCSeoMeta = new CCSeoMeta();
//if(!$CCSeoMeta->getDemo())
//    return false;

$sTableID = "b_sotbit_seometa_sitemaps";
$oSort = new CAdminSorting($sTableID, "ID", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

// Sites list
$arSites = array();
$rsSites = CSite::GetList($by1 = "NAME", $order1 = "desc", array("ACTIVE" => "Y"));
while($arSite = $rsSites->Fetch())
{
    $arSites[$arSite['LID']] = $arSite;
}

if($arID = $lAdmin->GroupAction())
{
    if($_REQUEST['action'] == "delete")
    {
        foreach($_REQUEST['ID'] as $ID)
        {
            $dbSitemap = SitemapTable::getById($ID);
            $arSitemap = $dbSitemap->fetch();
            $arSitemap['SETTINGS'] = unserialize($arSitemap['SETTINGS']);
            $arSites = array();
            $rsSites = CSite::GetById($arSitemap['SITE_ID']);
            $arSite = $rsSites->Fetch();
            $arSite['ABS_DOC_ROOT'] . $arSite['DIR'];
            if(file_exists($arSite['ABS_DOC_ROOT'] . $arSite['DIR'] . 'sitemap_seometa_' . $ID . '.xml'))
                unlink($arSite['ABS_DOC_ROOT'] . $arSite['DIR'] . 'sitemap_seometa_' . $ID . '.xml');
            if(file_exists($arSite['ABS_DOC_ROOT'] . $arSite['DIR'] . 'sitemap.xml'))
            {
                $SiteUrl = "";
                if(isset($arSitemap['SETTINGS']['PROTO']) && $arSitemap['SETTINGS']['PROTO'] == 1)
                {
                    $SiteUrl .= 'https://';
                }
                elseif(isset($arSitemap['SETTINGS']['PROTO']) && $arSitemap['SETTINGS']['PROTO'] == 0)
                {
                    $SiteUrl .= 'http://';
                }
                if(isset($arSitemap['SETTINGS']['DOMAIN']) && !empty($arSitemap['SETTINGS']['DOMAIN']))
                    $SiteUrl .= $arSitemap['SETTINGS']['DOMAIN'];


                $xml = simplexml_load_file($arSite['ABS_DOC_ROOT'] . $arSite['DIR'] . 'sitemap.xml');
                for($i = 0; $i < count($xml->sitemap); $i++)
                {
                    if(isset($xml->sitemap[$i]->loc) && $xml->sitemap[$i]->loc == $SiteUrl . '/sitemap_seometa_' . $ID . '.xml')
                    {
                        unset($xml->sitemap[$i]);
                    }
                }
                file_put_contents($arSite['ABS_DOC_ROOT'] . $arSite['DIR'] . 'sitemap.xml', $xml->asXML());
            }
            SitemapTable::delete($ID);
        }
    }
}

$map = SitemapTable::getMap();
unset($map['SETTINGS']);

$rsData = SitemapTable::getList(array(
    'select' => array_keys($map),
    'filter' => array(),
    'order' => array($by => $order),
));

$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();

$lAdmin->AddHeaders(array(
    array(
        "id" => "ID",
        "content" => Loc::getMessage("SEO_META_SITEMAP_ID"),
        "sort" => "ID",
        "align" => "right",
        "default" => true
    ),
    array(
        "id" => "TIMESTAMP_CHANGE",
        "content" => Loc::getMessage("SEO_META_SITEMAP_TIMESTAMP_CHANGE"),
        "sort" => "TIMESTAMP_CHANGE",
        "default" => true
    ),
    array(
        "id" => "NAME",
        "content" => Loc::getMessage("SEO_META_SITEMAP_NAME"),
        "sort" => "NAME",
        "default" => true
    ),
    array(
        "id" => "SITE_ID",
        "content" => Loc::getMessage("SEO_META_SITEMAP_SITE_ID"),
        "sort" => "SITE_ID",
        "default" => true
    ),
    array(
        "id" => "DATE_RUN",
        "content" => Loc::getMessage("SEO_META_SITEMAP_DATE_RUN"),
        "sort" => "DATE_RUN",
        "default" => true
    ),
    array(
        "id" => "RUN",
        "content" => Loc::getMessage("SEO_META_SITEMAP_RUN"),
        "sort" => "ID",
        "default" => true
    )
));

$lAdmin->NavText($rsData->GetNavPrint(Loc::getMessage("SEO_META_NAV")));
while($sitemap = $rsData->NavNext())
{
    $id = intval($sitemap['ID']);
    $row = &$lAdmin->AddRow($sitemap["ID"], $sitemap);
    $row->AddViewField("ID", $sitemap['ID']);
    $row->AddViewField('TIMESTAMP_CHANGE', $sitemap['TIMESTAMP_CHANGE']);
    $row->AddViewField('DATE_RUN', $sitemap['DATE_RUN'] ? $sitemap['DATE_RUN'] : Loc::getMessage('SEO_META_SITEMAP_DATE_RUN_NEVER'));
    $row->AddViewField('SITE_ID', '<a href="site_edit.php?lang=' . LANGUAGE_ID . '&amp;LID=' . $sitemap['SITE_ID'] . '">[' . $sitemap['SITE_ID'] . '] ' . $arSites[$sitemap['SITE_ID']]['NAME'] . '</a>');
    $row->AddViewField("NAME", '<a href="sotbit.seometa_sitemap_edit.php?ID=' . $sitemap["ID"] . '&amp;lang=' . LANGUAGE_ID . '" title="' . Loc::getMessage("SEO_META_SITEMAP_EDIT_TITLE") . '">' . $sitemap['NAME'] . '</a>');
    $row->AddViewField("RUN", '<input type="button" class="adm-btn-save" value="' . Loc::getMessage('SEO_META_SITEMAP_RUN') . '" onclick="generateSitemap(' . $sitemap['ID'] . ')" name="save" id="sitemap_run_button_' . $sitemap['ID'] . '" />');

    $row->AddActions(array(
        array(
            "ICON" => "edit",
            "TEXT" => Loc::getMessage("SEO_META_SITEMAP_EDIT"),
            "ACTION" => $lAdmin->ActionRedirect("sotbit.seometa_sitemap_edit.php?ID=" . $sitemap["ID"] . "&lang=" . LANGUAGE_ID),
            "DEFAULT" => true
        ),
        array(
            "ICON" => "move",
            "TEXT" => Loc::getMessage("SEO_META_SITEMAP_RUN"),
            "ACTION" => 'generateSitemap(' . $sitemap['ID'] . ');'
        ),
        array(
            "ICON" => "delete",
            "TEXT" => Loc::getMessage("SEO_META_SITEMAP_DELETE"),
            "ACTION" => "if(confirm('" . \CUtil::JSEscape(Loc::getMessage('SEO_META_SITEMAP_DELETE_CONFIRM')) . "')) " . $lAdmin->ActionDoGroup($id, "delete")
        )
    ));
}
$arDDMenu = array();

$arDDMenu[] = array(
    "TEXT" => Loc::getMessage("SEO_META_SEO_ADD_SITEMAP_CHOOSE_SITE"),
    "ACTION" => false
);

foreach($arSites as $arRes)
{
    $arDDMenu[] = array(
        "TEXT" => "[" . $arRes["LID"] . "] " . $arRes["NAME"],
        "LINK" => "sotbit.seometa_sitemap_edit.php?lang=" . LANGUAGE_ID . "&site_id=" . $arRes['LID']
    );
}

$aContext = array();
$aContext[] = array(
    "TEXT" => Loc::getMessage("SEO_META_SEO_ADD_SITEMAP"),
    "TITLE" => Loc::getMessage("SEO_META_SEO_ADD_SITEMAP_TITLE"),
    "ICON" => "btn_new",
    "MENU" => $arDDMenu
);

$lAdmin->AddAdminContextMenu($aContext);
$lAdmin->AddGroupActionTable(array("delete" => Loc::getMessage("MAIN_ADMIN_LIST_DELETE")));

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage("SEO_META_SEO_SITEMAP_TITLE"));
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

if( CCSeoMeta::ReturnDemo() == 2){
    ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <div class="adm-info-message">
            <div class="adm-info-message-title"><?=Loc::getMessage("SEO_META_DEMO")?></div>
            <div class="adm-info-message-icon"></div>
        </div>
    </div>
    <?
}
if( CCSeoMeta::ReturnDemo() == 3 || CCSeoMeta::ReturnDemo() == 0)
{
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

//if($CCSeoMeta->ReturnDemo() == 2) CAdminMessage::ShowMessage(array("MESSAGE" => Loc::getMessage("SEO_META_DEMO"), 'HTML' => true));
//if($CCSeoMeta->ReturnDemo() == 3) CAdminMessage::ShowMessage(array("MESSAGE" => Loc::getMessage("SEO_META_DEMO_END"), 'HTML' => true));

$lAdmin->DisplayList();
?>

<script>
    function generateSitemap(ID) {
        var node = BX('sitemap_run');
        node.style.display = 'block';
        var windowPos = BX.GetWindowSize();
        var pos = BX.pos(node);

        if (pos.top > windowPos.scrollTop + windowPos.innerHeight) {
            window.scrollTo(windowPos.scrollLeft, pos.top + 150 - windowPos.innerHeight);
        }

        BX.runSitemap(ID, 0, '', '');
    }

    BX.runSitemap = function (ID, value, pid, NS) {
        BX.adminPanel.showWait(BX('sitemap_run_button_' + ID));
        BX.ajax.post('/bitrix/admin/sotbit.seometa_sitemap_run.php', {
            lang: '<?=LANGUAGE_ID?>',
            action: 'sitemap_run',
            ID: ID,
            value: value,
            pid: pid,
            NS: NS,
            sessid: BX.bitrix_sessid()
        }, function (data) {
            BX.adminPanel.closeWait(BX('sitemap_run_button_' + ID));
            BX('sitemap_progress').innerHTML = data;
        });
    };
    BX.finishSitemap = function () {
        window.<?=$sTableID?>.GetAdminList('/bitrix/admin/sotbit.seometa_sitemap_list.php?lang=<?=LANGUAGE_ID?>');
    };
</script>

<div id="sitemap_run" style="display: none;">
    <div id="sitemap_progress">
        <?=SitemapRuntime::showProgress(Loc::getMessage('SEO_META_SITEMAP_RUN_INIT'), Loc::getMessage('SEO_META_SITEMAP_RUN_TITLE'), 0)?>
    </div>
</div>

<?
if(isset($_REQUEST['run']) && check_bitrix_sessid())
{
    $ID = intval($_REQUEST['run']);
    if($ID > 0)
    {
        ?>
        <script>
            BX.ready(BX.defer(function () {
                generateSitemap(<?=$ID?>);
            }));
        </script>
        <?
    }
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>