<?
use Sotbit\Seometa\SeometaUrlTable;
use Sotbit\Seometa\SectionUrlTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type;
use Bitrix\Main\Localization\Loc;


require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

CJSCore::Init(array("jquery"));
$error = '';
$id_module='sotbit.seometa';
Loader::includeModule($id_module);

Loc::loadMessages(__FILE__);
IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("sotbit.seometa");
if($POST_RIGHT=="D")
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

//$CCSeoMeta = new CCSeoMeta();
//if(!$CCSeoMeta->getDemo())
//    return false;

$sTableID = "b_sotbit_seometa_chpu";
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
    "find_in_sitemap",
);

$parentID = 0;
if(isset($_REQUEST["parent"]) && $_REQUEST["parent"])
{
    $parentID = $_REQUEST["parent"];
}

if(isset($parentID) && $parentID>0)
    $ParentUrl="&section=".$parentID;
else
    $ParentUrl='';

$lAdmin->InitFilter($FilterArr);

$arFilter = array();

if(CheckFilter())
{
    if($find != '' && $find_type == 'id')
        $arFilter['ID'] = $find;
    elseif($find_id != '')
        $arFilter['ID'] = $find_id;
    $arFilter['NAME'] = $find_name;
    $arFilter['ACTIVE'] = $find_active;
    $arFilter['IN_SITEMAP'] = $find_in_sitemap;
    $arFilter["CATEGORY_ID"] = $parentID;

    if(empty($arFilter['ID'])) unset($arFilter['ID']);
    if(empty($arFilter['NAME'])) unset($arFilter['NAME']);
    if(empty($arFilter['ACTIVE'])) unset($arFilter['ACTIVE']);
    if(empty($arFilter['IN_SITEMAP'])) unset($arFilter['IN_SITEMAP']);
    if($arFilter['CATEGORY_ID'] === '') unset($arFilter['CATEGORY_ID']);
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
        if($ID > 0)
        {
            if($TYPE == "P")
            {
                foreach($arFields as $key=>$value)
                    $arData[$key]=$value;                                                     
                $result = SeometaUrlTable::update($ID,$arData);
                if (!$result->isSuccess())
                {
                    $lAdmin->AddGroupError(Loc::getMessage("SEO_META_SAVE_ERROR")." ".Loc::getMessage("SEO_META_NO_ZAPIS"), $ID);
                }
            }          
            else
            {
                foreach($arFields as $key=>$value)
                    $arData[$key]=$value;
                $arData['DATE_CHANGE']=new Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s');
                $result = SectionUrlTable::update($ID, $arData);
                if (!$result->isSuccess())
                {
                    $lAdmin->AddGroupError(Loc::getMessage("SEO_META_SAVE_ERROR")." ".Loc::getMessage("SEO_META_NO_ZAPIS"), $ID);
                }
            }
        }
        else
        {
            $lAdmin->AddGroupError(Loc::getMessage("SEO_META_SAVE_ERROR")." ".Loc::getMessage("SEO_META_NO_ZAPIS"), $ID);
        }
    }
}

