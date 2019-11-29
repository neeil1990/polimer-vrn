<?
use Sotbit\Seometa\ConditionTable;
use Sotbit\Seometa\SeometaUrlTable;
use Sotbit\Seometa\SitemapSectionTable;
use Bitrix\Iblock;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type;
use Bitrix\Main\Localization\Loc;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

Loc::loadMessages(__FILE__);

const MIN_SEO_TITLE = 50;
const MAX_SEO_TITLE = 70;

const MIN_SEO_KEY = 120;
const MAX_SEO_KEY = 150;

const MIN_SEO_DESCR = 130;
const MAX_SEO_DESCR = 180;

echo "<script src='/bitrix/js/catalog/core_tree.js'></script>";
echo "<link rel='stylesheet' href='/bitrix/panel/catalog/catalog_cond.css'>";

$id_module = 'sotbit.seometa';
Loader::includeModule('iblock');

if (!Loader::includeModule( 'iblock' ) || !Loader::includeModule( $id_module ))
	die();

//$CCSeoMeta = new CCSeoMeta();
//if (!$CCSeoMeta->getDemo())
//	return false;

\Bitrix\Main\Loader::includeModule("fileman");

// For menu
CJSCore::Init( array(
	"jquery"
) );

//$TagsObject = new \Sotbit\Seometa\Tags();
//$condition = \Sotbit\Seometa\ConditionTable::GetByID(17)->Fetch();
//$condition['SECTIONS'] = unserialize($condition['SECTIONS']);
//$Conditions = array('17' => $condition);
//$condition = \Sotbit\Seometa\ConditionTable::GetByID(18)->Fetch();
//$condition['SECTIONS'] = unserialize($condition['SECTIONS']);
//$Conditions[18] = $condition;
//
//$Tags = $TagsObject->GenerateTags($Conditions, array());
//echo '<pre>';
//print_r($Tags);
//exit();

if($_REQUEST['action'] == 'delete' && $ID > 0 )
{
	$result=ConditionTable::delete($ID);

	SeometaUrlTable::deleteByConditionId($ID);

	if($result->isSuccess())
	{
		LocalRedirect('/bitrix/admin/sotbit.seometa_list.php?lang='.LANGUAGE_ID);
	}
}

$POST_RIGHT = $APPLICATION->GetGroupRight( "sotbit.seometa" );
if ($POST_RIGHT == "D")
	$APPLICATION->AuthForm( GetMessage( "ACCESS_DENIED" ) );

IncludeModuleLangFile( __FILE__ );

$aTabs = array(
	array(
		"DIV" => "edit1",
		"TAB" => GetMessage( "SEO_META_EDIT_TAB_CONDITION" ),
		"ICON" => "main_user_edit",
		"TITLE" => GetMessage( "SEO_META_EDIT_TAB_CONDITION_TITLE" )
	),
	array(
		"DIV" => "edit2",
		"TAB" => GetMessage( "SEO_META_EDIT_TAB_META" ),
		"ICON" => "main_user_edit",
		"TITLE" => GetMessage( "SEO_META_EDIT_TAB_META_TITLE" )
	),
	array(
		"DIV" => "edit4",
		"TAB" => GetMessage( "SEO_META_EDIT_TAB_URL" ),
		"ICON" => "main_user_edit",
		"TITLE" => GetMessage( "SEO_META_EDIT_TAB_URL" )
	),
	array(
		"DIV" => "edit3",
		"TAB" => GetMessage( "SEO_META_EDIT_TAB_VIDEO" ),
		"ICON" => "main_user_edit",
		"TITLE" => GetMessage( "SEO_META_EDIT_TAB_VIDEO" )
	)
);

$tabControl = new CAdminForm( "tabControl", $aTabs );

$ID = intval( $ID );

if($ID > 0)
{
	$conditionRes = \Sotbit\Seometa\ConditionTable::getById($ID);

	$condition = $conditionRes->fetch();
}

//print_r($condition);

//printr(unserialize($condition["RULE"]));

if (isset( $_REQUEST["NAME"] ) && $_REQUEST["NAME"])
	$condition["NAME"] = $_REQUEST["NAME"];
if (isset( $_REQUEST["ACTIVE"] ) && $_REQUEST["ACTIVE"])
	$condition["ACTIVE"] = $_REQUEST["ACTIVE"];
if (isset( $_REQUEST["SEARCH"] ) && $_REQUEST["SEARCH"])
	$condition["SEARCH"] = $_REQUEST["SEARCH"];
if (isset( $_REQUEST["SORT"] ) && $_REQUEST["SORT"])
	$condition["SORT"] = $_REQUEST["SORT"];
if (isset( $_REQUEST["section"] ) && $_REQUEST["section"])
	$condition["CATEGORY_ID"] = $_REQUEST["section"];
if (isset( $_REQUEST["CATEGORY_ID"] ) && $_REQUEST["CATEGORY_ID"])
	$condition["CATEGORY_ID"] = $_REQUEST["CATEGORY_ID"];
if (isset( $_REQUEST["SITES"] ) && $_REQUEST["SITES"])
	$condition["SITES"] = $_REQUEST["SITES"];
/*if (isset( $_REQUEST["TYPE_OF_CONDITION"] ) && $_REQUEST["TYPE_OF_CONDITION"])
	$condition["TYPE_OF_CONDITION"] = $_REQUEST["TYPE_OF_CONDITION"];*/
if (isset( $_REQUEST["TYPE_OF_INFOBLOCK"] ) && $_REQUEST["TYPE_OF_INFOBLOCK"])
	$condition["TYPE_OF_INFOBLOCK"] = $_REQUEST["TYPE_OF_INFOBLOCK"];
if (isset( $_REQUEST["INFOBLOCK"] ) && $_REQUEST["INFOBLOCK"])
	$condition["INFOBLOCK"] = $_REQUEST["INFOBLOCK"];
if (isset( $_REQUEST["SECTIONS"] ) && $_REQUEST["SECTIONS"])
	$condition["SECTIONS"] = $_REQUEST["SECTIONS"];
if (isset( $_REQUEST["RULE"] ) && $_REQUEST["RULE"])
	$condition["RULE"] = $_REQUEST["RULE"];
if (isset( $_REQUEST["META"] ) && $_REQUEST["META"])
	$condition["META"] = $_REQUEST["META"];
if (isset( $_REQUEST["NO_INDEX"] ) && $_REQUEST["NO_INDEX"])
	$condition["NO_INDEX"] = $_REQUEST["NO_INDEX"];
if (isset( $_REQUEST["STRONG"] ) && $_REQUEST["STRONG"])
	$condition["STRONG"] = $_REQUEST["STRONG"];
if (isset( $_REQUEST["PRIORITY"] ) && $_REQUEST["PRIORITY"])
	$condition["PRIORITY"] = $_REQUEST["PRIORITY"];
if (isset( $_REQUEST["CHANGEFREQ"] ) && $_REQUEST["CHANGEFREQ"])
	$condition["CHANGEFREQ"] = $_REQUEST["CHANGEFREQ"];
if (isset( $_REQUEST["FILTER_TYPE"] ) && $_REQUEST["FILTER_TYPE"])
	$condition["FILTER_TYPE"] = $_REQUEST["FILTER_TYPE"];
if (isset( $_REQUEST["TAG"] ) && $_REQUEST["TAG"])
	$condition["TAG"] = $_REQUEST["TAG"];
if (isset( $_REQUEST["CONDITION_TAG"] ) && $_REQUEST["CONDITION_TAG"])
	$condition["CONDITION_TAG"] = $_REQUEST["CONDITION_TAG"];
if (isset( $_REQUEST["STRICT_RELINKING"] ) && $_REQUEST["STRICT_RELINKING"])
	$condition["STRICT_RELINKING"] = $_REQUEST["STRICT_RELINKING"];

