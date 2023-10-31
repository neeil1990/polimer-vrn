<?
use Sotbit\Seometa\Orm\SeometaUrlTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

CJSCore::Init(array("jquery"));
$error = '';
$id_module='sotbit.seometa';
$arResult = [];

Loc::loadMessages(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight($id_module);
if(!Loader::includeModule($id_module) || $POST_RIGHT=="D")
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

//$CCSeoMeta= new CCSeoMeta();
//if(!$CCSeoMeta->getDemo())
//    return false;

$sTableID = "b_sotbit_seometa_chpu";
$order = $_REQUEST['order'] ?: 'asc';
$by = $_REQUEST['by'] ?: 'ID';
$oSort = new CAdminSorting($sTableID, $by, $order);
$lAdmin = new CAdminList($sTableID, $oSort);

function CheckFilter()
{
    global $FilterArr, $lAdmin;
    foreach ($FilterArr as $f) global $$f;
    return count($lAdmin->arFilterErrors)==0;
}


$FilterArr = Array(
    "find",
    "find_id",
    "find_name",
    "find_status",
);

$parentID = 0;
if(isset($_REQUEST["parent"]) && $_REQUEST["parent"])
{
    $parentID = $_REQUEST["parent"];
}

if(isset($parentID) && $parentID > 0)
    $ParentUrl = "&section=".$parentID;
else
    $ParentUrl = '';

$lAdmin->InitFilter($FilterArr);
$arFilter = array();

if (CheckFilter())
{
    if($find != '' && $find_type == 'id')
        $arFilter['ID'] = $find;
    elseif($find_id != '')
        $arFilter['ID'] = $find_id;
    $arFilter['NAME'] = $find_name;
    $arFilter['STATUS'] = $find_status;

    if(empty($arFilter['ID'])) unset($arFilter['ID']);
    if(empty($arFilter['NAME'])) unset($arFilter['NAME']);
    if(empty($arFilter['STATUS'])) unset($arFilter['STATUS']);
}

$filter = $arFilter;

$rsData = SeometaUrlTable::getList(array(
    'select' => array('ID', 'NAME', 'ACTIVE', 'SITE_ID', 'REAL_URL', 'NEW_URL', 'STATUS', 'DESCRIPTION', 'KEYWORDS', 'TITLE', 'DATE_SCAN'),
    'filter' => $arFilter,
    'order' => array($by => $order),
));

    while($arRes = $rsData->Fetch())
    {
        $arRes["T"] = "P";
        $arResult[] = $arRes;
    }

$request = Application::getInstance()->getContext()->getRequest();
$protocol = ($request->isHttps() ? 'https' : 'http') . '://';
$rsSites = CSite::GetList($by = "sort",
    $order = "desc",
    []
);

while ($arSite = $rsSites->Fetch()) {
    $SitesAll[$arSite['LID']] = $arSite;
}

$rs = new CDBResult;
$rs->InitFromArray($arResult);
$rsData = new CAdminResult($rs, $sTableID);
$rsData->NavStart();

$lAdmin->NavText($rsData->GetNavPrint(Loc::getMessage("SEO_META_NAV")));


$lAdmin->AddHeaders(array(
    array(
        "id" => "ID",
        "content" => Loc::getMessage("SEO_META_TABLE_ID"),
        "sort" => "ID",
        "align" => "right",
        "default" => true,
    ),
    array(
        "id" => "NAME",
        "content" => Loc::getMessage("SEO_META_TABLE_NAME"),
        "sort" => "NAME",
        "default" => true,
    ),
    array(
        "id" => "ACTIVE",
        "content" => Loc::getMessage("SEO_META_TABLE_ACTIVE"),
        "sort" => "ACTIVE",
        "default" => true,
    ),
    array(
        "id" => "SITE_ID",
        "content" => Loc::getMessage("SEO_META_TABLE_SITE_ID"),
        "sort" => "SITE_ID",
        "default" => true,
    ),
    array(
        "id" => "REAL_URL",
        "content" => Loc::getMessage("SEO_META_TABLE_REAL_URL"),
        "sort" => "REAL_URL",
        "default" => true,
    ),
    array(
        "id" => "NEW_URL",
        "content" => Loc::getMessage("SEO_META_TABLE_NEW_URL"),
        "sort" => "NEW_URL",
        "default" => true,
    ),
    array(
        "id" => "STATUS",
        "content" => Loc::getMessage("SEO_META_TABLE_STATUS"),
        "sort" => "STATUS",
        "default" => true,
    ),
    array(
        "id" => "DESCRIPTION",
        "content" => Loc::getMessage("SEO_META_TABLE_DESCRIPTION"),
        "sort" => "DESCRIPTION",
        "default" => true,
    ),
    array(
        "id" => "KEYWORDS",
        "content" => Loc::getMessage("SEO_META_TABLE_KEYWORDS"),
        "sort" => "KEYWORDS",
        "default" => true,
    ),
    array(
        "id" => "TITLE",
        "content" => Loc::getMessage("SEO_META_TABLE_TITLE"),
        "sort" => "TITLE",
        "default" => true,
    ),
    array(
        "id" => "DATE_SCAN",
        "content" => Loc::getMessage("SEO_META_TABLE_DATE_SCAN"),
        "sort" => "DATE_SCAN",
        "default" => true,
    ),
));


if(!Loader::includeModule('iblock'))
    CModule::IncludeModule('iblock');

while($arRes = $rsData->NavNext(true, "f_"))
{
    $row =& $lAdmin->AddRow($f_T.$f_ID, $arRes);
    $siteUrl = $SitesAll[$arRes['SITE_ID']]['SERVER_NAME'];
    $row->AddViewField("ACTIVE", $f_ACTIVE == 'Y' ? Loc::getMessage("SEO_META_YES") : Loc::getMessage("SEO_META_NO"));
    $row->AddViewField("REAL_URL", $f_ACTIVE == 'N' ? '<a href='.$protocol.$siteUrl.$f_REAL_URL.' target="_blank">'.$f_REAL_URL.'</a>' : $f_REAL_URL);
    $row->AddViewField("NEW_URL", $f_ACTIVE == 'Y' ? '<a href='.$protocol.$siteUrl.$f_NEW_URL.' target="_blank">'.$f_NEW_URL.'</a>' : $f_NEW_URL);
    $row->AddViewField("STATUS", $f_STATUS == '200' ? "<span style='color: green'>".$f_STATUS."</span>" : ($f_STATUS == '404' ? "<span style='color: red'>".$f_STATUS."</span>" : $f_STATUS));

    $arActions = Array();

    $arActions[] = array(
        "ICON" => "edit",
        "DEFAULT" => true,
        "TEXT" => Loc::getMessage("SEO_META_VIEW"),
        "ACTION" => $lAdmin->ActionRedirect("sotbit.seometa_webmaster_edit.php?ID=".$f_ID)
    );

    $arActions[] = array("SEPARATOR" => true);
    if(is_set($arActions[count($arActions)-1], "SEPARATOR"))
        unset($arActions[count($arActions)-1]);
    $row->AddActions($arActions);
}


$lAdmin->AddFooter(
    array(
        array("title" => Loc::getMessage("SEO_META_LIST_SELECTED"), "value" => $rsData->SelectedRowsCount()),
        array("counter" => true, "title" => Loc::getMessage("SEO_META_LIST_CHECKED"), "value" => "0"),
    )
);


$lAdmin->AddAdminContextMenu();
$lAdmin->CheckListMode();



if ($_REQUEST['action'] == "scan")
{
    $APPLICATION->RestartBuffer();
    header('Content-Type: application/javascript; charset=' . LANG_CHARSET);

    $done = false;
    $percent = 0;
    $lastID = isset($_REQUEST['lastID']) ? intval($_REQUEST['lastID']) : 0;

    CSeoMetaScaner::Scan($lastID, $percent);

    if($percent == 100)
    {
        $done = true;
		COption::SetOptionString($id_module, 'last_scan_time', time());
	}

	die(CUtil::PhpToJsObject(array(
		'last' => $lastID,
		'percent' => round($percent),
		'all_done' => $done ? 'Y' : 'N'
	)));
}



$APPLICATION->SetTitle(Loc::getMessage("SEO_META_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

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
?>



<script>
	BX.message(<?=CUtil::PhpToJSObject(IncludeModuleLangFile(__FILE__, false, true))?>);
	var JSeometaScaner = new JSeometaScaner();
</script>
<div class="adm-detail-content-item-block" style="width: 630px; margin-bottom: 20px;">
    <div id="resultcode_container"></div>
    <div id="start_container">
        <div id="first_start" class="adm-security-text-block" style="padding: 10px 0 30px;">
            <?if (($time = COption::GetOptionString('sotbit.seometa', 'last_scan_time')) > 0):?>
                <?=Loc::getMessage("SEO_META_LAST_SCAN_TIME_DONE")?><?= FormatDate('d.m.Y H:i:s', COption::GetOptionString('sotbit.seometa', 'last_scan_time'))?>
            <?else:?>
                <?=Loc::getMessage("SEO_META_LAST_SCAN_TIME_NONE")?><?endif;?>
        </div>
        <span id="start_button" class="adm-btn adm-btn-green" onclick="JSeometaScaner.startStop()"><?=Loc::getMessage("SEO_META_START_SCAN_BUTTON")?></span>
    </div>
    <div id="status_bar" style="display: none;">
        <div id="progress_bar" style="width: 100%;" class="adm-progress-bar-outer">
            <div id="progress_bar_inner" style="width: 0px;" class="adm-progress-bar-inner"></div>
            <div id="progress_text" style="width: 100%;" class="adm-progress-bar-inner-text">0%</div>
        </div>
        <div id="current_test"></div>
        <span id="stop_button" class="adm-btn stop-button" onclick="JSeometaScaner.startStop()" style="margin-top: 10px;"><?=Loc::getMessage("SEO_META_STOP_SCAN_BUTTON")?></span>
    </div>
</div>

<?
$APPLICATION->AddHeadScript('/bitrix/js/sotbit.seometa/scaner.js');



// filter of result list
$oFilter = new CAdminFilter(
    $sTableID."_filter_webmaster",
    array(
        Loc::getMessage("SEO_META_ID"),
        Loc::getMessage("SEO_META_NAME"),
        Loc::getMessage("SEO_META_STATUS"),
    )
);


?>

<form name="find_form" method="get" action="<?echo $APPLICATION->GetCurPage();?>">
    <?$oFilter->Begin();?>
        <tr>
            <td><b><?=Loc::getMessage("SEO_META_FIND")?>:</b></td>
            <td>
                <input type="text" size="25" name="find" value="<?echo htmlspecialchars($find)?>" title="<?=Loc::getMessage("SEO_META_FIND_TITLE")?>">
                <?
                $arr = array(
                    "reference" => array(
                        "ID",
                    ),
                    "reference_id" => array(
                        "id",
                    )
                );
                echo SelectBoxFromArray("find_type", $arr, $find_type, "", "");
                ?>
            </td>
        </tr>
        <tr>
            <td><?=Loc::getMessage("SEO_META_ID")?>:</td>
            <td>
                <input type="text" name="find_id" size="47" value="<?echo htmlspecialchars($find_id)?>">
            </td>
        </tr>
        <tr>
            <td><?=Loc::getMessage("SEO_META_NAME")?>:</td>
            <td>
                <input type="text" name="find_name" size="47" value="<?echo htmlspecialchars($find_name)?>">
            </td>
        </tr>
        <tr>
            <td><?=Loc::getMessage("SEO_META_STATUS")?>:</td>
            <td>
                <input type="text" name="find_status" size="47" value="<?echo htmlspecialchars($find_status)?>">
            </td>
        </tr>
    <?
    $oFilter->Buttons(array("table_id" => $sTableID, "url" => $APPLICATION->GetCurPage(), "form"=>"find_form"));
    $oFilter->End();
    ?>
</form>

<?

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


//if($CCSeoMeta->ReturnDemo() == 2) CAdminMessage::ShowMessage(array("MESSAGE" => Loc::getMessage("SEO_META_DEMO"), 'HTML' => true));
//if($CCSeoMeta->ReturnDemo() == 3) CAdminMessage::ShowMessage(array("MESSAGE" => Loc::getMessage("SEO_META_DEMO_END"), 'HTML' => true));

$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>