if($arID = $lAdmin->GroupAction())
{
    if($_REQUEST['action_target'] == 'selected')
    {
        $rsData=SeometaUrlTable::getList(array(
            'select' => array('ID','NAME','ACTIVE','REAL_URL','NEW_URL','DATE_CHANGE'),
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
            $filter = array();

        $rsSection = SectionUrlTable::getList(array(
            'limit' => null,
            'offset' => null,
            'select' => array("*"),
            "filter" => $filter
        ));
        while($arSection = $rsSection->Fetch()) {
            $arSection["T"]="S";
            $arSection['ID']="S".$arSection['ID'];
            $arID[]=$arSection;
        } 
    }

    foreach($arID as $ID)
    {    
        $TYPE = substr($ID, 0, 1);
        $ID = intval(substr($ID,1));

        if(strlen($ID)<=0)
        continue;
        $ID = IntVal($ID);  

        switch($_REQUEST['action'])
        {
            case "delete":
                if($TYPE=="P")
                {
                    $result = SeometaUrlTable::delete($ID);
                    if(!$result->isSuccess())
                    {
                        $lAdmin->AddGroupError(Loc::getMessage("SEO_META_DEL_ERROR")." ".Loc::getMessage("SEO_META_NO_ZAPIS"), $ID);
                    }
                }
                else
                {
                    $result = SectionUrlTable::delete($ID);
                    if(!$result->isSuccess())
                    {
                        $lAdmin->AddGroupError(Loc::getMessage("SEO_META_DEL_ERROR")." ".Loc::getMessage("SEO_META_NO_ZAPIS"), $ID);
                    }
                }
                break;
            case "activate":
            case "deactivate":
                $arFields["ACTIVE"]=($_REQUEST['action']=="activate"?"Y":"N");
                if($TYPE=="P")
                {
                    $result = SeometaUrlTable::update($ID, array(
                        'ACTIVE' => $arFields["ACTIVE"],
                    ));
                    if (!$result->isSuccess())
                        $lAdmin->AddGroupError(Loc::getMessage("SEO_META_SAVE_ERROR")." ".Loc::getMessage("SEO_META_NO_ZAPIS"), $ID);
                }
                else
                {
                    $result = SectionUrlTable::update($ID, array(
                        'ACTIVE' => $arFields["ACTIVE"],
                    ));
                    if (!$result->isSuccess())
                        $lAdmin->AddGroupError(Loc::getMessage("SEO_META_SAVE_ERROR")." ".Loc::getMessage("SEO_META_NO_ZAPIS"), $ID);
                }
                break;
            case "copy": 
                if($TYPE == "P")
                {
                    $conditionRes = SeometaUrlTable::getById($ID);
                    $condition = $conditionRes->fetch();
                    $arFields = Array(
                        "ACTIVE" => $condition['ACTIVE'],
                        "NAME" => $condition['NAME'],
                        "CATEGORY_ID" => $condition['CATEGORY_ID'],
                        "REAL_URL" => $condition['REAL_URL'],
                        "NEW_URL" => $condition['NEW_URL'],
                        'DATE_CHANGE' => new Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s'),
                    );
                    $result = SeometaUrlTable::add($arFields);
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
                    $conditionRes = SectionUrlTable::getById($ID);
                    $condition = $conditionRes->fetch();
                    $arFields = Array(
                        "ACTIVE" => $condition['ACTIVE'],
                        "NAME" => $condition['NAME'],
                        "SORT" => $condition['SORT'],
                        "DESCRIPTION" => $condition['DESCRIPTION'],
                        "PARENT_CATEGORY_ID" => $condition['PARENT_CATEGORY_ID'],
                        "DATE_CREATE" => new Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s'),
                        "DATE_CHANGE" => new Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s'),
                    );
                    $result = SectionUrlTable::add($arFields);
                    if($result->isSuccess()) {
                        $ID = $result->getId();
                    }
                }
            break;
        }
    }
}   

$show = "all";
if(isset($_REQUEST["show_sp"]) && $_REQUEST["show_sp"]=="all")
{
    unset($arFilter["CATEGORY_ID"]);
    $show = "all";
}
elseif(isset($_REQUEST["show_sp"]) && $_REQUEST["show_sp"]=="section")
{
    $show = "section";
    unset($arFilter["CATEGORY_ID"]);
} 

$filter = $arFilter;
if($show == "all" || $show == "section")
{
    if(isset($arFilter["CATEGORY_ID"]))
    {
        $filter["PARENT_CATEGORY_ID"] = $arFilter["CATEGORY_ID"];
        unset($filter["CATEGORY_ID"]);
    }
    if(isset($arFilter["IN_SITEMAP"]))
    {
        unset($filter["IN_SITEMAP"]);
    }

    $rsSection = SectionUrlTable::getList(array(
        'select' => array("*"),
        'limit' => null,
        'offset' => null,
        'filter' => $filter
    ));
        while($arSection = $rsSection->Fetch())
        {
            $arSection["T"] = "S";
            $arResult[] = $arSection;
        }
    unset($rsSection);
}

if(!isset($arFilter['CATEGORY_ID']))
    $arFilter['CATEGORY_ID'] = 0;



$rsData = SeometaUrlTable::getList(array(
    'select' => array('ID', 'NAME', 'CONDITION_ID', 'ACTIVE', 'REAL_URL', 'NEW_URL', 'IN_SITEMAP', 'iblock_id', 'section_id', 'PRODUCT_COUNT', 'DATE_CHANGE', 'PROPERTIES'),
    'filter' => $arFilter,
    'order' => array($by => $order),
));

if(isset($rsData))
    while($arRes = $rsData->Fetch())
    {
        $arRes["T"] = "P";
        $arResult[] = $arRes;
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
        "content" => Loc::getMessage("SEO_META_TABLE_TITLE"),
        "sort" => "NAME",
        "default" => true,
    ),
    array(
        "id" => "CONDITION_ID",
        "content" => Loc::getMessage("SEO_META_TABLE_CONDITION_ID"),
        "sort" => "CONDITION_ID",
        "default" => true,
    ),
    array(
        "id" => "ACTIVE",
        "content" => Loc::getMessage("SEO_META_TABLE_ACTIVE"),
        "sort" => "ACTIVE",
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
        "id" => "IN_SITEMAP",
        "content" => Loc::getMessage("SEO_META_TABLE_IN_SITEMAP"),
        "sort" => "IN_SITEMAP",
        "default" => true,
    ),
    array(
        "id" => "iblock_id",
        "content" => Loc::getMessage("SEO_META_TABLE_IBLOCK_ID"),
        "sort" => "iblock_id",
        "default" => true,
    ),
    array(
        "id" => "section_id",
        "content" => Loc::getMessage("SEO_META_TABLE_SECTION_ID"),
        "sort" => "section_id",
        "default" => true,
    ),
    array(
        "id" => "PRODUCT_COUNT",
        "content" => Loc::getMessage("SEO_META_TABLE_PRODUCT_COUNT"),
        "sort" => "PRODUCT_COUNT",
        "default" => true,
    ),
    array(
        "id" => "DATE_CHANGE",
        "content" => Loc::getMessage("SEO_META_TABLE_DATE_CHANGE"),
        "sort" => "DATE_CHANGE",
        "default" => true,
    ),
    array(
        "id" => "PROPERTIES",
        "content" => Loc::getMessage("SEO_META_TABLE_PROPERTIES"),
        "default" => true,
    ),
));