$message = null;
// ACTION
if (isset( $_REQUEST['action'] ))
{
	if ($_REQUEST['action'] == "copy")
	{
		if ($ID > 0)
		{
			$conditionRes = ConditionTable::getById( $ID );
			$condition = $conditionRes->fetch();
			$arFields = Array(
				"ACTIVE" => $condition['ACTIVE'],
				"SEARCH" => $condition['SEARCH'],
				"STRONG" => $condition['STRONG'],
				"NAME" => $condition['NAME'],
				"CATEGORY_ID" => $condition['CATEGORY_ID'],
				"SORT" => $condition['SORT'],
				"SITES" => $condition['SITES'],
				//"TYPE_OF_CONDITION" => $condition['TYPE_OF_CONDITION'],
				"TYPE_OF_INFOBLOCK" => $condition['TYPE_OF_INFOBLOCK'],
				"INFOBLOCK" => $condition['INFOBLOCK'],
				"DATE_CHANGE" => new Type\DateTime( date( 'Y-m-d H:i:s' ), 'Y-m-d H:i:s' ),
				"SECTIONS" => $condition['SECTIONS'],
				"RULE" => $condition['RULE'],
				"META" => $condition['META'],
				"FILTER_TYPE" => $condition['FILTER_TYPE'],
				"NO_INDEX" => $condition['NO_INDEX'],
				"TAG" => $condition['TAG'],
				"CONDITION_TAG" => $condition['CONDITION_TAG'],
				"PRIORITY" => $condition['PRIORITY'],
				"CHANGEFREQ" => $condition['CHANGEFREQ']
			);
			$result = ConditionTable::add( $arFields );
			if ($result->isSuccess()){
				$ID = $result->getId();
				LocalRedirect( "/bitrix/admin/sotbit.seometa_edit.php?ID=" . $ID . "lang=" . LANG);
			}
		}
	}
	elseif ($_REQUEST['action'] == "generate_chpu")
	{
		if ($ID > 0) {
			//\Bitrix\Main\Loader::includeModule('sotbit.seometa');
			$chpu = ConditionTable::generateUrlForCondition($ID);
		}
	}
}

// POST
if ($REQUEST_METHOD == "POST" && ($save != "" || $apply != "") && $POST_RIGHT == "W" && check_bitrix_sessid())
{

    $bVarsFromForm = true;

    if(is_array($ELEMENT_FILE))
    {
        $arELEMENT_FILE = \CIBlock::makeFileArray($ELEMENT_FILE, ${"ELEMENT_FILE_del"} === "Y", $_REQUEST['ELEMENT_FILE_descr']);
        $fid = CFile::SaveFile($arELEMENT_FILE, "seometa");
    }
    elseif(is_numeric($ELEMENT_FILE))
        $fid = $ELEMENT_FILE;
    else {
        $arELEMENT_FILE = \CIBlock::makeFileArray($ELEMENT_FILE);
        $fid = CFile::SaveFile($arELEMENT_FILE, "seometa");
    }
    
	$META_TEMPLATE['ELEMENT_TOP_DESC'] = $ELEMENT_TOP_DESC;
	$META_TEMPLATE['ELEMENT_BOTTOM_DESC'] = $ELEMENT_BOTTOM_DESC;
	$META_TEMPLATE['ELEMENT_ADD_DESC'] = $ELEMENT_ADD_DESC;
	$META_TEMPLATE['ELEMENT_TOP_DESC_TYPE'] = $ELEMENT_TOP_DESC_TYPE;
	$META_TEMPLATE['ELEMENT_BOTTOM_DESC_TYPE'] = $ELEMENT_BOTTOM_DESC_TYPE;
    $META_TEMPLATE['ELEMENT_ADD_DESC_TYPE'] = $ELEMENT_ADD_DESC_TYPE;
    $META_TEMPLATE['ELEMENT_FILE'] = $fid;
    
    if(isset($ELEMENT_FILE_del) && $ELEMENT_FILE_del == 'Y')
        unset($META_TEMPLATE['ELEMENT_FILE']);
   
	$CONDITIONS = '';
	$obCond3 = new SMCondTree();
	$boolCond = $obCond3->Init( BT_COND_MODE_PARSE, BT_COND_BUILD_CATALOG, array() );

	$CONDITIONS = $obCond3->Parse( $rule );
	if (!isset( $SITES ))
		$SITES = array();
	$arFields = Array(
		"ACTIVE" => ($ACTIVE != "Y" ? "N" : "Y"),
		"SEARCH" => ($SEARCH != "Y" ? "N" : "Y"),
		"STRONG" => ($STRONG != "Y" ? "N" : "Y"),
		"NAME" => $NAME,
		"CATEGORY_ID" => $CATEGORY_ID,
		"SORT" => $SORT,
		"SITES" => serialize( $SITES ),
		//"TYPE_OF_CONDITION" => $TYPE_OF_CONDITION,
		"TYPE_OF_INFOBLOCK" => $TYPE_OF_INFOBLOCK,
		"INFOBLOCK" => $INFOBLOCK,
		"DATE_CHANGE" => new Type\DateTime( date( 'Y-m-d H:i:s' ), 'Y-m-d H:i:s' ),
		"SECTIONS" => serialize( $SECTIONS ),
		"RULE" => serialize( $CONDITIONS ),
		"META" => serialize( $META_TEMPLATE ),
		"NO_INDEX" => ($NO_INDEX != "Y" ? "N" : "Y"),
		"TAG" => $TAG,
		"CONDITION_TAG" => serialize($CONDITION_TAG),
		"STRICT_RELINKING" => ($STRICT_RELINKING != "Y" ? "N" : "Y"),
		"PRIORITY" => $PRIORITY,
		"CHANGEFREQ" => $CHANGEFREQ,
		"FILTER_TYPE" => $FILTER_TYPE
	);
	
	if ($ID > 0)
	{
		$result = ConditionTable::update( $ID, $arFields );
		if (!$result->isSuccess()){
			$errors = $result->getErrorMessages();
			$res = false;
		} else {
			$res = true;
		}
	}
	else
	{
		$result = ConditionTable::add( $arFields );
		if($result->isSuccess())
		{
			$ID = $result->getId();
			$res = true;
		}
		else
		{
			$errors = $result->getErrorMessages();
			$res = false;
		}
	}
	if ($res)
	{
		if ($apply != "")
			LocalRedirect( "/bitrix/admin/sotbit.seometa_edit.php?ID=" . $ID . "&mess=ok&lang=" . LANG . "&" . $tabControl->ActiveTabParam() );
		else {
			if($CATEGORY_ID>0)
				LocalRedirect( "/bitrix/admin/sotbit.seometa_list.php?lang=" . LANG.'&parent='.$CATEGORY_ID );
			else {
				if(isset($_GET['INFOBLOCK'])&&!empty($_GET['INFOBLOCK'])
					&&isset($_GET['SECTIONS'])&&!empty($_GET['SECTIONS'])
					&&isset($_GET['TYPE_OF_INFOBLOCK'])&&!empty($_GET['TYPE_OF_INFOBLOCK'])){
					LocalRedirect("/bitrix/admin/iblock_section_edit.php?IBLOCK_ID=".$condition['INFOBLOCK']."&type=".$condition['TYPE_OF_INFOBLOCK']."&ID=".$condition['SECTIONS'][0]."&find_section_section=0&lang=" . LANG);
				} else {
					LocalRedirect( "/bitrix/admin/sotbit.seometa_list.php?lang=" . LANG );
				}
			}
		}
	}
}
$APPLICATION->SetTitle( ($ID > 0 ? GetMessage( "SEO_META_EDIT_EDIT" ) . $ID : GetMessage( "SEO_META_EDIT_ADD" )) );
require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

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

