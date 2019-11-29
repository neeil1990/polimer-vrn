<?
use Sotbit\Seometa\ConditionTable;
use Sotbit\Seometa\SitemapSectionTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type;
use Bitrix\Main\Localization\Loc;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

$id_module='sotbit.seometa';

Loader::includeModule($id_module);
Loc::loadMessages(__FILE__);
IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("sotbit.seometa");
if($POST_RIGHT=="D")
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

//$CCSeoMeta= new CCSeoMeta();
//if(!$CCSeoMeta->getDemo())
//	return false;

$sTableID = "b_sotbit_seometa";
$oSort = new CAdminSorting($sTableID, "ID", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

function CheckFilter()
{
	global $FilterArr, $lAdmin;
	foreach ($FilterArr as $f) global $$f;
	return count($lAdmin->arFilterErrors)==0;
}

//$arrErrors = $DB->RunSqlBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sotbit.seometa/install/db/".strtolower($DB->type)."/update.sql");

$FilterArr = Array(
	"find",
	"find_id",
	"find_name",
	"find_active",
);

$parentID = 0;
if(isset($_REQUEST["parent"]) && $_REQUEST["parent"])
{
	$parentID = $_REQUEST["parent"];
}


if(isset($parentID) && $parentID>0)
	$ParentUrl='&section='.$parentID;
else
	$ParentUrl='';


$lAdmin->InitFilter($FilterArr);
$arFilter=array();

if (CheckFilter())
{    
	if($find!='' && $find_type=='id')
		$arFilter['ID']=$find;
	elseif($find_id!='')
		$arFilter['ID']=$find_id;
	$arFilter['NAME']=$find_name;
	$arFilter['ACTIVE']=$find_active;
	$arFilter["CATEGORY_ID"] = $parentID;


	if(empty($arFilter['ID'])) unset($arFilter['ID']);
	if(empty($arFilter['NAME'])) unset($arFilter['NAME']);
	if(empty($arFilter['ACTIVE'])) unset($arFilter['ACTIVE']);
	if($arFilter['CATEGORY_ID']===null ||$arFilter['CATEGORY_ID']==="") unset($arFilter['CATEGORY_ID']);
}                         

if($lAdmin->EditAction())
{
	foreach($FIELDS as $ID=>$arFields)
	{

		$TYPE = substr($ID, 0, 1);
		$ID = intval(substr($ID,1));


		if(!$lAdmin->IsUpdated($ID))
			continue;

		$ID = IntVal($ID);
		if($ID>0)
		{
			if($TYPE=="P")
			{
				foreach($arFields as $key=>$value)
					$arData[$key]=$value;
				$arData['DATE_CHANGE'] = new Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s');
				$result = ConditionTable::update($ID,$arData);
				if (!$result->isSuccess())
		        {
		            $lAdmin->AddGroupError(GetMessage("SEO_META_SAVE_ERROR")." ".GetMessage("SEO_META_NO_ZAPIS"), $ID);
		        }
			}
			else
			{
				foreach($arFields as $key=>$value)
					$arData[$key]=$value;
					$arData['DATE_CHANGE'] = new Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s');
					$result = SitemapSectionTable::update($ID,$arData);
					if (!$result->isSuccess())
					{
						$lAdmin->AddGroupError(GetMessage("SEO_META_SAVE_ERROR")." ".GetMessage("SEO_META_NO_ZAPIS"), $ID);
					}
			}
		}
		else
		{
			$lAdmin->AddGroupError(GetMessage("SEO_META_SAVE_ERROR")." ".GetMessage("SEO_META_NO_ZAPIS"), $ID);
		}
	}
}

if($arID = $lAdmin->GroupAction())
{
	if($_REQUEST['action_target']=='selected')
	{
		$rsData=ConditionTable::getList(array(
			'select' => array('ID','NAME','SORT','ACTIVE','DATE_CHANGE'),
			'filter' =>$arFilter,
			'order' => array($by => $order),
		));
            while($arRes = $rsData->Fetch())
            {
                $arRes["T"]="S";
                $arRes['ID']="P".$arRes['ID'];
                $arID[] = $arRes['ID'];
            }

		if(!isset($filter))
			$filter=array();

		$rsSection = SitemapSectionTable::getList(array(
				'limit' =>null,
				'offset' => null,
				'select' => array("*"),
				"filter" => $filter
		));
            while($arSection = $rsSection->Fetch())
            {
                $arSection["T"]="S";
                $arSection['ID']="S".$arSection['ID'];
                $arID[]=$arSection;
            }
	}

	foreach($arID as $ID)
	{
		$TYPE = substr($ID, 0, 1);
		$ID = intval(substr($ID, 1));

		if(strlen($ID) <= 0)
			continue;
		$ID = IntVal($ID);

		switch($_REQUEST['action'])
		{
			case "delete":
				if($TYPE=="P")
				{
					$result = ConditionTable::delete($ID);
					if(!$result->isSuccess())
					{
						$lAdmin->AddGroupError(GetMessage("SEO_META_DEL_ERROR")." ".GetMessage("SEO_META_NO_ZAPIS"), $ID);
					}
				}
				else
				{
					$result = SitemapSectionTable::deleteSection($ID);
					if(!$result->isSuccess())
					{
						$lAdmin->AddGroupError(GetMessage("SEO_META_DEL_ERROR")." ".GetMessage("SEO_META_NO_ZAPIS"), $ID);
					}
				}
				break;
			case "activate":
			case "deactivate":
				$arFields["ACTIVE"] = ($_REQUEST['action']=="activate"?"Y":"N");
				if($TYPE=="P")
				{
					$result = ConditionTable::update($ID, array(
						'ACTIVE' => $arFields["ACTIVE"],
					));
					if (!$result->isSuccess())
						$lAdmin->AddGroupError(GetMessage("SEO_META_SAVE_ERROR")." ".GetMessage("SEO_META_NO_ZAPIS"), $ID);
				}
				else
				{
					$result = SitemapSectionTable::update($ID, array(
						'ACTIVE' => $arFields["ACTIVE"],
					));
					if (!$result->isSuccess())
						$lAdmin->AddGroupError(GetMessage("SEO_META_SAVE_ERROR")." ".GetMessage("SEO_META_NO_ZAPIS"), $ID);
				}
				break;
			case "copy":
				if($TYPE=="P")
				{
					$conditionRes=ConditionTable::getById($ID);
					    $condition=$conditionRes->fetch();
					$arFields = Array(
							"ACTIVE"	=> $condition['ACTIVE'],
							"STRONG"	=> $condition['STRONG'],
							"NAME"		=> $condition['NAME'],
							"SORT"		=> $condition['SORT'],
							"SITES"	=> $condition['SITES'],
							//"TYPE_OF_CONDITION"		=> $condition['TYPE_OF_CONDITION'],
							"TYPE_OF_INFOBLOCK"		=> $condition['TYPE_OF_INFOBLOCK'],
							"INFOBLOCK"		=> $condition['INFOBLOCK'],
							"DATE_CHANGE"	=> new Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s'),
							"SECTIONS"=>$condition['SECTIONS'],
							"RULE"=>$condition['RULE'],
							"META"=>$condition['META'],
							"NO_INDEX"	=> $condition['NO_INDEX'],
							"PRIORITY"=>$condition['PRIORITY'],
							"CHANGEFREQ"=>$condition['CHANGEFREQ'],
							"CATEGORY_ID"=>$condition['CATEGORY_ID'],
							"FILTER_TYPE"=>$condition['FILTER_TYPE'],
					);
					$result=ConditionTable::add($arFields);
					if($result->isSuccess())
					{
						$ID = $result->getId();
					}
					else
					{
						$errors = $result->getErrorMessages();
					}
				}
				else
				{
					$conditionRes=SitemapSectionTable::getById($ID);
					    $condition=$conditionRes->fetch();
					$arFields = Array(
							"ACTIVE"	=> $condition['ACTIVE'],
							"NAME"		=> $condition['NAME'],
							"SORT"		=> $condition['SORT'],
							"DESCRIPTION"		=> $condition['DESCRIPTION'],
							"PARENT_CATEGORY_ID"		=> $condition['PARENT_CATEGORY_ID'],
							"DATE_CREATE"	=> new Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s'),
							"DATE_CHANGE"	=> new Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s'),
							);
					$result=SitemapSectionTable::add($arFields);
					if($result->isSuccess())
					{
						$ID = $result->getId();
					}
				}
				break;
		}
	}

	/*if($_REQUEST['action'] == "export_to_excel")
	{
		$spreadsheet = new CSeoMetaExcel();
		$spreadsheet->Write($arID);
	}*/
}

$show = "section";     
if(isset($_REQUEST["show_sp"]) && $_REQUEST["show_sp"]=="all")
{
	unset($arFilter["CATEGORY_ID"]);
	$show = "all";
}elseif(isset($_REQUEST["show_sp"]) && $_REQUEST["show_sp"]=="section")
{
	$show = "section";
	unset($arFilter["CATEGORY_ID"]);
}                    

$filter = $arFilter;                            
if($show=="all" || $show=="section")
{
	if(isset($arFilter["CATEGORY_ID"]))
	{
		$filter["PARENT_CATEGORY_ID"] = $arFilter["CATEGORY_ID"];
		unset($filter["CATEGORY_ID"]);
	}                 
	$rsSection = SitemapSectionTable::getList(array(
			'limit' =>null,
			'offset' => null,
			'select' => array("*"),
			"filter" => $filter
	));

        while($arSection = $rsSection->Fetch())
        {
            $arSection["T"]="S";
            $arResult[]=$arSection;
        }
	unset($rsSection);
}

if(!isset($arFilter['CATEGORY_ID']))
	$arFilter['CATEGORY_ID']=0;

$rsData=ConditionTable::getList(array(
	'select' => array('ID','NAME','SORT','ACTIVE','DATE_CHANGE'),
	'filter' =>$arFilter,
	'order' => array($by => $order),
));
    while($arRes = $rsData->Fetch())
    {
        $arRes["T"]="P";
        $arResult[] = $arRes;
    }

$rs = new CDBResult;
$rs->InitFromArray($arResult);
$rsData = new CAdminResult($rs, $sTableID);
$rsData->NavStart();

$lAdmin->NavText($rsData->GetNavPrint(GetMessage("SEO_META_NAV")));

$lAdmin->AddHeaders(array(
	array(  "id"    =>"ID",
		"content"  =>GetMessage("SEO_META_TABLE_ID"),
		"sort"    =>"ID",
		"align"    =>"right",
		"default"  =>true,
	),
	array(  "id"    =>"NAME",
		"content"  =>GetMessage("SEO_META_TABLE_TITLE"),
		"sort"    =>"NAME",
		"default"  =>true,
	),
	array(  "id"    =>"SORT",
		"content"  =>GetMessage("SEO_META_TABLE_SORT"),
		"sort"    =>"SORT",
		"align"    =>"right",
		"default"  =>true,
	),
	array(  "id"    =>"ACTIVE",
		"content"  =>GetMessage("SEO_META_TABLE_ACTIVE"),
		"sort"    =>"ACTIVE",
		"default"  =>true,
	),
	array(  "id"    =>"DATE_CHANGE",
		"content"  =>GetMessage("SEO_META_TABLE_DATE_CHANGE"),
		"sort"    =>"DATE_CHANGE",
		"default"  =>true,
	),
));
while($arRes = $rsData->NavNext(true, "f_")):
	$row =& $lAdmin->AddRow($f_T.$f_ID, $arRes);

	$row->AddInputField("NAME", array("size"=>20));

	if($f_T=="S")
	{
		$row->AddViewField("NAME", '<a href="sotbit.seometa_list.php?parent='.$f_ID.'&lang='.LANG.'" class="adm-list-table-icon-link" title="'.GetMessage("IBLIST_A_LIST").'"><span class="adm-submenu-item-link-icon adm-list-table-icon iblock-section-icon"></span><span class="adm-list-table-link">'.$f_NAME.'</span></a>');
	}
	else
	{
		$row->AddViewField("NAME", '<a href="sotbit.seometa_edit.php?ID='.$f_ID.'&lang='.LANG.$ParentUrl.'">'.$f_NAME.'</a>');
	}


	$row->AddInputField("SORT", array("size"=>20));

	$row->AddCheckField("ACTIVE");

	$arActions = Array();

if($f_T=='P')
{
	$arActions[] = array(
			"ICON"=>"edit",
			"DEFAULT"=>true,
			"TEXT"=>GetMessage("SEO_META_EDIT"),
			"ACTION"=>$lAdmin->ActionRedirect("sotbit.seometa_edit.php?ID=".$f_ID.$ParentUrl)
	);
}
else
{
	$arActions[] = array(
			"ICON"=>"edit",
			"DEFAULT"=>true,
			"TEXT"=>GetMessage("SEO_META_EDIT"),
			"ACTION"=>$lAdmin->ActionRedirect("sotbit.seometa_section_edit.php?ID=".$f_ID)
	);
}

	$arActions[] = array(
			"ICON"=>"copy",
			"DEFAULT"=>true,
			"TEXT"=>GetMessage("SEO_META_COPY"),
			"ACTION"=>$lAdmin->ActionDoGroup($f_T.$f_ID, "copy",'parent='.$parent)
	);

	if ($POST_RIGHT>="W")
		$arActions[] = array(
			"ICON"=>"delete",
			"TEXT"=>GetMessage("SEO_META_DEL"),
			"ACTION"=>"if(confirm('".GetMessage('SEO_META_DEL_CONF')."')) ".$lAdmin->ActionDoGroup($f_T.$f_ID, "delete")
		);

			$arActions[] = array("SEPARATOR"=>true);
			if(is_set($arActions[count($arActions)-1], "SEPARATOR"))
				unset($arActions[count($arActions)-1]);

	$row->AddActions($arActions);

	endwhile;

$lAdmin->AddFooter(
	array(
		array("title"=>GetMessage("SEO_META_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
		array("counter"=>true, "title"=>GetMessage("SEO_META_LIST_CHECKED"), "value"=>"0"),
	)
);

$lAdmin->AddGroupActionTable(Array(
	"delete" => GetMessage("SEO_META_LIST_DELETE"),
	"copy" => GetMessage("SEO_META_LIST_COPY"),
	"activate" => GetMessage("SEO_META_LIST_ACTIVATE"),
	"deactivate" => GetMessage("SEO_META_LIST_DEACTIVATE"),
	//"export_to_excel" => GetMessage("SEO_META_LIST_EXPORT_TO_EXCEL"),
));

if($parentID>0)
{
	$Section = SitemapSectionTable::getById($parentID)->Fetch();

	$aContext = array(
		array(
			"TEXT"=>GetMessage("SEO_META_POST_ADD_TEXT"),
			"LINK"=>"sotbit.seometa_edit.php?lang=".LANG.$ParentUrl,
			"TITLE"=>GetMessage("SEO_META_POST_ADD_TITLE"),
			"ICON"=>"btn_new",
		),
			array(
					"TEXT"=>GetMessage("SEO_META_SECTION_ADD"),
					"LINK"=>"sotbit.seometa_section_edit.php?parent=".$parentID."&lang=".LANG,
					"TITLE"=>GetMessage("SEO_META_SECTION_ADD"),
					"ICON"=>"btn_sect_new",
			),
			array(
					"TEXT"=>GetMessage("SEO_META_SECTION_UP"),
					"LINK"=>"sotbit.seometa_list.php?parent=".$Section['PARENT_CATEGORY_ID']."&lang=".LANG,
					"TITLE"=>GetMessage("SEO_META_SECTION_UP"),
					"ICON"=>"btn_sect_new",
			),
	);
}
else
{
	$aContext = array(
			array(
					"TEXT"=>GetMessage("SEO_META_POST_ADD_TEXT"),
					"LINK"=>"sotbit.seometa_edit.php?lang=".LANG.$ParentUrl,
					"TITLE"=>GetMessage("SEO_META_POST_ADD_TITLE"),
					"ICON"=>"btn_new",
			),
			array(
					"TEXT"=>GetMessage("SEO_META_SECTION_ADD"),
					"LINK"=>"sotbit.seometa_section_edit.php?parent=".$parentID."&lang=".LANG,
					"TITLE"=>GetMessage("SEO_META_SECTION_ADD"),
					"ICON"=>"btn_sect_new",
			),
	);
}

$lAdmin->AddAdminContextMenu($aContext);


$lAdmin->CheckListMode();


$APPLICATION->SetTitle(GetMessage("SEO_META_TITLE"));


require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");


$oFilter = new CAdminFilter(
	$sTableID."_filter",
	array(
		GetMessage("SEO_META_ID"),
		GetMessage("SEO_META_NAME"),
		GetMessage("SEO_META_ACTIVE"),
	)
);
?>
<form name="find_form" method="get" action="<?echo $APPLICATION->GetCurPage();?>">
	<?$oFilter->Begin();?>
	<tr>
	<td><b><?=GetMessage("SEO_META_FIND")?>:</b></td>
	<td>
	<input type="text" size="25" name="find" value="<?echo htmlspecialchars($find)?>" title="<?=GetMessage("SEO_META_FIND_TITLE")?>">
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
		<td><?=GetMessage("SEO_META_ID")?>:</td>
		<td>
		<input type="text" name="find_id" size="47" value="<?echo htmlspecialchars($find_id)?>">
	</td>
		</tr>
	<tr>
	<td><?=GetMessage("SEO_META_NAME")?>:</td>
	<td>
		<input type="text" name="find_name" size="47" value="<?echo htmlspecialchars($find_name)?>">
	</td>
	</tr>
	<tr>
	<td><?=GetMessage("SEO_META_ACTIVE")?>:</td>
	<td>
	<?
	$arr = array(
		"reference" => array(
			GetMessage("SEO_META_POST_YES"),
			GetMessage("SEO_META_POST_NO"),
		),
		"reference_id" => array(
			"Y",
			"N",
		)
	);
	echo SelectBoxFromArray("find_active", $arr, $find_active, "", "");
	?>
	</td>
	</tr>
	<?
	$oFilter->Buttons(array("table_id"=>$sTableID,"url"=>$APPLICATION->GetCurPage(),"form"=>"find_form"));
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

//if($CCSeoMeta->ReturnDemo()==2)CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SEO_META_DEMO"), 'HTML'=>true));
//if($CCSeoMeta->ReturnDemo()==3)CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SEO_META_DEMO_END"), 'HTML'=>true));

$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>