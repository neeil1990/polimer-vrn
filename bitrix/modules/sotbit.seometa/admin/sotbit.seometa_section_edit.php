<?
use Sotbit\Seometa\SitemapSectionTable;
use Bitrix\Iblock;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type;
use Bitrix\Main\Localization\Loc;

require_once ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

$id_module = 'sotbit.seometa';
if (!Loader::includeModule( 'iblock' ) || !Loader::includeModule( $id_module ))
	die();

Loc::loadMessages(__FILE__);
//$CCSeoMeta = new CCSeoMeta();
//if (!$CCSeoMeta->getDemo())
//	return false;

$POST_RIGHT = $APPLICATION->GetGroupRight( "sotbit.seometa" );
if ($POST_RIGHT == "D")
	$APPLICATION->AuthForm( GetMessage( "ACCESS_DENIED" ) );

IncludeModuleLangFile( __FILE__ );

$aTabs = array(
		array(
				"DIV" => "edit1",
				"TAB" => GetMessage( "SEO_META_EDIT_TAB_SECTION_TITLE" ),
				"ICON" => "main_user_edit",
				"TITLE" => GetMessage( "SEO_META_EDIT_TAB_SECTION_TITLE" )
		),
);
$tabControl = new CAdminForm( "tabControl", $aTabs );

$parentID = 0;
if(isset($_REQUEST["parent"]) && $_REQUEST["parent"])
{
    $parentID = $_REQUEST["parent"];
}
/*if(isset($_REQUEST["ID"]) && $_REQUEST["ID"])
{
	$parentID = $_REQUEST["ID"];
} */
$message = null;      
// POST
if ($REQUEST_METHOD == "POST" && ($save != "" || $apply != "") && $POST_RIGHT == "W" && check_bitrix_sessid())
{

	$arFields = Array(
			"ACTIVE" => ($ACTIVE != "Y" ? "N" : "Y"),
			"NAME" => $NAME,
			"SORT" => $SORT,
			"DATE_CHANGE" => new Type\DateTime( date( 'Y-m-d H:i:s' ), 'Y-m-d H:i:s' ),
			"DESCRIPTION" => $DESCRIPTION,
			"PARENT_CATEGORY_ID" => $PARENT_CATEGORY_ID,
	);
	if ($ID > 0)
	{
		$result = SitemapSectionTable::update( $ID, $arFields );
		if (!$result->isSuccess())
		{
			$errors = $result->getErrorMessages();
			$res = false;
		}
		else
			$res = true;
	}
	else
	{
		$arFields["DATE_CREATE"] = new Type\DateTime( date( 'Y-m-d H:i:s' ), 'Y-m-d H:i:s' );
		$result = SitemapSectionTable::add( $arFields );
		if ($result->isSuccess())
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
			LocalRedirect( "/bitrix/admin/sotbit.seometa_section_edit.php?ID=" . $ID . "&mess=ok&lang=" . LANG . "&" . $tabControl->ActiveTabParam() );
		else
			LocalRedirect( "/bitrix/admin/sotbit.seometa_list.php?lang=" . LANG );
	}
}