//if ($CCSeoMeta->ReturnDemo() == 2)
//	CAdminMessage::ShowMessage( array(
//		"MESSAGE" => GetMessage( "SEO_META_DEMO" ),
//		'HTML' => true
//	));
//if ($CCSeoMeta->ReturnDemo() == 3)
//	CAdminMessage::ShowMessage( array(
//		"MESSAGE" => GetMessage( "SEO_META_DEMO_END" ),
//		'HTML' => true
//	));
$arrSubmenu = array(
	array(
		"TEXT" => GetMessage( "SEO_META_EDIT_LIST" ),
		"TITLE" => GetMessage( "SEO_META_EDIT_LIST_TITLE" ),
		"LINK" => "sotbit.seometa_list.php?lang=" . LANG,
		"ICON" => "btn_list"
	)
);

$condition['SECTIONS'] = is_array($condition['SECTIONS'])?$condition['SECTIONS']:unserialize($condition['SECTIONS']);

if(isset($condition['INFOBLOCK']) && !empty($condition['INFOBLOCK'])
	&& isset($condition['SECTIONS']) && !empty($condition['SECTIONS'])
	&& isset($condition['TYPE_OF_INFOBLOCK']) && !empty($condition['TYPE_OF_INFOBLOCK']))
{
	$arrSubmenu[]=array(
		"TEXT" => GetMessage( "SEO_META_EDIT_SECTION_BACK" ),
		"TITLE" => GetMessage( "SEO_META_EDIT_SECTION_BACK_TITLE" ),
	    "LINK" => "iblock_section_edit.php?IBLOCK_ID=".$condition['INFOBLOCK']."&type=".$condition['TYPE_OF_INFOBLOCK']."&ID=".$condition['SECTIONS'][0]."&find_section_section=0&lang=" . LANG,
	    //"ACTION" => CAdminList::ActionRedirect("iblock_section_edit.php?IBLOCK_ID=".$condition['INFOBLOCK']."&type=".$condition['TYPE_OF_INFOBLOCK']."&ID=".$condition['SECTIONS'][0]."&find_section_section=0&lang=" . LANG),
		//"ACTION" => CAdminList::ActionRedirect("iblock_section_edit.php?IBLOCK_ID=".$_GET['INFOBLOCK']."&type=".$_GET['TYPE_OF_INFOBLOCK']."&ID=".$_GET['FROM']."&find_section_section=".$_REQUEST['SECT_FROM']."&lang=" . LANG),
	);
}
$aMenu[]= array(
	"TEXT" => GetMessage( "SEO_META_EDIT_BACK" ),
	"TITLE" => GetMessage( "SEO_META_EDIT_BACK_TITLE" ),
	"ICON" => "btn_list",
	'MENU' =>$arrSubmenu,
);
if ($ID > 0)
{
	$aMenu[] = array(
		"SEPARATOR" => "Y"
	);
	$aMenu[] = array(
		"TEXT" => GetMessage( "SEO_META_EDIT_ADD" ),
		"TITLE" => GetMessage( "SEO_META_EDIT_ADD_TITLE" ),
		"LINK" => "sotbit.seometa_edit.php?lang=" . LANG,
		"ICON" => "btn_new"
	);
	$aMenu[] = array(
		"TEXT" => GetMessage( "SEO_META_EDIT_COPY" ),
		"TITLE" => GetMessage( "SEO_META_EDIT_COPY_TITLE" ),
		"LINK" => "sotbit.seometa_edit.php?action=copy&ID=" . $ID . "lang=" . LANG . "&" . bitrix_sessid_get() . "';",
		"ICON" => "btn_new"
	);
	$aMenu[] = array(
		"TEXT" => GetMessage( "SEO_META_EDIT_DEL" ),
		"TITLE" => GetMessage( "SEO_META_EDIT_DEL_TITLE" ),
		"LINK" => "javascript:if(confirm('" . GetMessage( "SEO_META_EDIT_DEL_CONF" ) . "'))window.location='sotbit.seometa_edit.php?ID=" . $ID . "&action=delete&lang=" . LANG . "&" . bitrix_sessid_get() . "';",
		"ICON" => "btn_delete"
	);
}

$context = new CAdminContextMenu( $aMenu );
$context->Show();

if(isset($errors) && is_array($errors) && count($errors)>0){
	CAdminMessage::ShowMessage( array(
		"MESSAGE" => $errors[0]
	) );
}
if ($_REQUEST["mess"] == "ok" && $ID > 0)
	CAdminMessage::ShowMessage( array(
		"MESSAGE" => GetMessage( "SEO_META_EDIT_SAVED" ),
		"TYPE" => "OK"
	));

// Calculate start values
//***All section***
$AllSections['REFERENCE_ID'][0]=0;
$AllSections['REFERENCE'][0]=GetMessage( "SEO_META_CHECK_CATEGORY" );
$RsAllSections = SitemapSectionTable::getList(array(
	'select' => array('*'),
	'filter' =>array('ACTIVE'=>'Y'),
	'order' => array('SORT' => 'ASC')
));
    while($AllSection=$RsAllSections->Fetch())
    {
        $AllSections['REFERENCE_ID'][] = $AllSection['ID'];
        $AllSections['REFERENCE'][] = $AllSection['NAME'];
    }

// Menu for meta
$PropMenu = CCSeoMeta::PropMenu( $condition['INFOBLOCK'] );
$PropMenuTemplate = CCSeoMeta::PropMenuTemplate( $condition['INFOBLOCK'] );
// Meta
if (isset( $condition["META"] ))
	$Meta = unserialize( $condition["META"] );
else
	$Meta = array();

/*$TypeOfCondition = array(
	'filter' => GetMessage( "SEO_META_EDIT_TYPE_OF_CONDITION_FILTER" )
);*/
$arIBlockTypeSel = array();

$SitesAll = array();
$rsSites = CSite::GetList( $by = "sort", $order = "desc", Array() );
while ( $arSite = $rsSites->Fetch() )
{
	array_push( $SitesAll, $arSite['LID'] );
}

$arIBlockType = CIBlockParameters::GetIBlockTypes();
foreach ( $arIBlockType as $code => $val )
{
	$arIBlockTypeSel["REFERENCE_ID"][] = $code;
	$arIBlockTypeSel["REFERENCE"][] = $val;
}

if ($condition["TYPE_OF_INFOBLOCK"])
{
	$catalogs = array();
    $rsIBlocks = CIBlockElement::GetList( array(), array(), false, false, array(
		'IBLOCK_ID'
	) );
	while ( $arIBlock = $rsIBlocks->Fetch() )
	{
		array_push( $catalogs, $arIBlock['IBLOCK_ID'] );
	}
	
	$rsIBlock = CIBlock::GetList(
		array(
			"sort" => "asc"
		),
		array(
			"TYPE" => $condition["TYPE_OF_INFOBLOCK"],
			"ACTIVE" => "Y"
		));
	while ( $arr = $rsIBlock->Fetch() )
	{
		if (in_array( $arr["ID"], $catalogs ))
		{
			$arIBlockSel["REFERENCE_ID"][] = $arr["ID"];
			$arIBlockSel["REFERENCE"][] = "[" . $arr["ID"] . "] " . $arr["NAME"];
		}
	}
}

$FilterType = array(
	'default' => GetMessage('SEO_META_FILTERS_default'),
	'bitrix_chpu' => GetMessage('SEO_META_FILTERS_bitrix_chpu'),
	'bitrix_not_chpu' => GetMessage('SEO_META_FILTERS_bitrix_not_chpu'),
	'misshop_chpu' => GetMessage('SEO_META_FILTERS_misshop_chpu'),
	'combox_chpu' => GetMessage('SEO_META_FILTERS_combox_chpu'),
	'combox_not_chpu' => GetMessage('SEO_META_FILTERS_combox_not_chpu')
);

