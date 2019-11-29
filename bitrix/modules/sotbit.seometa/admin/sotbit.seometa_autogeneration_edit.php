<? 
use Sotbit\Seometa\AutogenerationTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;

// подключим все необходимые файлы:
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог

// подключим языковой файл
Loc::loadMessages(__FILE__);

CJSCore::Init(array('jquery'));

// получим права доступа текущего пользователя на модуль
$POST_RIGHT = $APPLICATION->GetGroupRight("sotbit.seometa");
// если нет прав - отправим к форме авторизации сообщение об ошибке
if($POST_RIGHT == "D")
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

// сформируем список закладок
$aTabs = array(
    array(
        "DIV" => "edit1",
        "TAB" => Loc::getMessage("SEOMETA_AUTOGENERATION_TAB1"),
        "ICON" => "main_user_edit",
        "TITLE" => Loc::getMessage("SEOMETA_AUTOGENERATION_TAB1_TITLE")
    ),
    array(
        "DIV" => "edit2",
        "TAB" => Loc::getMessage("SEOMETA_AUTOGENERATION_TAB2"),
        "ICON" => "main_user_edit",
        "TITLE" => Loc::getMessage("SEOMETA_AUTOGENERATION_TAB2_TITLE")
    ),
    array(
        "DIV" => "edit3",
        "TAB" => Loc::getMessage("SEOMETA_AUTOGENERATION_TAB3"),
        "ICON" => "main_user_edit",
        "TITLE" => Loc::getMessage("SEOMETA_AUTOGENERATION_TAB3_TITLE")
    ),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

$ID = intval($ID); // идентификатор редактируемой записи
$logicAND = Loc::getMessage("SEOMETA_LOGIC_AND");
$logicOR = Loc::getMessage("SEOMETA_LOGIC_OR");
$firstBlock = Loc::getMessage("SEOMETA_FIRST_BLOCK");
$nBlock = Loc::getMessage("SEOMETA_N_BLOCK");



// ******************************************************************** //
//                ВЫБОРКА И ПОДГОТОВКА ДАННЫХ ФОРМЫ                     //
// ******************************************************************** //

// выборка данных
if($ID > 0)
{
    $result = AutogenerationTable::getById($ID);
    $condition = $result->fetch();
}

// дополнительная подготовка данных
$findKeys = array();
foreach($_REQUEST as $key => $value)
{
    if(preg_match("/^BLOCK_WITH_PROPS_\d+$/", $key))
    {
        $findKeys[$key] = $value;
    }
}

if($condition["META"])
    $meta = unserialize($condition["META"]);


//printr($_REQUEST);

//printr($condition);


if(isset($_REQUEST["NAME"]))
    $condition["NAME"] = $_REQUEST["NAME"];
if(isset($_REQUEST["SITES"]))
    $condition["SITES"] = $_REQUEST["SITES"];
if(isset($_REQUEST["TYPE_OF_INFOBLOCK"]))
    $condition["TYPE_OF_INFOBLOCK"] = $_REQUEST["TYPE_OF_INFOBLOCK"];
if(isset($_REQUEST["INFOBLOCK"]))
    $condition["INFOBLOCK"] = $_REQUEST["INFOBLOCK"];
if(isset($_REQUEST["SECTIONS"]))
    $condition["SECTIONS"] = $_REQUEST["SECTIONS"];

if($findKeys)
    $condition["RULE"] = $findKeys;

if(isset($_REQUEST["LOGIC"]))
    $condition["LOGIC"] = $_REQUEST["LOGIC"];
if(isset($_REQUEST["FILTER_TYPE"]))
    $condition["FILTER_TYPE"] = $_REQUEST["FILTER_TYPE"];
if(isset($_REQUEST["NAME_TEMPLATE"]))
    $condition["NAME_TEMPLATE"] = $_REQUEST["NAME_TEMPLATE"];
if(isset($_REQUEST["ACTIVE"]))
    $condition["ACTIVE"] = $_REQUEST["ACTIVE"];
if(isset($_REQUEST["SEARCH"]))
    $condition["SEARCH"] = $_REQUEST["SEARCH"];
if(isset($_REQUEST["NO_INDEX"]))
    $condition["NO_INDEX"] = $_REQUEST["NO_INDEX"];
if(isset($_REQUEST["STRICT"]))
    $condition["STRICT"] = $_REQUEST["STRICT"];
if(isset($_REQUEST["CATEGORY"]))
    $condition["CATEGORY"] = $_REQUEST["CATEGORY"];
if(isset($_REQUEST["META"]))
    $meta = $_REQUEST["META"];
if(isset($_REQUEST["TAGS"]))
    $condition["TAGS"] = $_REQUEST["TAGS"];
if(isset($_REQUEST["NEW_URL_TEMPLATE"]))
    $condition["NEW_URL_TEMPLATE"] = $_REQUEST["NEW_URL_TEMPLATE"];
if(isset($_REQUEST["GENERATE_CHPU"]))
    $condition["GENERATE_CHPU"] = $_REQUEST["GENERATE_CHPU"];



$propMenuNewUrlTemplate = CCSeoMeta::PropMenuTemplate($condition['INFOBLOCK']);



/* --------------------------------------------------------------------------------------------------------------- */

$ag = new CSeoMetaAutogenerator();

$arrFilterTypes = array();
$filterTypes = array(
	'default' => Loc::getMessage("SEOMETA_FILTER_DEFAULT"),
	'bitrix_chpu' => Loc::getMessage("SEOMETA_FILTER_BITRIX_CHPU"),
	'bitrix_not_chpu' => Loc::getMessage("SEOMETA_FILTER_BITRIX_NOT_CHPU"),
	'misshop_chpu' => Loc::getMessage("SEOMETA_FILTER_MISSSHOP_CHPU"),
	'combox_chpu' => Loc::getMessage("SEOMETA_FILTER_COMBOX_CHPU"),
	'combox_not_chpu' => Loc::getMessage("SEOMETA_FILTER_COMBOX_NOT_CHPU")
);
foreach($filterTypes as $id => $name)
{
    $arrFilterTypes["REFERENCE"][] = $name;
    $arrFilterTypes["REFERENCE_ID"][] = $id;
}

$arrCategoriesList = array();
$arrCategoriesList['REFERENCE'][] = Loc::getMessage("SEOMETA_SELECT_CATEGORY");
$arrCategoriesList['REFERENCE_ID'][] = 0;
$categoriesList = \Sotbit\Seometa\SitemapSectionTable::getList(array(
    'select' => array('ID', 'NAME'),
    'filter' => array('ACTIVE' => 'Y'),
    'order' => array('SORT' => 'ASC')
));
if(isset($categoriesList))
    while($category = $categoriesList->Fetch())
    {
        $arrCategoriesList['REFERENCE'][] = $category['NAME'];
        $arrCategoriesList['REFERENCE_ID'][] = $category['ID'];
    }

if($condition["TYPE_OF_INFOBLOCK"])
{
    $arrIBlockList = $ag->getIBlocks($condition["TYPE_OF_INFOBLOCK"]);
}

if($condition["INFOBLOCK"])
{
    $arrSectionsList = $ag->getSections($condition["INFOBLOCK"]);
}

$delPropertyBtn = '<span class="property_delete" title="'.Loc::getMessage("SEOMETA_DELETE_PROPERTY").'" onclick="delete_property()"></span>';
$addPropertyBtn = '<input type="button" value="'.Loc::getMessage("SEOMETA_ADD_PROPERTY").'" onclick="add_property(event)">';
$addBlockBtns = '<span class="button" onclick="add_block_with_properties()">'.Loc::getMessage("SEOMETA_ADD_BLOCK").'</span><span class="button" onclick="delete_block_with_properties()" style="display: none;">'.Loc::getMessage("SEOMETA_DELETE_BLOCK").'</span>';

$filter = array();

if(isset($_POST['TYPE_OF_INFOBLOCK']))
    $filter['IBLOCK_TYPE_ID'] = $_POST['TYPE_OF_INFOBLOCK'];

if(isset($_POST['INFOBLOCK']) && isset($_POST['refresh_iblock_id']))
    $filter['ID'] = $_POST['INFOBLOCK'];

$arrProps = $ag->getAllProps($filter);

$selectHTML = '<select>';
if($arrProps)
{
    foreach($arrProps as $iblockName => $props)
    {
        $selectHTML .= '<optgroup label="' . $iblockName . '">';
        foreach($props as $key => $value)
        {
            $selectHTML .= '<option value="' . $key . '">' . $value . '</option>';
        }
        $selectHTML .= '</optgroup>';
    }
}
$selectHTML .= '</select>';
$selectHTML .= $delPropertyBtn;



if($condition["RULE"])
{
    if(!is_array($condition["RULE"]))
        $condition["RULE"] = unserialize($condition["RULE"]);
    //printr($condition["RULE"]);
    $logic = false;
    $logicValue = $condition["LOGIC"];
    if($logicValue == "AND")
    {
        $logicClass = "condition_logic_and";
        $logicText = $logicAND;
    }
    else
    {
        $logicClass = "condition_logic_or";
        $logicText = $logicOR;
    }

    $num = 1;
    $blocks = '<div id="blocks">';
    foreach($condition["RULE"] as $key => $value)
    {
        $blocks .= '<div class="block_with_properties"><span class="block_name">' . $nBlock . $num . '</span><div>';
        foreach($value as $item)
        {
            $blocks .= '<div class="block_item">';

            // формируем select
            $selectForDB = '<select name="'. $key .'[]">';
            if($arrProps)
            {
                foreach($arrProps as $iblockName => $props)
                {
                    $selectForDB .= '<optgroup label="' . $iblockName . '">';
                    foreach($props as $k => $v)
                    {
                        $selectForDB .= '<option value="' . $k . '"' . (($k == $item) ? ' selected' : '') . '>' . $v . '</option>';
                    }
                    $selectForDB .= '</optgroup>';
                }
            }
            $selectForDB .= '</select>';
            $selectForDB .= $delPropertyBtn;

            $blocks .= $selectForDB;
            $blocks .= '</div>';
        }
        $blocks .= '</div>';
        $blocks .= $addPropertyBtn;
        if($logic)
        {
            $blocks .= '<div class="condition_logic '.$logicClass.'">'.$logicText.'</div>';
        }
        $blocks .= '</div>';

        $logic = true;
        $num++;
    }
    $blocks .= '</div>';
    $blocks .= $addBlockBtns;
}
else
{
    $blocks = '<div id="blocks"><div class="block_with_properties"><span class="block_name">' . $firstBlock . '</span><div></div>';
    $blocks .= $addPropertyBtn;
    $blocks .= '</div></div>';
    $blocks .= $addBlockBtns;
}

/* --------------------------------------------------------------------------------------------------------------- */



// ******************************************************************** //
//                ОБРАБОТКА ИЗМЕНЕНИЙ ФОРМЫ                             //
// ******************************************************************** //

// проверка метода вызова страницы && проверка нажатия кнопок "Сохранить" и "Применить" && проверка наличия прав на запись для модуля && проверка идентификатора сессии
if($REQUEST_METHOD == "POST" && ($save != "" || $apply != "") && $POST_RIGHT == "W" && check_bitrix_sessid())
{
    // обработка данных формы
    $arFields = Array(
        "NAME" => $NAME,
        "SITES" => serialize($SITES),
        "TYPE_OF_INFOBLOCK" => $TYPE_OF_INFOBLOCK,
        "INFOBLOCK" => $INFOBLOCK,
        "SECTIONS" => serialize($SECTIONS),
        "RULE" => $findKeys ? serialize($findKeys) : "",
        "LOGIC" => $LOGIC,
        "FILTER_TYPE" => $FILTER_TYPE,
        "NAME_TEMPLATE" => $NAME_TEMPLATE,
        "ACTIVE" => ($ACTIVE != "Y" ? "N" : "Y"),
        "SEARCH" => ($SEARCH != "Y" ? "N" : "Y"),
        "NO_INDEX" => ($NO_INDEX != "Y" ? "N" : "Y"),
        "STRICT" => ($STRICT != "Y" ? "N" : "Y"),
        "CATEGORY" => $CATEGORY,
        "META" => serialize($META),
        "TAGS" => $TAGS,
        "NEW_URL_TEMPLATE" => $NEW_URL_TEMPLATE,
        "GENERATE_CHPU" => ($GENERATE_CHPU != "Y" ? "N" : "Y"),
        "DATE_CHANGE" => new \Bitrix\Main\Type\DateTime()
    );

    // сохранение данных
    if($ID > 0)
    {
        $result = AutogenerationTable::update($ID, $arFields);
        if($result->isSuccess())
        {
            $res = true;
        }
        else
        {
            $errors = $result->getErrorMessages();
            $res = false;
        }
    }
    else
    {
        $result = AutogenerationTable::add($arFields);
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

    if($res)
    {
        // если сохранение прошло удачно - перенаправим на новую страницу
        // (в целях защиты от повторной отправки формы нажатием кнопки "Обновить" в браузере)
        if($apply != "")
            // если была нажата кнопка "Применить" - отправляем обратно на форму
            LocalRedirect("/bitrix/admin/sotbit.seometa_autogeneration_edit.php?ID=".$ID."&mess=ok&lang=".LANG."&".$tabControl->ActiveTabParam());
        else
            // если была нажата кнопка "Сохранить" - отправляем к списку элементов
            LocalRedirect("/bitrix/admin/sotbit.seometa_autogeneration_list.php?lang=".LANG);
    }
}

if($_REQUEST['action'] == "start")
{
    $done = false;
    if($condition['RULE'])
    {
        $ag->startGeneration($condition);
        $done = true;
    }
    else
        $errors[] = Loc::getMessage("SEOMETA_EMPTY_RULE");
}



// ******************************************************************** //
//                ВЫВОД ФОРМЫ                                           //
// ******************************************************************** //

// установим заголовок страницы
$APPLICATION->SetTitle(($ID > 0 ? Loc::getMessage("SEOMETA_AUTOGENERATION_EDIT_TITLE").$ID : Loc::getMessage("SEOMETA_AUTOGENERATION_ADD_TITLE")));

// не забудем разделить подготовку данных и вывод
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");


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

// конфигурация административного меню
$aMenu = array(
    array(
        "TEXT" => Loc::getMessage("SEOMETA_AUTOGENERATION_BACK"),
        "TITLE" => Loc::getMessage("SEOMETA_AUTOGENERATION_TITLE"),
        "LINK" => "sotbit.seometa_autogeneration_list.php?lang=".LANG,
        "ICON" => "btn_list",
    )
);

if($ID > 0)
{
    $aMenu[] = array("SEPARATOR" => "Y");
    $aMenu[] = array(
        "TEXT" => Loc::getMessage("SEOMETA_AUTOGENERATION_ADD"),
        "TITLE" => Loc::getMessage("SEOMETA_AUTOGENERATION_ADD"),
        "LINK" => "sotbit.seometa_autogeneration_edit.php?lang=".LANG,
        "ICON" => "btn_new",
    );
    $aMenu[] = array(
        "TEXT" => Loc::getMessage("SEOMETA_AUTOGENERATION_DELETE"),
        "TITLE" => Loc::getMessage("SEOMETA_AUTOGENERATION_DELETE"),
        "LINK" => "javascript:if(confirm('".Loc::getMessage("SEOMETA_AUTOGENERATION_DELETE_CONFIRM")."'))window.location='sotbit.seometa_autogeneration_list.php?ID=".$ID."&action=delete&lang=".LANG."&".bitrix_sessid_get()."';",
        "ICON" => "btn_delete",
    );
    $aMenu[] = array("SEPARATOR" => "Y");
    $aMenu[] = array(
        "TEXT" => Loc::getMessage("SEOMETA_AUTOGENERATION_START"),
        "TITLE" => Loc::getMessage("SEOMETA_AUTOGENERATION_START"),
        "LINK" => "sotbit.seometa_autogeneration_edit.php?lang=".LANG."&ID=".$ID."&action=start",
        "LINK_PARAM" => "class=\"adm-btn adm-btn-green\""
    );
}

// создание экземпляра класса административного меню
$context = new CAdminContextMenu($aMenu);

// вывод административного меню
$context->Show();

// если есть сообщения об ошибках или об успешном сохранении - выведем их
if(isset($errors))
    CAdminMessage::ShowMessage(array(
        "MESSAGE" => $errors[0]
    ));

if($_REQUEST["mess"] == "ok" && $ID > 0)
    CAdminMessage::ShowMessage(array(
        "MESSAGE" => Loc::getMessage("SEOMETA_AUTOGENERATION_SAVED"),
        "TYPE" => "OK"
    ));

if($done)
    CAdminMessage::ShowMessage(array(
        "MESSAGE" => Loc::getMessage("SEOMETA_DONE"),
        "TYPE" => "OK"
    ));



/* кнопка для шаблонизатора названия условия */
$arItemsName = array(
    array(
        "TEXT" => Loc::getMessage("MENU_NAME_TEMPLATE_SECTION_ID"),
        "ONCLICK" => "__SetUrlVar('#SECTION_ID#', 'menu_NAME_TEMPLATE', 'NAME_TEMPLATE')",
    ),
    array(
        "TEXT" => Loc::getMessage("MENU_NAME_TEMPLATE_SECTION_NAME"),
        "ONCLICK" => "__SetUrlVar('#SECTION_NAME#', 'menu_NAME_TEMPLATE', 'NAME_TEMPLATE')",
    ),
    array("SEPARATOR" => true),
    array(
        "TEXT" => Loc::getMessage("MENU_NAME_TEMPLATE_PROPERTY_ID"),
        "ONCLICK" => "__SetUrlVar('#PROPERTY_ID#', 'menu_NAME_TEMPLATE', 'NAME_TEMPLATE')",
    ),
    array(
        "TEXT" => Loc::getMessage("MENU_NAME_TEMPLATE_PROPERTY_NAME"),
        "ONCLICK" => "__SetUrlVar('#PROPERTY_NAME#', 'menu_NAME_TEMPLATE', 'NAME_TEMPLATE')",
    ),
);
$u = new CAdminPopupEx("menu_NAME_TEMPLATE", $arItemsName, false);
$u->Show();



/* кнопки для шаблонизатора метаданных */
$numberOfBlocks = 0;
if($condition["RULE"])
{
    $numberOfBlocks = count($condition["RULE"]);
}

$u = new CAdminPopupEx(
    "menu_META_TITLE",
    $ag->getItemsForMetaMenu($condition['INFOBLOCK'], $numberOfBlocks, '__SetUrlVar', 'menu_META_TITLE', 'META[ELEMENT_TITLE]'),
    false
);
$u->Show();

$u = new CAdminPopupEx(
    "menu_META_KEYWORDS",
    $ag->getItemsForMetaMenu($condition['INFOBLOCK'], $numberOfBlocks, '__SetUrlVar', 'menu_META_KEYWORDS', 'META[ELEMENT_KEYWORDS]'),
    false
);
$u->Show();

$u = new CAdminPopupEx(
    "menu_META_DESCRIPTION",
    $ag->getItemsForMetaMenu($condition['INFOBLOCK'], $numberOfBlocks, '__SetUrlVar', 'menu_META_DESCRIPTION', 'META[ELEMENT_DESCRIPTION]'),
    false
);
$u->Show();

$u = new CAdminPopupEx(
    "menu_SECTION_HEADER",
    $ag->getItemsForMetaMenu($condition['INFOBLOCK'], $numberOfBlocks, '__SetUrlVar', 'menu_SECTION_HEADER', 'META[ELEMENT_PAGE_TITLE]'),
    false
);
$u->Show();

$u = new CAdminPopupEx(
    "menu_BREADCRUMBS_TITLE",
    $ag->getItemsForMetaMenu($condition['INFOBLOCK'], $numberOfBlocks, '__SetUrlVar', 'menu_BREADCRUMBS_TITLE', 'META[ELEMENT_BREADCRUMB_TITLE]'),
    false
);
$u->Show();

$u = new CAdminPopupEx(
    "menu_TAGS",
    $ag->getItemsForMetaMenu($condition['INFOBLOCK'], $numberOfBlocks, '__SetUrlVar', 'menu_TAGS', 'TAGS'),
    false
);
$u->Show();
?>

<script>
	function __SetUrlVar(id, mnu_id, el_id)
	{
		var obj_ta = BX(el_id);
		//IE
		if (document.selection)
		{
			obj_ta.focus();
			var sel = document.selection.createRange();
			sel.text = id;
			//var range = obj_ta.createTextRange();
			//range.move('character', caretPos);
			//range.select();
		}
		//FF
		else if (obj_ta.selectionStart || obj_ta.selectionStart == '0')
		{
			var startPos = obj_ta.selectionStart;
			var endPos = obj_ta.selectionEnd;
			var caretPos = startPos + id.length;
			obj_ta.value = obj_ta.value.substring(0, startPos) + id + obj_ta.value.substring(endPos, obj_ta.value.length);
			obj_ta.setSelectionRange(caretPos, caretPos);
			obj_ta.focus();
		}
		else
		{
			obj_ta.value += id;
			obj_ta.focus();
		}

		BX.fireEvent(obj_ta, 'change');
		obj_ta.focus();
	}
</script>



<?
// далее выводим собственно форму
?>
<form method="POST" action="<?=$APPLICATION->GetCurPage()?>" enctype="multipart/form-data" name="post_form">
    <?
    // проверка идентификатора сессии
    echo bitrix_sessid_post();
    // отобразим заголовки закладок
    $tabControl->Begin();
    //********************
    // первая закладка - Условия
    //********************
    $tabControl->BeginNextTab();
    ?>



    <tr class="heading">
        <td colspan="2"><?=Loc::getMessage("SEOMETA_AUTOGENERATION_TAB1_MAIN_OPTIONS")?></td>
    </tr>

    <?if($ID > 0):?>
        <tr>
            <td width="40%"><?="ID:"?></td>
            <td width="60%">
                <span><?=$ID?></span>
                <input type="hidden" name="ID" value="<?=$ID?>">
            </td>
        </tr>
    <?endif;?>

    <tr>
        <td width="40%"><span class="required">* </span><?=Loc::getMessage("SEOMETA_FIELD_NAME")?></td>
        <td width="60%"><input type="text" name="NAME" value="<?=$condition['NAME']?>" size="44" maxlength="255"></td>
    </tr>

    <tr>
        <td><?=Loc::getMessage("SEOMETA_FIELD_SITES")?></td>
        <td>
            <?=CSite::SelectBoxMulti("SITES", !isset($condition['SITES']) ? $ag->getSites() : (is_array($condition['SITES']) ? $condition['SITES'] : unserialize($condition['SITES'])));?>
        </td>
    </tr>

    <tr>
        <td><?=Loc::getMessage("SEOMETA_FIELD_TYPE_OF_INFOBLOCK")?></td>
        <td>
            <?=SelectBoxFromArray('TYPE_OF_INFOBLOCK', $ag->getIBlockTypes(), $condition['TYPE_OF_INFOBLOCK'], '', 'style="min-width: 350px; margin-right: 5px;"', false, '');?>
            <input type="submit" name="refresh_iblock_type" value="OK">
        </td>
    </tr>

    <tr>
        <td><?=Loc::getMessage("SEOMETA_FIELD_INFOBLOCK")?></td>
        <td>
            <?=SelectBoxFromArray('INFOBLOCK', $arrIBlockList, $condition['INFOBLOCK'], '', 'style="min-width: 350px; margin-right: 5px;"', false, '');?>
            <input type="submit" name="refresh_iblock_id" value="OK">
        </td>
	</tr>

    <tr>
        <td><?=Loc::getMessage("SEOMETA_FIELD_SECTIONS")?></td>
        <td>
            <?=SelectBoxMFromArray('SECTIONS[]', $arrSectionsList ? $arrSectionsList : array(), is_array($condition['SECTIONS']) ? $condition['SECTIONS'] : unserialize($condition['SECTIONS']), '', false, '', 'style="min-width: 350px;"');?>
        </td>
    </tr>

	<tr>
		<td><?=Loc::getMessage("SEOMETA_FIELD_RULE")?></td>
		<td>
            <div id="blocks_wrapper">
                <?=$blocks?>
            </div>
            <input type="hidden" name="LOGIC" value="<?=$condition['LOGIC'] ? $condition['LOGIC'] : 'AND' ?>">
        </td>
  	</tr>

    <tr>
        <td><?=Loc::getMessage("SEOMETA_FIELD_FILTER_TYPE")?></td>
        <td>
            <?=SelectBoxFromArray('FILTER_TYPE', $arrFilterTypes, $condition['FILTER_TYPE'], '', '', false, '');?>
        </td>
    </tr>

    <tr>
        <td><?=Loc::getMessage("SEOMETA_FIELD_NAME_TEMPLATE")?></td>
        <td>
            <input type="text" name="NAME_TEMPLATE" id="NAME_TEMPLATE" value="<?=$condition['NAME_TEMPLATE']?>" size="44" maxlength="255" style="margin-right: 5px;">
            <input type="button" id="menu_NAME_TEMPLATE" value='...'>
        </td>
    </tr>

    <tr class="heading">
        <td colspan="2"><?=Loc::getMessage("SEOMETA_AUTOGENERATION_TAB1_EXTRA_OPTIONS")?></td>
    </tr>

    <tr>
        <td><?=Loc::getMessage("SEOMETA_FIELD_ACTIVE")?></td>
        <td>
            <input type="checkbox" name="ACTIVE" value="Y" <?=$condition['ACTIVE'] ? ($condition['ACTIVE'] == "Y" ? "checked" : "") : "checked"?>>
        </td>
    </tr>

    <tr>
        <td><?=Loc::getMessage("SEOMETA_FIELD_SEARCH")?></td>
        <td>
            <input type="checkbox" name="SEARCH" value="Y" <?=$condition['SEARCH'] ? ($condition['SEARCH'] == "Y" ? "checked" : "") : "checked"?>>
        </td>
    </tr>

    <tr>
        <td><?=Loc::getMessage("SEOMETA_FIELD_NO_INDEX")?></td>
        <td>
            <input type="checkbox" name="NO_INDEX" value="Y" <?=($condition['NO_INDEX'] == "Y") ? "checked" : ""?>>
        </td>
    </tr>

    <tr>
        <td><?/*ShowJSHint("podskazka");*/?><?=Loc::getMessage("SEOMETA_FIELD_STRICT")?></td>
        <td>
            <input type="checkbox" name="STRICT" value="Y" <?=$condition['STRICT'] ? ($condition['STRICT'] == "Y" ? "checked" : "") : "checked"?>>
        </td>
    </tr>

    <tr>
        <td><?=Loc::getMessage("SEOMETA_FIELD_CATEGORY")?></td>
        <td>
            <?=SelectBoxFromArray('CATEGORY', $arrCategoriesList, $condition['CATEGORY'], '', 'style="min-width: 213px"', false, '');?>
        </td>
    </tr>



    <?
    //********************
    // вторая закладка - Метаинформация
    //********************
    $tabControl->BeginNextTab();
    ?>

    <tr>
        <td width="40%"><?=Loc::getMessage("SEOMETA_FIELD_META_TITLE")?></td>
        <td width="60%">
            <textarea name="META[ELEMENT_TITLE]" id="META[ELEMENT_TITLE]" cols="55" rows="1" style="width: 90%;"><?=$meta['ELEMENT_TITLE'] ? $meta['ELEMENT_TITLE'] : ''?></textarea>
            <input style="float: right;" type="button" id="menu_META_TITLE" value='...'>
        </td>
    </tr>

    <tr>
        <td><?=Loc::getMessage("SEOMETA_FIELD_META_KEYWORDS")?></td>
        <td>
            <textarea name="META[ELEMENT_KEYWORDS]" id="META[ELEMENT_KEYWORDS]" cols="55" rows="1" style="width: 90%;"><?=$meta['ELEMENT_KEYWORDS'] ? $meta['ELEMENT_KEYWORDS'] : ''?></textarea>
            <input style="float: right;" type="button" id="menu_META_KEYWORDS" value='...'>
        </td>
    </tr>

    <tr>
        <td><?=Loc::getMessage("SEOMETA_FIELD_META_DESCRIPTION")?></td>
        <td>
            <textarea name="META[ELEMENT_DESCRIPTION]" id="META[ELEMENT_DESCRIPTION]" cols="55" rows="1" style="width: 90%;"><?=$meta['ELEMENT_DESCRIPTION'] ? $meta['ELEMENT_DESCRIPTION'] : ''?></textarea>
            <input style="float: right;" type="button" id="menu_META_DESCRIPTION" value='...'>
        </td>
    </tr>

    <tr>
        <td><?=Loc::getMessage("SEOMETA_FIELD_SECTION_HEADER")?></td>
        <td>
            <textarea name="META[ELEMENT_PAGE_TITLE]" id="META[ELEMENT_PAGE_TITLE]" cols="55" rows="1" style="width: 90%;"><?=$meta['ELEMENT_PAGE_TITLE'] ? $meta['ELEMENT_PAGE_TITLE'] : ''?></textarea>
            <input style="float: right;" type="button" id="menu_SECTION_HEADER" value='...'>
        </td>
    </tr>

    <tr>
        <td><?=Loc::getMessage("SEOMETA_FIELD_BREADCRUMBS_TITLE")?></td>
        <td>
            <textarea name="META[ELEMENT_BREADCRUMB_TITLE]" id="META[ELEMENT_BREADCRUMB_TITLE]" cols="55" rows="1" style="width: 90%;"><?=$meta['ELEMENT_BREADCRUMB_TITLE'] ? $meta['ELEMENT_BREADCRUMB_TITLE'] : ''?></textarea>
            <input style="float: right;" type="button" id="menu_BREADCRUMBS_TITLE" value='...'>
        </td>
    </tr>


    <tr>
        <td><?=Loc::getMessage("SEOMETA_FIELD_TAGS")?></td>
        <td>
            <textarea name="TAGS" id="TAGS" cols="55" rows="1" style="width: 90%;"><?=$condition["TAGS"] ? $condition["TAGS"] : ''?></textarea>
            <input style="float: right;" type="button" id="menu_TAGS" value='...'>
        </td>
    </tr>

    <?
    //********************
    // третья закладка - ЧПУ
    //********************
    $tabControl->BeginNextTab();
    ?>

    <tr>
        <td width="40%"><?=Loc::getMessage("SEOMETA_FIELD_NEW_URL_TEMPLATE")?></td>
        <td width="60%">
            <input type="text" name="NEW_URL_TEMPLATE" value="<?=$condition['NEW_URL_TEMPLATE']?>" maxlength="255" style="min-width: 80%; margin-right: 5px;">
            <?=$propMenuNewUrlTemplate?>
        </td>
    </tr>

    <tr>
        <td></td>
        <td>
            <div class="adm-info-message-wrap">
                <div class="adm-info-message" style="width: 75%;">
                    <?=Loc::getMessage("SEOMETA_FIELD_NEW_URL_TEMPLATE_NOTE")?>
                </div>
            </div>
        </td>
    </tr>

    <tr>
        <td><?=Loc::getMessage("SEOMETA_FIELD_GENERATE_CHPU")?></td>
        <td>
            <input type="checkbox" name="GENERATE_CHPU" value="Y" <?=($condition['GENERATE_CHPU'] == "Y") ? "checked" : ""?>>
        </td>
    </tr>



<?
// завершение формы - вывод кнопок сохранения изменений
$tabControl->Buttons(
    array(
        "disabled" => ($POST_RIGHT < "W"),
        "back_url" => "sotbit.seometa_autogeneration_list.php?lang=".LANG,
    )
);
?>

<input type="hidden" name="lang" value="<?=LANG?>">

<?
// завершаем интерфейс закладок
$tabControl->End();
?>


<? /* скрипты и стили для шаблонизатора ЧПУ (взяты из условий) */
Asset::getInstance()->addString("
<link rel='stylesheet' href='//code.jquery.com/ui/1.12.0/themes/smoothness/jquery-ui.css'>
<script src='//code.jquery.com/ui/1.12.0/jquery-ui.js'></script>

<script>

$(document).ready(function() {

	$('.progressbar').each(function() {
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

	$('textarea.count_symbol').keyup(function() {
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

$(document).on('click','#SotbitSeoMenuButton',function() {
	var NavMenu = $(this).siblings( '.navmenu-v' );
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

$(document).on('click','.navmenu-v li.with-prop ',function() {
	if($(this).data('prop') !== 'undefined')
	{
		if($(this).closest('tr').find('iframe').length > 0)
			{
				$(this).closest('tr').find('iframe').contents().find('body').append($(this).data( 'prop' ));
				$(this).closest('tr').find('textarea').insertAtCaret($(this).data( 'prop' ));
			}
		else
			{
				$(this).closest('tr').find('textarea').insertAtCaret($(this).data( 'prop' ));
				$(this).closest('tr').find('input[name=\"NEW_URL_TEMPLATE\"]').insertAtCaret($(this).data( 'prop' ));
				if($(this).closest('tr').find('textarea').length > 0)
					triggerTextarea($(this).closest('tr').find('textarea'));
			}

	}
});

//For add in textarea in focus place
jQuery.fn.extend({
	insertAtCaret: function(myValue) {
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
</style>");
?>



<style>
    div#blocks_wrapper {
        padding: 15px 15px 15px 70px;
        background-color: #fdfefe;
        border: 1px solid #caced7;
        border-radius: 4px;
		box-shadow: 0 1px 0 #eeeeee;
    }
    div.condition_logic {
        position: absolute;
        top: -22px;
        left: -56px;
        width: 70px;
        height: 30px;
        border-radius: 4px;
        line-height: 30px;
        color: #fff;
        font-weight: bold;
        text-align: center;
        cursor: pointer;
    }
    div.condition_logic_and {
        background-color: #9cbd6d;
    }
    div.condition_logic_or {
        background-color: #88abc2;
    }
	div.block_with_properties {
		padding: 15px 20px;
		margin-bottom: 10px;
        background-color: #edf5f6;
        border: 1px solid #c9cdd6;
        border-radius: 4px;
		box-shadow: 0 1px 0 #eceded;
        position: relative;
	}
    span.block_name {
        display: block;
        color: #113c7d;
        font-size: 13px;
        font-weight: bold;
        margin-bottom: 8px;
    }
	div.block_item {
		margin-bottom: 8px;
	}
    div.block_item select {
        margin-right: 7px;
    }
    span.property_delete {
        display: inline-block;
        width: 7px;
        height: 7px;
        cursor: pointer;
        background: transparent url(/bitrix/panel/catalog/images/cond-delete.png) no-repeat left top;
    }
    span.property_delete:hover {
        background: transparent url(/bitrix/panel/catalog/images/cond-delete.png) no-repeat left bottom;
    }
	span.button {
		margin-right: 10px;
		color: #113c7d;
		font-size: 13px;
		font-weight: bold;
		border-bottom: 1px dashed #113c7d;
        cursor: pointer;
	}
	span.button:hover {
		border-bottom: none;
	}
</style>

<script>
    var cnt = document.getElementById("blocks").getElementsByClassName("block_with_properties").length;
    if(cnt > 1)
    {
        document.getElementById("blocks_wrapper").lastElementChild.style.removeProperty("display");
    }

    function add_property(event)
    {
        var new_block = document.createElement("div");
        new_block.className = "block_item";
        new_block.innerHTML = '<?=$selectHTML?>';
        event.target.parentNode.getElementsByTagName("div")[0].appendChild(new_block);

        var numberOfSelects = event.target.parentNode.getElementsByTagName("select").length;
        var selectName = "BLOCK_WITH_PROPS_" + getNumBlock(event.target.parentNode) + "[]";
        event.target.parentNode.getElementsByTagName("select")[numberOfSelects - 1].setAttribute("name", selectName);
    }
    function getNumBlock(elem)
    {
        var i = 1;
        while(elem = elem.previousSibling)
        {
            i++;
        }
        return i;
    }
    function delete_property()
    {
        parent = event.target.parentNode;
        parent.parentNode.removeChild(parent);
    }
    function add_block_with_properties()
    {
        var logic = document.getElementsByName("LOGIC")[0].value;
        var logicClass;
        var logicText;
        if(logic == "AND")
        {
            logicClass = "condition_logic_and";
            logicText = "<?=$logicAND?>";
        }
        else
        {
            logicClass = "condition_logic_or";
            logicText = "<?=$logicOR?>";
        }

        var new_block = document.createElement("div");
        new_block.className = "block_with_properties";
        new_block.innerHTML = '<span class="block_name"><?=$nBlock?>' + (getNumBlock(event.target.previousSibling.lastElementChild) + 1) + '</span><div></div><?=$addPropertyBtn?><div class="condition_logic ' + logicClass + '">' + logicText + '</div>';
        document.getElementById("blocks").appendChild(new_block);

        if(event.target.parentNode.lastElementChild.hasAttribute("style"))
        {
            event.target.parentNode.lastElementChild.removeAttribute("style")
        }
    }
    function delete_block_with_properties()
    {
        var count = document.getElementById("blocks").getElementsByClassName("block_with_properties").length;
        if(count > 1)
        {
            var childNodes = document.getElementById("blocks").childNodes;
            document.getElementById("blocks").removeChild(childNodes[childNodes.length - 1]);

            var countAfter = document.getElementById("blocks").getElementsByClassName("block_with_properties").length;
            if(countAfter == 1)
            {
                event.target.style.display = "none";
            }
        }
    }

    $("#blocks").on("click", ".condition_logic", function() {
        var and = "<?=$logicAND?>";
        var or = "<?=$logicOR?>";
        var value = $(this).text();
        if(value == and)
        {
            $(".condition_logic").text(or).removeClass("condition_logic_and").addClass("condition_logic_or");
            $("input[name='LOGIC']").val("OR");
        }
        else
        {
            $(".condition_logic").text(and).removeClass("condition_logic_or").addClass("condition_logic_and");
            $("input[name='LOGIC']").val("AND");
        }
    });
</script>



<?
// завершение страницы
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>