if(!Loader::includeModule('iblock'))
    CModule::IncludeModule('iblock');

while($arRes = $rsData->NavNext(true, "f_"))
{
    $row =& $lAdmin->AddRow($f_T.$f_ID, $arRes);
    $row->AddInputField("NAME", array("size"=>20));
    $row->AddCheckField("ACTIVE");

    if($f_T == "S")
    {
        $row->AddViewField("NAME", '<a href="sotbit.seometa_chpu_list.php?parent='.$f_ID.'&lang='.LANG.'" class="adm-list-table-icon-link" title="'.Loc::getMessage("IBLIST_A_LIST").'"><span class="adm-submenu-item-link-icon adm-list-table-icon iblock-section-icon"></span><span class="adm-list-table-link">'.$f_NAME.'</span></a>');
    }
    else
    {
        $iblock = CIBlock::GetByID($arRes['iblock_id'])->fetch(); 
        $section = CIBlockSection::GetByID($arRes['section_id'])->fetch();
        
        $props = unserialize($arRes['PROPERTIES']);
        $pr = '';
        if(is_array(($props)))
        {
            foreach ($props as $code => $value)
            {
                $name = CIBlockProperty::GetByID($code)->fetch();
                $pr .= $name['NAME'] . ' - ' . implode(', ', $value) . '; ';
            }
            $row->AddViewField("PROPERTIES", $pr);
        }

        $row->AddViewField("iblock_id", '<a target="_blank" href="iblock_edit.php?type='.$iblock['IBLOCK_TYPE_ID'].'&lang='.LANG.'&ID='.$arRes['iblock_id'].'&admin=Y">'.$iblock['NAME'].'</a>');
        $row->AddViewField("section_id", '<a target="_blank" href="iblock_section_edit.php?IBLOCK_ID='.$arRes['iblock_id'].'&type='.$iblock['IBLOCK_TYPE_ID'].'&ID='.$arRes['section_id'].'&lang='.LANG.'&find_section_section='.$section['IBLOCK_SECTION_ID'].'">'.$section['NAME'].'</a>');
        $row->AddViewField("NAME", '<a href="sotbit.seometa_chpu_edit.php?ID='.$f_ID.'&lang='.LANG.$ParentUrl.'">'.$f_NAME.'</a>');

        $cond = \Sotbit\Seometa\ConditionTable::getById($f_CONDITION_ID)->fetch();
        $row->AddViewField("CONDITION_ID", $cond ? '<a href="/bitrix/admin/sotbit.seometa_edit.php?ID='.$cond['ID'].'&lang='.LANG.'" target="_blank">#'.$cond['ID'].' - '.$cond['NAME'].'</a>' : '');

        $row->AddViewField("IN_SITEMAP", $f_IN_SITEMAP == 'Y' ? Loc::getMessage("SEO_META_POST_YES") : Loc::getMessage("SEO_META_POST_NO"));

        $row->AddViewField("DATE_CHANGE", $arRes['DATE_CHANGE']);
    }

    $arActions = Array();                      
    if($f_T=='P')
    {
        $arActions[] = array(
            "ICON"=>"edit",
            "DEFAULT"=>true,
            "TEXT"=>Loc::getMessage("SEO_META_EDIT"),
            "ACTION"=>$lAdmin->ActionRedirect("sotbit.seometa_chpu_edit.php?ID=".$f_ID.$ParentUrl)
        );
    }
    else
    {
        $arActions[] = array(
            "ICON"=>"edit",
            "DEFAULT"=>true,
            "TEXT"=>Loc::getMessage("SEO_META_EDIT"),
            "ACTION"=>$lAdmin->ActionRedirect("sotbit.seometa_section_chpu_edit.php?ID=".$f_ID)
        );
    }                                          
    $arActions[] = array(
            "ICON"=>"copy",
            "DEFAULT"=>true,
            "TEXT"=>Loc::getMessage("SEO_META_COPY"),
            "ACTION"=>$lAdmin->ActionDoGroup($f_T.$f_ID, "copy",'parent='.$parent)
    );                                         
    if ($POST_RIGHT>="W"){
        $arActions[] = array(
            "ICON"=>"delete",
            "TEXT"=>Loc::getMessage("SEO_META_DEL"),
            "ACTION"=>"if(confirm('".Loc::getMessage('SEO_META_DEL_CONF')."')) ".$lAdmin->ActionDoGroup($f_T.$f_ID, "delete")
        );                
    }                                          
    $arActions[] = array("SEPARATOR"=>true);
    if(is_set($arActions[count($arActions)-1], "SEPARATOR"))
        unset($arActions[count($arActions)-1]);
    $row->AddActions($arActions);
}