$Priority = array(
	'0.0' => '0.0',
	'0.1' => '0.1',
	'0.2' => '0.2',
	'0.3' => '0.3',
	'0.4' => '0.4',
	'0.5' => '0.5',
	'0.6' => '0.6',
	'0.7' => '0.7',
	'0.8' => '0.8',
	'0.9' => '0.9',
	'1.0' => '1.0'
);

$ChangeFreq = array(
	'always' => GetMessage( "SEO_META_EDIT_CHANGEFREQ_ALWAYS" ),
	'hourly' => GetMessage( "SEO_META_EDIT_CHANGEFREQ_HOURLY" ),
	'daily' => GetMessage( "SEO_META_EDIT_CHANGEFREQ_DAILY" ),
	'weekly' => GetMessage( "SEO_META_EDIT_CHANGEFREQ_WEEKLY" ),
	'monthly' => GetMessage( "SEO_META_EDIT_CHANGEFREQ_MONTHLY" ),
	'yearly' => GetMessage( "SEO_META_EDIT_CHANGEFREQ_YEARLY" ),
	'never' => GetMessage( "SEO_META_EDIT_CHANGEFREQ_NEVER" )
);

$sectionLinc = array();
$arFilter = array(
	'ACTIVE' => 'Y',
	'IBLOCK_ID' => $condition["INFOBLOCK"],
	'GLOBAL_ACTIVE' => 'Y'
);
$arSelect = array(
	'ID',
	'NAME',
	'DEPTH_LEVEL'
);
$arOrder = array(
	"left_margin"=>"asc"
);
if ($condition["INFOBLOCK"])
{
	$rsSections = CIBlockSection::GetList( $arOrder, $arFilter, false, $arSelect );
	while ( $arSection = $rsSections->GetNext() )
	{
		$sectionLinc["REFERENCE_ID"][] = $arSection["ID"];
		$sectionLinc["REFERENCE"][] = "[" . $arSection["ID"] . "] ". str_repeat(" . ", $arSection["DEPTH_LEVEL"]) . $arSection["NAME"];
	}
}

$tabControl->Begin( array(
	"FORM_ACTION" => $APPLICATION->GetCurPage()
) );

$tabControl->BeginNextFormTab();


$tabControl->AddViewField( 'ID', GetMessage( "SEO_META_EDIT_ID" ), $ID, false ); // ID
$tabControl->AddCheckBoxField( "ACTIVE", GetMessage( "SEO_META_EDIT_ACT" ), false, "Y", ($condition['ACTIVE'] == "Y" || !isset( $condition['ACTIVE'] )) );


$tabControl->BeginCustomField( "SEARCH", GetMessage( 'SEO_META_EDIT_SEARCH' ), false );
?>
<tr id="SEARCH">
	<td width="40%" valign="top"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
	<td width="60%">
		<input type="checkbox" name="SEARCH" value="Y" <?php echo ($condition['SEARCH'] == "Y" || !isset( $condition['SEARCH'] ))?'checked="checked"':'';?> id="designed_checkbox_SEARCH" class="adm-designed-checkbox">
		<label class="adm-designed-checkbox-label" for="designed_checkbox_SEARCH" title=""></label>
		<div class="adm-info-message-wrap">
			<div class="adm-info-message">
				<?php echo GetMessage('SEO_META_EDIT_SEARCH_NOTE');?>
			</div>
		</div>
	</td>
</tr>
<?
$tabControl->EndCustomField( "SEARCH" );


//$tabControl->AddCheckBoxField( "SEARCH", GetMessage( "SEO_META_EDIT_SEARCH" ), false, "Y", ($condition['SEARCH'] == "Y" || !isset( $condition['SEARCH'] )) );
$tabControl->AddCheckBoxField( "NO_INDEX", GetMessage( "SEO_META_EDIT_INDEX" ), false, "Y", ($condition['NO_INDEX'] == "Y") );


$tabControl->AddCheckBoxField( "STRONG", GetMessage( "SEO_META_EDIT_STRONG" ), false, "Y", ($condition['STRONG'] == "Y" || !isset( $condition['STRONG'] )) );

$tabControl->BeginCustomField( "STRONG_NOTE", "");
?>
<tr>
	<td></td>
	<td>
		<?=BeginNote();?>
		<?=GetMessage("SEO_META_EDIT_STRONG_NOTE")?>
		<?=EndNote();?>
	</td>
</tr>
<?
$tabControl->EndCustomField( "STRONG_NOTE" );


$tabControl->AddEditField( "NAME", GetMessage( "SEO_META_EDIT_NAME" ), true, array(
	"size" => 44,
	"maxlength" => 255
), htmlspecialcharsbx( $condition['NAME'] ) );


$tabControl->AddEditField( "SORT", GetMessage( "SEO_META_EDIT_SORT" ), true, array(
	"size" => 6,
	"maxlength" => 255
), htmlspecialcharsbx( isset( $condition['SORT'] ) && !empty( $condition['SORT'] ) ) ? $condition['SORT'] : 100 );

$tabControl->BeginCustomField( "SORT_NOTE", "");
?>
<tr>
	<td></td>
	<td>
		<?=BeginNote();?>
		<?echo GetMessage("SEO_META_EDIT_SORT_NOTE")?>
		<?=EndNote();?>
	</td>
</tr>
<?
$tabControl->EndCustomField( "SORT_NOTE" );


$tabControl->AddViewField( 'DATE_CHANGE_TEXT', GetMessage( "SEO_META_EDIT_DATE_CHANGE" ), $condition['DATE_CHANGE'], false );

$tabControl->BeginCustomField( "CATEGORY_ID", GetMessage( 'SEO_META_EDIT_CATEGORY_ID' ), false );
?>
<tr id="CATEGORY_ID">
	<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
	<td width="60%">
		<?echo SelectBoxFromArray('CATEGORY_ID', $AllSections, $condition['CATEGORY_ID'],'',false,'','style="min-width:350px"');?>
	</td>
</tr>
<?
$tabControl->EndCustomField( "CATEGORY_ID" );


$tabControl->BeginCustomField( "SITES", GetMessage( 'SEO_META_EDIT_SITES' ), false );
?>
<tr id="tr_SITES">
	<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
	<td>
		<?=CLang::SelectBoxMulti("SITES", (!empty($condition['SITES']) && !is_array($condition['SITES']))?unserialize($condition['SITES']):$SitesAll);?>
	</td>
</tr>
<?

$tabControl->EndCustomField( "SITES" );

//$tabControl->AddDropDownField( "TYPE_OF_CONDITION", GetMessage( 'SEO_META_EDIT_TYPE_OF_CONDITION' ), true, $TypeOfCondition, $condition['TYPE_OF_CONDITION'] );

$tabControl->BeginCustomField( "TYPE_OF_INFOBLOCK", GetMessage( 'SEO_META_EDIT_TYPE_OF_INFOBLOCK' ), false );
?>
<tr id="tr_TYPE_OF_INFOBLOCK">
	<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
	<td width="60%">
		<?
		echo SelectBoxFromArray( 'TYPE_OF_INFOBLOCK', $arIBlockTypeSel, $condition['TYPE_OF_INFOBLOCK'], '', 'style="min-width: 350px; margin-right: 7px;"', false, '' );
		echo '<input type="submit" name="refresh" value="OK" />';
		?>
	</td>
</tr>
<?
$tabControl->EndCustomField( "TYPE_OF_INFOBLOCK" );

$tabControl->BeginCustomField( "INFOBLOCK", GetMessage( 'SEO_META_EDIT_INFOBLOCK' ), false );
?>
<tr id="tr_INFOBLOCK">
	<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
	<td width="60%">
		<?
		echo SelectBoxFromArray( 'INFOBLOCK', $arIBlockSel, $condition['INFOBLOCK'], '', 'style="min-width: 350px; margin-right: 7px;"', false, '' );
		echo '<input type="submit" name="refresh" value="OK" />';
		?>
	</td>
</tr>

<?
$tabControl->EndCustomField( "INFOBLOCK" );