if ($ID > 0)
{
	$Section = SitemapSectionTable::getById($ID)->Fetch();
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
//			"MESSAGE" => GetMessage( "SEO_META_DEMO" ),
//			'HTML' => true
//	) );
//if ($CCSeoMeta->ReturnDemo() == 3)
//	CAdminMessage::ShowMessage( array(
//			"MESSAGE" => GetMessage( "SEO_META_DEMO_END" ),
//			'HTML' => true
//	) );

$aMenu = array(
		array(
				"TEXT" => GetMessage( "SEO_META_EDIT_LIST" ),
				"TITLE" => GetMessage( "SEO_META_EDIT_LIST_TITLE" ),
				"LINK" => "sotbit.seometa_list.php?lang=" . LANG,
				"ICON" => "btn_list"
		)
);
if ($parentID > 0)
{
	$aMenu[] = array(
			"SEPARATOR" => "Y"
	);
	$aMenu[] = array(
			"TEXT" => GetMessage( "SEO_META_EDIT_ADD" ),
			"TITLE" => GetMessage( "SEO_META_EDIT_ADD_TITLE" ),
			"LINK" => "sotbit.seometa_section_edit.php?&lang=" . LANG,
			"ICON" => "btn_new"
	);
	$aMenu[] = array(
			"TEXT" => GetMessage( "SEO_META_EDIT_DEL" ),
			"TITLE" => GetMessage( "SEO_META_EDIT_DEL_TITLE" ),
			"LINK" => "javascript:if(confirm('" . GetMessage( "SEO_META_EDIT_DEL_CONF" ) . "'))window.location='sotbit.seometa_list.php?ID=S" . $parentID . "&action=delete&lang=" . LANG . "&" . bitrix_sessid_get() . "';",
			"ICON" => "btn_delete"
	);
}
$context = new CAdminContextMenu( $aMenu );
$context->Show();
?>

<?
if(isset($errors) && is_array($errors) && count($errors)>0)
{
	CAdminMessage::ShowMessage( array(
			"MESSAGE" => $errors[0]
	) );
}?>


<?
if ($_REQUEST["mess"] == "ok" && $ID > 0)
	CAdminMessage::ShowMessage( array(
			"MESSAGE" => GetMessage( "SEO_META_EDIT_SAVED" ),
			"TYPE" => "OK"
	) );

	// Calculate start values

	//***All section***
	$AllSections['REFERENCE_ID'][0]=0;
	$AllSections['REFERENCE'][0]=GetMessage( "SEO_META_CHECK_CATEGORY" );
	$RsAllSections = SitemapSectionTable::getList(array(
			'select' => array('*'),
			'filter' =>array(),
			'order' => array('SORT' => 'ASC')
	));
	while($AllSection=$RsAllSections->Fetch())
    {
        $AllSections['REFERENCE_ID'][]=$AllSection['ID'];
        $AllSections['REFERENCE'][]=$AllSection['NAME'];
    }

	//
$tabControl->Begin( array(
		"FORM_ACTION" => $APPLICATION->GetCurPage()
) );

$tabControl->BeginNextFormTab();

$tabControl->AddViewField( 'ID', GetMessage( "SEO_META_EDIT_ID" ), $ID, false ); // ID
$tabControl->AddCheckBoxField( "ACTIVE", GetMessage( "SEO_META_EDIT_ACT" ), false, "Y", ($Section['ACTIVE'] == "Y" || !isset( $Section['ACTIVE'] )) ); // ����������
$tabControl->AddEditField( "SORT", GetMessage( "SEO_META_EDIT_SORT" ), true, array(
		"size" => 6,
		"maxlength" => 255
), htmlspecialcharsbx( isset( $Section['SORT'] ) && !empty( $Section['SORT'] ) ) ? $Section['SORT'] : 100 );

$tabControl->AddEditField( "NAME", GetMessage( "SEO_META_EDIT_NAME" ), true, array(
		"size" => 50,
		"maxlength" => 255
), htmlspecialcharsbx( $Section['NAME'] ) );

$tabControl->BeginCustomField( "PARENT_CATEGORY_ID", GetMessage( 'SEO_META_EDIT_PARENT_CATEGORY_ID' ), false );


?>
<tr id="PARENT_CATEGORY_ID">
	<td width="40%"><? echo $tabControl->GetCustomLabelHTML(); ?></td>
	<td width="60%">
<?echo SelectBoxFromArray('PARENT_CATEGORY_ID', $AllSections, $Section['PARENT_CATEGORY_ID']?:$parentID,'',false,'','style="min-width:350px"');?>
</td>
</tr><?
$tabControl->EndCustomField( "PARENT_CATEGORY_ID" );

$tabControl->AddTextField( 'DESCRIPTION', GetMessage( "SEO_META_EDIT_DESCRIPTION" ), $Section['DESCRIPTION'], false );

$tabControl->AddViewField( 'DATE_CREATE_TEXT', GetMessage( "SEO_META_EDIT_DATE_CREATE" ), $Section['DATE_CREATE'], false );

$tabControl->AddViewField( 'DATE_CHANGE_TEXT', GetMessage( "SEO_META_EDIT_DATE_CHANGE" ), $Section['DATE_CHANGE'], false );

$tabControl->BeginCustomField( "SECTION_NOTE", GetMessage( 'SEO_META_EDIT_SECTION_NOTE' ), false );

$tabControl->EndCustomField( "SECTION_NOTE" );

$tabControl->BeginCustomField( "HID", '', false );
?>
<?echo bitrix_sessid_post();?>
<input type="hidden" name="lang" value="<?=LANG?>">
<?if($ID>0 && !$bCopy):?>
<input type="hidden" name="ID" value="<?=$ID?>">
<?endif;?>
<?


$tabControl->EndCustomField( "HID" );

$arButtonsParams = array(
		"disabled" => $readOnly,
		"back_url" => "/bitrix/admin/sotbit.seometa_list.php?lang=" . LANG
);

$tabControl->Buttons( $arButtonsParams );
$tabControl->Show();
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>