$lAdmin->AddFooter(
    array(
        array("title"=>Loc::getMessage("SEO_META_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
        array("counter"=>true, "title"=>Loc::getMessage("SEO_META_LIST_CHECKED"), "value"=>"0"),
    )
);

$lAdmin->AddGroupActionTable(Array(
    "delete"=>Loc::getMessage("SEO_META_LIST_DELETE"),
    "copy"=>Loc::getMessage("SEO_META_LIST_COPY"),
    "activate"=>Loc::getMessage("SEO_META_LIST_ACTIVATE"),
    "deactivate"=>Loc::getMessage("SEO_META_LIST_DEACTIVATE"),
));

if($parentID > 0)
{
    $Section = SectionUrlTable::getById($parentID)->Fetch();
    $aContext = array(
        array(
            "TEXT"=>Loc::getMessage("SEO_META_POST_ADD_TEXT"),
            "LINK"=>"sotbit.seometa_chpu_edit.php?lang=".LANG.$ParentUrl,
            "TITLE"=>Loc::getMessage("SEO_META_POST_ADD_TITLE"),
            "ICON"=>"btn_new",
        ),
        array(
            "TEXT"=>Loc::getMessage("SEO_META_SECTION_ADD"),
            "LINK"=>"sotbit.seometa_section_chpu_edit.php?parent=".$parentID."&lang=".LANG,
            "TITLE"=>Loc::getMessage("SEO_META_SECTION_ADD"),
            "ICON"=>"btn_sect_new",
        ),
        array(
            "TEXT"=>Loc::getMessage("SEO_META_SECTION_UP"),
            "LINK"=>"sotbit.seometa_chpu_list.php?parent=".$Section['PARENT_CATEGORY_ID']."&lang=".LANG,
            "TITLE"=>Loc::getMessage("SEO_META_SECTION_UP"),
            "ICON"=>"btn_sect_new",
        ),
        /*array(
            "TEXT"=>Loc::getMessage("SEO_META_SECTION_UPDATE_COUNT"),
            "LINK"=>"",
            "TITLE"=>Loc::getMessage("SEO_META_SECTION_UPDATE_COUNT"),
            "ICON"=>"btn_update_count",
        ),  */
    );
}
else
{
    $aContext = array(
        array(
            "TEXT" => Loc::getMessage("SEO_META_POST_ADD_TEXT"),
            "LINK" => "sotbit.seometa_chpu_edit.php?lang=".LANG.$ParentUrl,
            "TITLE" => Loc::getMessage("SEO_META_POST_ADD_TITLE"),
            "ICON" => "btn_new",
        ),
        array(
            "TEXT" => Loc::getMessage("SEO_META_SECTION_ADD"),
            "LINK" => "sotbit.seometa_section_chpu_edit.php?parent=".$parentID."&lang=".LANG,
            "TITLE" => Loc::getMessage("SEO_META_SECTION_ADD"),
            "ICON" => "btn_sect_new",
        ),
        /*array(
            "TEXT"=>Loc::getMessage("SEO_META_SECTION_UPDATE_COUNT"),
            "LINK"=>"",
            "TITLE"=>Loc::getMessage("SEO_META_SECTION_UPDATE_COUNT"),
            "ICON"=>"btn_update_count",
        ), */
    );
}

$lAdmin->AddAdminContextMenu($aContext);
$lAdmin->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage("SEO_META_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$oFilter = new CAdminFilter(
    $sTableID."_filter",
    array(
        Loc::getMessage("SEO_META_ID"),
        Loc::getMessage("SEO_META_NAME"),
        Loc::getMessage("SEO_META_ACTIVE"),
        Loc::getMessage("SEO_META_IN_SITEMAP"),
    )
);

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

<form name="find_form" method="get" action="<?=$APPLICATION->GetCurPage();?>">
    <?
        $oFilter->Begin();
    ?>
    <tr>
        <td><b><?=Loc::getMessage("SEO_META_FIND")?>:</b></td>
        <td>
            <input type="text" size="25" name="find" value="<?=htmlspecialchars($find)?>" title="<?=Loc::getMessage("SEO_META_FIND_TITLE")?>">
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
        <td><?=Loc::getMessage("SEO_META_ACTIVE")?>:</td>
        <td>
            <?
                $arr = array(
                    "reference" => array(
                        Loc::getMessage("SEO_META_POST_NO_MATTER"),
                        Loc::getMessage("SEO_META_POST_YES"),
                        Loc::getMessage("SEO_META_POST_NO"),
                    ),
                    "reference_id" => array(
                        "",
                        "Y",
                        "N",
                    )
                );
                echo SelectBoxFromArray("find_active", $arr, $find_active, "", "");
            ?>
        </td>
    </tr>
    <tr>
        <td><?=Loc::getMessage("SEO_META_IN_SITEMAP")?>:</td>
        <td>
            <?
                $arr = array(
                    "reference" => array(
                        Loc::getMessage("SEO_META_POST_NO_MATTER"),
                        Loc::getMessage("SEO_META_POST_YES"),
                        Loc::getMessage("SEO_META_POST_NO"),
                    ),
                    "reference_id" => array(
                        "",
                        "Y",
                        "N",
                    )
                );
                echo SelectBoxFromArray("find_in_sitemap", $arr, $find_in_sitemap, "", "");
            ?>
        </td>
    </tr>
    <?
        $oFilter->Buttons(array("table_id" => $sTableID, "url" => $APPLICATION->GetCurPage(), "form" => "find_form"));
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

//if($CCSeoMeta->ReturnDemo()==2) CAdminMessage::ShowMessage(array("MESSAGE"=>Loc::getMessage("SEO_META_DEMO"), 'HTML'=>true));
//if($CCSeoMeta->ReturnDemo()==3) CAdminMessage::ShowMessage(array("MESSAGE"=>Loc::getMessage("SEO_META_DEMO_END"), 'HTML'=>true));

$lAdmin->DisplayList();

/*?>
<script>
    $('#btn_update_count').click(function(){
        BX.updateProductCount();
    });
    BX.updateProductCount = function()
    {
        BX.ajax.post('/bitrix/admin/sotbit.seometa_chpu_update_count.php', {
            lang:'<?=LANGUAGE_ID?>',
            sessid: BX.bitrix_sessid()
        }, function(data)
        {
            BX.adminPanel.closeWait(BX('sitemap_run_button_' + ID));
            BX('sitemap_progress').innerHTML = data;
        });
    };
</script>
<?*/

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>