$tabControl->BeginCustomField( "SECTIONS", GetMessage( 'SEO_META_EDIT_SECTIONS' ), false );
?>
<tr id="SECTIONS">
	<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
	<td width="60%">
		<?echo SelectBoxMFromArray('SECTIONS'.'[]', $sectionLinc, is_array($condition['SECTIONS'])?$condition['SECTIONS']:unserialize($condition['SECTIONS']),'',false,'','style="min-width: 350px;"');?>
	</td>
</tr>
<?$tabControl->EndCustomField( "SECTIONS" );?>



<?$tabControl->BeginCustomField("CONDITIONS", GetMessage('SEO_META_EDIT_SECTIONS_COND') . ":", false);?>
<tr id="tr_CONDITIONS">
	<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
	<td width="60%">
		<div id="tree" style="position: relative; z-index: 1;"></div>
		<?
		if(!is_array( $condition['RULE'] ))
		{
			if (CheckSerializedData( $condition['RULE'] ))
			{
				$condition['RULE'] = unserialize( $condition['RULE'] );
			}
			else
			{
				$condition['RULE'] = '';
			}
		}
		$obCond = new SMCondTree();
		$boolCond = $obCond->Init( BT_COND_MODE_DEFAULT, BT_COND_BUILD_CATALOG, array(
            'FORM_NAME' => 'tabControl_form',
            'CONT_ID' => 'tree',
            'JS_NAME' => 'JSCond'
        ));
		if(!$boolCond)
		{
			if ($ex = $APPLICATION->GetException())
				echo $ex->GetString() . "<br>";
		}
		else
		{
		    if(Loader::includeModule('catalog'))
		        $product_block = CCatalog::GetList(array(), array('IBLOCK_ID' => $condition['INFOBLOCK']), false, false, array('OFFERS_IBLOCK_ID'))->fetch();
		    else
		        $product_block = CIBlockElement::GetList(array(), array('IBLOCK_ID' => $condition['INFOBLOCK']), false, false, array('OFFERS_IBLOCK_ID'))->fetch();

			$obCond->Show( $condition['RULE'], array($condition['INFOBLOCK'], $product_block['OFFERS_IBLOCK_ID']));
		}
		?>
	</td>
</tr>
<script>
	BX.ready(function() {
		<?
		CJSCore::Init(array(
			"jquery"
		));
		?>
		$('body').on('click', 'a[id*="add_link"]', function(e) {
			e.preventDefault();
			$(this).next('select').show();
			$(this).hide();
		});
	});
</script>
<?
$APPLICATION->AddHeadString('<style>span.condition-alert{display: none !important;}</style>', true);
$tabControl->EndCustomField("CONDITIONS");
?>



<?
$tabControl->AddDropDownField("FILTER_TYPE", GetMessage('SEO_META_EDIT_FILTER_TYPE'), false, $FilterType, $condition['FILTER_TYPE']);

$tabControl->AddDropDownField("PRIORITY", GetMessage('SEO_META_EDIT_PRIORITY'), true, $Priority, $condition['PRIORITY']);
?>



<?$tabControl->BeginCustomField("PRIORITY_NOTE", "");?>
<tr>
	<td></td>
	<td>
		<?=BeginNote();?>
		<?=GetMessage("SEO_META_EDIT_PRIORITY_NOTE")?>
		<?=EndNote();?>
	</td>
</tr>
<?$tabControl->EndCustomField("PRIORITY_NOTE");?>



<?
$tabControl->AddDropDownField( "CHANGEFREQ", GetMessage( 'SEO_META_EDIT_CHANGEFREQ' ), true, $ChangeFreq, $condition['CHANGEFREQ'] );

$tabControl->BeginCustomField( "CHANGEFREQ_NOTE", "");
?>
<tr>
	<td></td>
	<td>
		<?=BeginNote();?>
		<?=GetMessage("SEO_META_EDIT_CHANGEFREQ_NOTE")?>
		<?=EndNote();?>
	</td>
</tr>
<?
$tabControl->EndCustomField( "CHANGEFREQ_NOTE" );


$tabControl->BeginCustomField( "HID", '', false );
?>
<?echo bitrix_sessid_post();?>
<input type="hidden" name="lang" value="<?=LANG?>">
<?if($ID>0 && !$bCopy):?>
<input type="hidden" name="ID" value="<?=$ID?>">
<?endif;?>
<?
$tabControl->EndCustomField( "HID" );

$tabControl->BeginNextFormTab(); // Metatags ?>
<?$tabControl->BeginCustomField("GROUP_ELEMENT", GetMessage('SEO_META_EDIT_GROUP_ELEMENT'),false);?>
<tr class="heading">
	<td colspan="3"><?echo $tabControl->GetCustomLabelHTML();?></td>
</tr>
<?$tabControl->EndCustomField("GROUP_ELEMENT");?>

<?$tabControl->BeginCustomField("ELEMENT_TITLE", GetMessage('SEO_META_EDIT_ELEMENT_TITLE'),false);?>
<tr class="adm-detail-valign-top">
	<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
	<td width="50%"><textarea style="width: 90%" class="count_symbol"
			name="META_TEMPLATE[ELEMENT_TITLE]"><?=(isset($Meta['ELEMENT_TITLE'])&&!empty($Meta['ELEMENT_TITLE']))?$Meta['ELEMENT_TITLE']:'' ?></textarea>
		<div class="count_symbol_print">
			<?echo GetMessage('SEO_META_SYMBOL_COUNT_FROM').MIN_SEO_TITLE.' - '.MAX_SEO_TITLE;?>
			<span class="meta_title"></span>
			<div class="progressbar" data-min="<?php echo MIN_SEO_TITLE;?>" data-max="<?php echo MAX_SEO_TITLE;?>"></div>
		</div>
	</td>
	<td width="10%" align="left">
	<?=$PropMenu?>
</td>
</tr>
<?$tabControl->EndCustomField("ELEMENT_TITLE");?>

<?$tabControl->BeginCustomField("ELEMENT_KEYWORDS", GetMessage('SEO_META_EDIT_ELEMENT_KEYWORDS'),false);?>
<tr class="adm-detail-valign-top">
	<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
	<td width="50%"><textarea style="width: 90%" class="count_symbol"
			name="META_TEMPLATE[ELEMENT_KEYWORDS]"><?=(isset($Meta['ELEMENT_KEYWORDS'])&&!empty($Meta['ELEMENT_KEYWORDS']))?$Meta['ELEMENT_KEYWORDS']:'' ?></textarea>
	   <div class="count_symbol_print">
			<?echo GetMessage('SEO_META_SYMBOL_COUNT_FROM').MIN_SEO_KEY.' - '.MAX_SEO_KEY;?>
			<span class="meta_key"></span>
			<div class="progressbar" data-min="<?php echo MIN_SEO_KEY;?>" data-max="<?php echo MAX_SEO_KEY;?>"></div>
	   </div>
	</td>
	<td width="10%" align="left">
	<?=$PropMenu?>
</td>
</tr>
<?$tabControl->EndCustomField("ELEMENT_KEYWORDS");?>

<?$tabControl->BeginCustomField("ELEMENT_DESCRIPTION", GetMessage('SEO_META_EDIT_ELEMENT_DESCRIPTION'),false);?>
<tr class="adm-detail-valign-top">
	<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
	<td width="50%"><textarea style="width: 90%" class="count_symbol"
			name="META_TEMPLATE[ELEMENT_DESCRIPTION]"><?=(isset($Meta['ELEMENT_DESCRIPTION'])&&!empty($Meta['ELEMENT_DESCRIPTION']))?$Meta['ELEMENT_DESCRIPTION']:'' ?></textarea>
		<div class="count_symbol_print">
			<?echo GetMessage('SEO_META_SYMBOL_COUNT_FROM').MIN_SEO_DESCR.' - '.MAX_SEO_DESCR;?>
			<span class="meta_descr"></span>
			<div class="progressbar" data-min="<?php echo MIN_SEO_DESCR;?>" data-max="<?php echo MAX_SEO_DESCR;?>"></div>
		</div>
	</td>
	<td width="10%" align="left">
	<?=$PropMenu?>
	</td>
</tr>
<?$tabControl->EndCustomField("ELEMENT_DESCRIPTION");?>

<?$tabControl->BeginCustomField("ELEMENT_PAGE_TITLE", GetMessage('SEO_META_EDIT_ELEMENT_PAGE_TITLE'),false);?>
<tr class="adm-detail-valign-top">
	<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
	<td width="50%"><textarea style="width: 90%"
			name="META_TEMPLATE[ELEMENT_PAGE_TITLE]"><?=(isset($Meta['ELEMENT_PAGE_TITLE'])&&!empty($Meta['ELEMENT_PAGE_TITLE']))?$Meta['ELEMENT_PAGE_TITLE']:'' ?></textarea>
	</td>
	<td width="10%" align="left">
	<?=$PropMenu?>
</td>
</tr>
<?$tabControl->EndCustomField("ELEMENT_PAGE_TITLE");?>

<?$tabControl->BeginCustomField("ELEMENT_BREADCRUMB_TITLE", GetMessage('SEO_META_EDIT_ELEMENT_BREADCRUMB_TITLE'),false);?>
<tr class="adm-detail-valign-top">
	<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
	<td width="50%"><textarea style="width: 90%"
			name="META_TEMPLATE[ELEMENT_BREADCRUMB_TITLE]"><?=(isset($Meta['ELEMENT_BREADCRUMB_TITLE'])&&!empty($Meta['ELEMENT_BREADCRUMB_TITLE']))?$Meta['ELEMENT_BREADCRUMB_TITLE']:'' ?></textarea>
	</td>
	<td width="10%" align="left">
	<?=$PropMenu?>
</td>
</tr>
<?$tabControl->EndCustomField("ELEMENT_BREADCRUMB_TITLE");?>

<?$tabControl->BeginCustomField("ELEMENT_TOP_DESC", GetMessage('SEO_META_EDIT_ELEMENT_TOP_DESC'),false);?>
<tr class="adm-detail-valign-top">
	<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
	<td width="50%">
		<?
		CFileMan::AddHTMLEditorFrame(
				"ELEMENT_TOP_DESC",
				(isset( $Meta['ELEMENT_TOP_DESC'] ) && !empty( $Meta['ELEMENT_TOP_DESC'] )) ? $Meta['ELEMENT_TOP_DESC'] : '',
				"ELEMENT_TOP_DESC_TYPE",
				"html",
				array(
						'height' => 150,
						'width' => '100%'
				),
				"N",
				0,
				"",
				"",
				"ru"//��� ����
		);
		/*$APPLICATION->IncludeComponent( "bitrix:fileman.light_editor", ".default", Array(
				"CONTENT" => (isset( $Meta['ELEMENT_TOP_DESC'] ) && !empty( $Meta['ELEMENT_TOP_DESC'] )) ? $Meta['ELEMENT_TOP_DESC'] : '',
				"INPUT_NAME" => "META_TEMPLATE[ELEMENT_TOP_DESC]",
				"WIDTH" => "90%",
				"HEIGHT" => "200px",
				"USE_FILE_DIALOGS" => "Y",
				"FLOATING_TOOLBAR" => "Y",
				"ARISING_TOOLBAR" => "Y",
				"RESIZABLE"=>"Y",
				"AUTO_RESIZE"=>"Y"
		) );*/
		?>
	</td>
	<td width="10%" align="left">
		<?=$PropMenu?>
	</td>
</tr>
<?$tabControl->EndCustomField("ELEMENT_TOP_DESC");?>

<?$tabControl->BeginCustomField("ELEMENT_BOTTOM_DESC", GetMessage('SEO_META_EDIT_ELEMENT_BOTTOM_DESC'),false);?>
<tr class="adm-detail-valign-top">
	<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
	<td width="50%">
		<?
		CFileMan::AddHTMLEditorFrame(
				"ELEMENT_BOTTOM_DESC",
				(isset( $Meta['ELEMENT_BOTTOM_DESC'] ) && !empty( $Meta['ELEMENT_BOTTOM_DESC'] )) ? $Meta['ELEMENT_BOTTOM_DESC'] : '',
				"ELEMENT_BOTTOM_DESC_TYPE",
				"html",
				array(
						'height' => 150,
						'width' => '100%'
				),
				"N",
				0,
				"",
				"",
				"ru"//��� ����
		);
		/*
		$APPLICATION->IncludeComponent( "bitrix:fileman.light_editor", ".default", Array(
				"CONTENT" => (isset( $Meta['ELEMENT_BOTTOM_DESC'] ) && !empty( $Meta['ELEMENT_BOTTOM_DESC'] )) ? $Meta['ELEMENT_BOTTOM_DESC'] : '',
				"INPUT_NAME" => "META_TEMPLATE[ELEMENT_BOTTOM_DESC]",
				"WIDTH" => "90%",
				"HEIGHT" => "200px",
				"USE_FILE_DIALOGS" => "Y",
				"FLOATING_TOOLBAR" => "Y",
				"ARISING_TOOLBAR" => "Y",
				"RESIZABLE"=>"Y",
				"AUTO_RESIZE"=>"Y"
		) );*/
		?>
	</td>
	<td width="10%" align="left">
		<?=$PropMenu?>
	</td>
</tr>
<?$tabControl->EndCustomField("ELEMENT_BOTTOM_DESC");?>

<?$tabControl->BeginCustomField("ELEMENT_ADD_DESC", GetMessage('SEO_META_EDIT_ELEMENT_ADD_DESC'),false);?>
<tr class="adm-detail-valign-top">
	<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
	<td width="50%">
		<?
		CFileMan::AddHTMLEditorFrame(
				"ELEMENT_ADD_DESC",
				(isset( $Meta['ELEMENT_ADD_DESC'] ) && !empty( $Meta['ELEMENT_ADD_DESC'] )) ? $Meta['ELEMENT_ADD_DESC'] : '',
				"ELEMENT_ADD_DESC_TYPE",
				"html",
				array(
						'height' => 150,
						'width' => '100%'
				),
				"N",
				0,
				"",
				"",
				"ru"//��� ����
		);
		/*$APPLICATION->IncludeComponent( "bitrix:fileman.light_editor", ".default", Array(
				"CONTENT" => (isset( $Meta['ELEMENT_ADD_DESC'] ) && !empty( $Meta['ELEMENT_ADD_DESC'] )) ? $Meta['ELEMENT_ADD_DESC'] : '',
				"INPUT_NAME" => "META_TEMPLATE[ELEMENT_ADD_DESC]",
				"WIDTH" => "90%",
				"HEIGHT" => "200px",
				"USE_FILE_DIALOGS" => "Y",
				"FLOATING_TOOLBAR" => "Y",
				"ARISING_TOOLBAR" => "Y",
				"RESIZABLE"=>"Y",
				"AUTO_RESIZE"=>"Y"
		) );*/
		?>
	</td>
	<td width="10%" align="left">
		<?=$PropMenu?>
	</td>
</tr>
<?$tabControl->EndCustomField("ELEMENT_ADD_DESC");?>



<?
$tabControl->BeginCustomField("ELEMENT_FILE", GetMessage('SEO_META_EDIT_FILE'),false);
?>
	<tr class="adm-detail-file-row">
		<td><?echo $tabControl->GetCustomLabelHTML()?></td>
		<td><?echo \Bitrix\Main\UI\FileInput::createInstance(array(
            "name" => "ELEMENT_FILE",
            "description" => true,
            "upload" => true,
            "allowUpload" => "I",
            "medialib" => true,
            "fileDialog" => true,
            "cloud" => true,
            "delete" => true,
            "maxCount" => 1
            ))->show(($bVarsFromForm ? $_REQUEST["ELEMENT_FILE"] : $Meta['ELEMENT_FILE']), $bVarsFromForm);?>
        </td>
    </tr>
<?
$tabControl->EndCustomField("ELEMENT_FILE");
?>



<?
$tabControl->BeginCustomField("TAG", GetMessage('SEO_META_EDIT_TAG'),false);?>
	<tr class="adm-detail-valign-top">
		<td width="40%">
			<?echo $tabControl->GetCustomLabelHTML();?>
		</td>
		<td width="50%">
			<textarea style="width: 90%" name="TAG"><?=($condition['TAG']) ? $condition['TAG'] : ''?></textarea>
		</td>
		<td width="10%" align="left">
			<?=$PropMenu?>
		</td>
	</tr>
<?$tabControl->EndCustomField("TAG");


$tabControl->AddCheckBoxField("STRICT_RELINKING", GetMessage("SEO_META_EDIT_STRICT_RELINKING"), false, "Y", ($condition['STRICT_RELINKING'] == "Y"));
$tabControl->BeginCustomField("STRICT_RELINKING_NOTE", "");
?>
<tr>
	<td></td>
	<td>
		<?=BeginNote();?>
		<?=GetMessage("SEO_META_EDIT_STRICT_RELINKING_NOTE")?>
		<?=EndNote();?>
	</td>
</tr>
<?
$tabControl->EndCustomField("STRICT_RELINKING_NOTE");


$tabControl->BeginCustomField("CONDITION_TAG", GetMessage('SEO_META_EDIT_CONDITION_TAG'), false);
$conditions = array();
$rs = \Sotbit\Seometa\ConditionTable::getList(array('select' => array('NAME','ID')));
    while($cond = $rs->fetch())
    {
        $conditions["REFERENCE_ID"][] = $cond['ID'];
        $conditions["REFERENCE"][] = '['.$cond['ID'].'] '.$cond['NAME'];
    }
?>

<tr class="adm-detail-valign-top" id="CONDITION_TAG">
	<td width="40%">
		<?echo $tabControl->GetCustomLabelHTML();?>
	</td>
	<td width="60%">
		<?echo SelectBoxMFromArray('CONDITION_TAG'.'[]', $conditions, is_array($condition['CONDITION_TAG'])?$condition['CONDITION_TAG']:unserialize($condition['CONDITION_TAG']),'',false,'','style="min-width:350px"');?>
		<div class="adm-info-message-wrap">
			<div class="adm-info-message">
				<?echo GetMessage('SEO_META_EDIT_CONDITION_TAG_NOTE');?>
			</div>
		</div>
	</td>
</tr>

<?$tabControl->EndCustomField("CONDITION_TAG");


$tabControl->BeginCustomField("META_NOTE", "");?>
<tr>
	<td colspan="3" align="center">
		<div class="adm-info-message-wrap">
			<div class="adm-info-message" style="text-align:left;">
				<?=GetMessage("SEO_META_EDIT_META_NOTE")?>
			</div>
		</div>
	</td>
</tr>
<?$tabControl->EndCustomField("META_NOTE");


$tabControl->BeginNextFormTab();//URL
define ( 'B_ADMIN_SUBCHPU', 1 );
define ( 'B_ADMIN_SUBCHPU_LIST', false );


$tabControl->BeginCustomField( "TEMPLATE_NEW_URL", '', false );
?>
<tr>
	<td width="30%">
		<?php echo GetMessage('SEO_META_EDIT_TEMPLATE_NEW_URL');?>
	</td>
	<td width="50%">
		<input type="text" name="META_TEMPLATE[TEMPLATE_NEW_URL]" maxlength="255" size="110" value="<?php echo isset($Meta['TEMPLATE_NEW_URL'])&&!empty($Meta['TEMPLATE_NEW_URL'])?$Meta['TEMPLATE_NEW_URL']:''?>">
	</td>
	<td width="10%" align="left">
		<?=$PropMenuTemplate?>
	</td>
</tr>
<tr>
	<td></td>
	<td>
		<?=BeginNote();?>
		<?=GetMessage("SEO_META_NOTE_TEMPLATE_URL")?>
		<?=EndNote();?>
	</td>
</tr>
<?php
$tabControl->EndCustomField("TEMPLATE_NEW_URL");


$tabControl->BeginCustomField( "BUTTON_GENERATE", '', false );
?>
<tr>
	<td></td>
	<td>
		<a class="adm-btn" href="sotbit.seometa_edit.php?ID=<?php echo $ID?>&action=generate_chpu&lang=<?php echo LANG?>"><?php echo GetMessage('SEO_META_GENERATE_CHPU')?></a>
	</td>
</tr>
<tr>
	<td></td>
	<td>
		<?=BeginNote();?>
		<?=GetMessage("SEO_META_NOTE_GENERATE_CHPU")?>
		<?=EndNote();?>
	</td>
</tr>
<?php
$tabControl->EndCustomField("BUTTON_GENERATE");


$tabControl->BeginCustomField("CHPU_LIST", '',false);

?>
<tr id="tr_LISTCHPU">
<td colspan="2">
<?php
require ($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/sotbit.seometa/admin/templates/sub_chpu.php');
?>
</td>
</tr>
<?php
$tabControl->EndCustomField('CHPU_LIST');

$tabControl->BeginNextFormTab();?>


<?$tabControl->BeginCustomField("ELEMENT_VIDEO", GetMessage('SEO_META_EDIT_VIDEO_TEXT'), false);?>
<tr class="heading">
    <td colspan="2">
        <?=$tabControl->GetCustomLabelHTML();?>
    </td>
</tr>
<tr class="adm-detail-valign-top">
    <td colspan="2" align="center">
        <iframe width="800" height="450" src="https://www.youtube.com/embed/videoseries?list=PL2fR59TvIPXfz2rlmgMHMEg7Zshhi7hC3" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
    </td>
</tr>
<?$tabControl->EndCustomField("ELEMENT_VIDEO");?>


<?if(isset($_GET['INFOBLOCK'])&&!empty($_GET['INFOBLOCK'])
 &&isset($_GET['FROM'])&&!empty($_GET['FROM'])
 &&isset($_GET['TYPE_OF_INFOBLOCK'])&&!empty($_GET['TYPE_OF_INFOBLOCK'])
 &&isset($_GET['SECT_FROM'])&&!empty($_GET['SECT_FROM'])){
	$backUrl = "/bitrix/admin/iblock_section_edit.php?IBLOCK_ID=".$_GET['INFOBLOCK']."&type=".$_GET['TYPE_OF_INFOBLOCK']."&ID=".$_GET['FROM']."&find_section_section=".$_GET['SECT_FROM']."&lang=" . LANG;
 } else {
	$backUrl = "/bitrix/admin/sotbit.seometa_list.php?lang=" . LANG;
 }

$arButtonsParams = array(
		"disabled" => $readOnly,
		"back_url" => $backUrl,
);

$tabControl->Buttons( $arButtonsParams );
$tabControl->Show();

$APPLICATION->AddHeadString( "
<link rel='stylesheet' href='//code.jquery.com/ui/1.12.0/themes/smoothness/jquery-ui.css'>
<script src='//code.jquery.com/ui/1.12.0/jquery-ui.js'></script>
<script>

$(document).ready(function() {

	$('.progressbar').each(function(){
		val = $(this).parent().parent().find('textarea').val().length;

		v = (val/$(this).attr('data-max'))*100;
		if(v>100)
			v = 100;
		$(this).progressbar({value: v});

		if(val>0 && val<$(this).attr('data-min')) {
			$(this).find('.ui-progressbar-value').addClass('orange-color-bg');
		} else if(val == 0 || val>$(this).attr('data-max')){
			$(this).find('.ui-progressbar-value').addClass('red-color-bg');
		} else {
			$(this).find('.ui-progressbar-value').addClass('green-color-bg');
		}

	});

	$('.count_symbol_print span').each(function() {
		l = $(this).parent().parent().find('textarea.count_symbol').val().length;
		$(this).html(l);
		if($(this).hasClass('meta_title')){
			limit_min = ".MIN_SEO_TITLE.";
			limit_max = ".MAX_SEO_TITLE.";
		}
		if($(this).hasClass('meta_key')){
			limit_min = ".MIN_SEO_KEY.";
			limit_max = ".MAX_SEO_KEY.";
		}
		if($(this).hasClass('meta_descr')){
			limit_min = ".MIN_SEO_DESCR.";
			limit_max = ".MAX_SEO_DESCR.";
		}
		if(l>0 && l<limit_min){
			$(this).addClass('orange-color');
		} else {
			if(l==0 || l>limit_max){
				$(this).addClass('red-color');
			}
			else{
				$(this).addClass('green-color');
			}
		}
	})

	$('textarea.count_symbol').keyup(function(){
		triggerTextarea($(this));
	});
});

function triggerTextarea(t){
	v = t.parent().find('.count_symbol_print span');
	l = t.val().length;
	v.html(l);

	 if(v.hasClass('meta_title')){
		limit_min = ".MIN_SEO_TITLE.";
		limit_max = ".MAX_SEO_TITLE.";
	 }
	 if(v.hasClass('meta_key')){
		limit_min = ".MIN_SEO_KEY.";
		limit_max = ".MAX_SEO_KEY.";
	 }
	 if(v.hasClass('meta_descr')){
		limit_min = ".MIN_SEO_DESCR.";
		limit_max = ".MAX_SEO_DESCR.";
	 }

	 bar = t.parent().find('.progressbar');
	 vl = (l/bar.attr('data-max'))*100;
	 if(vl>100)
		vl = 100;
	 bar.progressbar({value: vl});

	 if(l>0 && l<limit_min){
		v.removeClass('green-color').removeClass('red-color').addClass('orange-color');
		t.parent().find('.ui-progressbar-value').removeClass('green-color-bg').removeClass('red-color-bg').addClass('orange-color-bg');
	 } else {
		if(l==0 || l>limit_max){
			v.removeClass('green-color').removeClass('orange-color').addClass('red-color');
			t.parent().find('.ui-progressbar-value').removeClass('orange-color-bg').removeClass('green-color-bg').addClass('red-color-bg');
		} else {
			v.removeClass('red-color').removeClass('orange-color').addClass('green-color');
			t.parent().find('.ui-progressbar-value').removeClass('orange-color-bg').removeClass('red-color-bg').addClass('green-color-bg');
		}
	 }

	return true;
}

$(document).on('click','#SotbitSeoMenuButton',function(){
	var NavMenu=$(this).siblings( '.navmenu-v' );
	if(NavMenu.css('display')=='none')
	{
		$('.navmenu-v').css('display','none');
		NavMenu.css('display','block');
		NavMenu.find('ul').css('right',NavMenu.innerWidth());
	}
	else
	{
		$('.navmenu-v').css('display','none');
		NavMenu.css('display','none');
	}
});

$(document).on('click','.navmenu-v li.with-prop ',function(){
	if($(this).data( 'prop' )!== 'undefined')
	{
		if($(this).closest('tr').find('iframe').length>0)
			{
				$(this).closest('tr').find('iframe').contents().find('body').append($(this).data( 'prop' ));
				$(this).closest('tr').find('textarea').insertAtCaret($(this).data( 'prop' ));
			}
		else
			{
				$(this).closest('tr').find('textarea').insertAtCaret($(this).data( 'prop' ));
				$(this).closest('tr').find('input[name=\"META_TEMPLATE[TEMPLATE_NEW_URL]\"]').insertAtCaret($(this).data( 'prop' ));
				if($(this).closest('tr').find('textarea').length > 0)
					triggerTextarea($(this).closest('tr').find('textarea'));
			}

	}
});

//For add in textarea in focus place
jQuery.fn.extend({
	insertAtCaret: function(myValue){
		return this.each(function(i) {
			if (document.selection) {
				// Internet Explorer
				this.focus();
				var sel = document.selection.createRange();
				sel.text = myValue;
				this.focus();
			}
			else if (this.selectionStart || this.selectionStart == '0') {
				//  Firefox and Webkit
				var startPos = this.selectionStart;
				var endPos = this.selectionEnd;
				var scrollTop = this.scrollTop;
				this.value = this.value.substring(0, startPos)+myValue+this.value.substring(endPos,this.value.length);
				this.focus();
				this.selectionStart = startPos + myValue.length;
				this.selectionEnd = startPos + myValue.length;
				this.scrollTop = scrollTop;
			} else {
				this.value += myValue;
				this.focus();
			}
		})
	}
});

//For menu
navHover = function() {
	var lis = document.getElementByClass('navmenu-v').getElementsByTagName('LI');
	for (var i=0; i<lis.length; i++) {
		lis[i].onmouseover=function() {
			this.className+=' iehover';
		}
		lis[i].onmouseout=function() {
			this.className=this.className.replace(new RegExp(' iehover\\b'), '');
		}
	}
}
if (window.attachEvent) window.attachEvent('onload', navHover);
</script>
<style>
.count_symbol_print {
	font-size: 12px;
	color: gray;
	width: 92%;
}
.count_symbol_print span {
	display: inline-block;
	width: 20px;
	float: right;
	text-align: right;
}
.progressbar{
	display: inline-block;
	height: 3px;
	width: 100px;
	float: right;
	margin-top: 4px;
}
.orange-color {
	color: orange;
}
.orange-color-bg {
	background: orange;
}
.green-color {
	color: green;
}
.green-color-bg {
	background: green;
}
.red-color {
	color: red;
}
.red-color-bg {
	background: red;
}
ul.navmenu-v
{
position:absolute;
margin: 0;
border: 0 none;
padding: 0;
list-style: none;
z-index:9999;
display:none;
right:20px;
}
ul.navmenu-v li,
ul.navmenu-v ul {
margin: 0;
border: 0 none;
padding: 0;
list-style: none;
z-index:9999;
}
ul.navmenu-v li:hover
{
	background:#ebf2f4;
}
ul.navmenu-v:after {
clear: both;
display: block;
font: 1px/0px serif;
content: " . ";
height: 0;
visibility: hidden;
}

ul.navmenu-v li {
font-size:13px;
font-weight:normal;
font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
white-space:nowrap;
height:30px;
line-height:27px;
padding-left:21px;
padding-right:21px;
text-shadow:0 1px white;
display: block;
position: relative;
background: #FFF;
color: #303030;
text-decoration: none;
cursor:pointer;
}
ul.navmenu-v,
ul.navmenu-v ul,
ul.navmenu-v ul ul,
ul.navmenu-v ul ul ul {
border:1px solid #d5e1e4;
border-radius:4px;
box-shadow:0 18px 20px rgba(72, 93, 99, 0.3);
background:#FFF;
}


ul.navmenu-v ul,
ul.navmenu-v ul ul,
ul.navmenu-v ul ul ul {
display: none;
position: absolute;
top: 0;
right: 292px;
}


ul.navmenu-v li:hover ul ul,
ul.navmenu-v li:hover ul ul ul,
ul.navmenu-v li.iehover ul ul,
ul.navmenu-v li.iehover ul ul ul {
display: none;
}

ul.navmenu-v li:hover ul,
ul.navmenu-v ul li:hover ul,
ul.navmenu-v ul ul li:hover ul,
ul.navmenu-v li.iehover ul,
ul.navmenu-v ul li.iehover ul,
ul.navmenu-v ul ul li.iehover ul {
display: block;
}
</style>", true );
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>