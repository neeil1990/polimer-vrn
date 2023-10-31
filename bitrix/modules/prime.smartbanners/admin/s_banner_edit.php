<?php
$MODULE_ID = 'prime.smartbanners';
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$MODULE_ID."/include.php");
IncludeModuleLangFile(__FILE__);



$ID = intval($ID);

$tabs = array(
    array("DIV" => "edit1", "TAB" => GetMessage("REK_TAB_DEFAULT"), "ICON"=>"main_user_edit", "TITLE"=>GetMessage("REK_TAB_DEFAULT_TITLE")),
);

$tabControl = new CAdminTabControl("tabControl", $tabs);

if(
$REQUEST_METHOD == "POST"
&&
($save!="" || $apply!="")
&&
check_bitrix_sessid()
)
{
    if(!is_array($arIMAGE))
        $arIMAGE = $_FILES["arIMAGE"];

    if($arIMAGE_del == "Y")
        $arIMAGE["del"] = $arIMAGE_del;

    if($ACTIVE != "Y")
        $ACTIVE = 'N';

    if($OPEN_NEW_WINDOW != "Y")
        $OPEN_NEW_WINDOW = 'N';

    if($SHOW_OUT_SITE != "Y")
        $SHOW_OUT_SITE = 'N';

    if($SHOW_TIME != "Y")
        $SHOW_TIME = 'N';


    $SID = $_POST["SID"];
    if(!empty($SID))
        $SID = serialize($SID);
    else
        $SID = '';

    if(!empty($SHOW_OFF))
        $SHOW_OFF = serialize(explode("\n", $SHOW_OFF));


    $classes = new Nb_class();

    $arFields = Array(
        "ACTIVE"		 =>	$ACTIVE,
        "SID"		     =>	$SID,
        "NAME"			 => $NAME,
        "URL"			 => $URL,
        "IMAGE_ID"		 => $arIMAGE,
        "PAUSE"			 => $PAUSE,
        "SHOW_FROM"		 => $SHOW_FROM,
        "SHOW_TO"		 => $SHOW_TO,
        "SHOW_POSITION"	 => $SHOW_POSITION,
        "SHOW_OFF"	     => $SHOW_OFF,
        "SHOW_OUT_SITE"	 => $SHOW_OUT_SITE,
        "SHOW_TIME"	 => $SHOW_TIME,
        "OPEN_NEW_WINDOW"	 => $OPEN_NEW_WINDOW,
    );
    if($ID > 0)
    {
        $res = $classes->Update($ID, $arFields);
    }
    else
    {
        $ID = $classes->Add($arFields);
        $res = ($ID > 0);
    }
    if($res)
    {
        // если сохранение прошло удачно - перенаправим на новую страницу
        // (в целях защиты от повторной отправки формы нажатием кнопки "Обновить" в браузере)
        if ($apply != "")
            // если была нажата кнопка "Применить" - отправляем обратно на форму.
            LocalRedirect("/bitrix/admin/nb_s_banner_edit.php?ID=".$ID."&mess=ok&lang=".LANG."&".$tabControl->ActiveTabParam());
        else
            // если была нажата кнопка "Сохранить" - отправляем к списку элементов.
            LocalRedirect("/bitrix/admin/nb_s_banner_list.php?lang=".LANG);
    }
    else
    {
        if($e = $APPLICATION->GetException())
            $message = new CAdminMessage(GetMessage("BANNER_SAVE_ERROR"), $e);
    }

}



if($ID>0)
{
    $arBanner = Nb_class::GetByID($ID);
    if(!$arBanner->ExtractFields("str_"))
        $ID=0;

    global $DB;
    if($str_SHOW_FROM)
        $str_SHOW_FROM = date("d.m.Y",MakeTimeStamp($str_SHOW_FROM,"YYYY-MM-DD"));
    if($str_SHOW_TO)
        $str_SHOW_TO= date("d.m.Y",MakeTimeStamp($str_SHOW_TO,"YYYY-MM-DD"));

    if(!empty($str_SHOW_OFF))
        $str_SHOW_OFF = htmlspecialcharsbx(implode("\n",unserialize(htmlspecialchars_decode($str_SHOW_OFF))));
}


if(!$str_SID = unserialize(htmlspecialchars_decode($str_SID)))
    $str_SID = array();


$APPLICATION->SetTitle(($ID>0? GetMessage("BANNER_TITLE_EDIT").$ID : GetMessage("BANNER_TITLE_ADD")));
?>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php"); // второй общий пролог
?>

<?
$aMenu = array(
    array(
        "TEXT"=>GetMessage("BANNER_LIST"),
        "TITLE"=>GetMessage("BANNER_LIST_TITLE"),
        "LINK"=>"nb_s_banner_list.php?lang=".LANG,
        "ICON"=>"btn_list",
    ),
    array(
        "TEXT"	=> GetMessage("BANNER_DELETE"),
        "TITLE"	=> GetMessage("BANNER_DELETE_TITLE"),
        "LINK"	=> "javascript:if(confirm('".GetMessage("BANNER_DELETE_CONFIRM")."'))window.location='nb_s_banner_list.php?ID=".$ID."&lang=".LANGUAGE_ID."&sessid=".bitrix_sessid()."&action=delete';",
        "ICON"	=> "btn_delete"
    )
);

$topMenu = new CAdminContextMenu($aMenu);
$topMenu->Show();

if($_REQUEST["mess"] == "ok" && $ID>0)
    CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("BANNER_SAVED"), "TYPE"=>"OK"));

if($message)
    echo $message->Show();
elseif($classes->LAST_ERROR!="")
    CAdminMessage::ShowMessage($classes->LAST_ERROR);
?>

<form method="POST" Action="<?echo $APPLICATION->GetCurPage()?>" ENCTYPE="multipart/form-data" name="post_form">
    <?// проверка идентификатора сессии ?>
    <?echo bitrix_sessid_post();?>
    <input type="hidden" name="lang" value="<?=LANG?>">
    <?if($ID>0 && !$bCopy):?>
        <input type="hidden" name="ID" value="<?=$ID?>">
    <?endif;?>
    <?
    // отобразим заголовки закладок
    $tabControl->Begin();
    ?>
    <?
    //********************
    // первая закладка - форма редактирования баннера
    //********************
    $tabControl->BeginNextTab();
    ?>
    <tr>
        <td width="40%"><label for="active"><?echo GetMessage("BANNER_ACTIVE")?></label></td>
        <td width="60%">
            <?
            echo InputType("checkbox", "ACTIVE", "Y", $str_ACTIVE, false, "", 'id="active"');
            ?>
        </td>
    </tr>

    <tr>
        <td width="40%"><span class="required">*</span><?echo GetMessage("BANNER_SITE")?></td>
        <td width="60%">
            <?
            $rsSites = CSite::GetList($by="sort", $order="desc", array("ACTIVE" => "Y"));
            while ($arSite = $rsSites->Fetch())
            {
                $checked = "";
                if($str_SID[$arSite["LID"]] == "Y")
                    $checked = "checked";
                echo "<input type='checkbox' name='SID[{$arSite["LID"]}]' $checked value='Y' id='site_{$arSite["LID"]}'/><label for='site_{$arSite["LID"]}'> [".$arSite["LID"]."] ".htmlspecialcharsbx($arSite["NAME"])."</label><br/>";
            }
            ?>
        </td>
    </tr>

    <tr>
        <td><?=GetMessage("SHOW_DATE")." (".CSite::GetDateFormat("SHORT")."):"?></td>
        <td><?echo CalendarPeriod("SHOW_FROM", $str_SHOW_FROM, "SHOW_TO", $str_SHOW_TO, "post_form");?></td>
    </tr>

    <tr>
        <td><span class="required">*</span><?echo GetMessage("BANNER_NAME")?></td>
        <td><input type="text" name="NAME" value="<?echo $str_NAME;?>" size="30" maxlength="100"></td>
    </tr>

    <tr>
        <td><?echo GetMessage("BANNER_URL")?></td>
        <td><input type="text" name="URL" value="<?echo $str_URL;?>" size="30" maxlength="100"></td>
    </tr>

    <tr>
        <td width="40%"><label for="active"><?echo GetMessage("BANNER_TARGET")?></label></td>
        <td width="60%">
            <?
            echo InputType("checkbox", "OPEN_NEW_WINDOW", "Y", $str_OPEN_NEW_WINDOW, false, "", 'id="open_new_window"');
            ?>
        </td>
    </tr>

    <tr>
        <td><?echo GetMessage("BANNER_WEIGHT")?></td>
        <td><input type="text" name="PAUSE" value="<?echo $str_PAUSE;?>" size="30" maxlength="100"></td>
    </tr>

    <tr>
        <td width="40%"><?=GetMessage("BANNER_SHOW_OFF")?></td>
        <td width="60%">
            <textarea name="SHOW_OFF" cols="30" rows="5"><?=$str_SHOW_OFF?></textarea>
        </td>
    </tr>

    <tr>
        <td><?=GetMessage("POSITION")?></td>
        <td>
            <input type="radio" onclick="changeType('top');" id="SHOW_TOP" name="SHOW_POSITION" value="top"<?if (((($ID && $str_SHOW_POSITION=='top')|| !$ID) && !isset($SHOW_POSITION)) || (isset($SHOW_POSITION) && ($SHOW_POSITION == 'top'))): ?> checked="checked"<? endif; ?>>
            <label><?=GetMessage("POSITION_TOP")?></label><br>
            <input type="radio" onclick="changeType('bottom');" id="SHOW_BOTTOM" name="SHOW_POSITION" value="bottom"<?if (($ID && $str_SHOW_POSITION=='bottom') || (isset($SHOW_POSITION) && ($SHOW_POSITION == 'bottom'))): ?> checked="checked"<? endif; ?>>
            <label><?=GetMessage("POSITION_BOTTOM")?></label><br>
            <input type="radio" onclick="changeType('custom');" id="SHOW_CUSTOM" name="SHOW_POSITION" value="center"<?if (($ID && $str_SHOW_POSITION=='center') || (isset($SHOW_POSITION) && ($SHOW_POSITION == 'center'))): ?> checked="checked"<? endif; ?>>
            <label><?=GetMessage("POSITION_CENTER")?></label>
        </td>
    </tr>

    <tr>
        <td width="40%"><label for="active"><?=GetMessage("BANNER_SHOW_OUT_SITE")?></label></td>
        <td width="60%">
            <?
            echo InputType("checkbox", "SHOW_OUT_SITE", "Y", $str_SHOW_OUT_SITE, false, "", 'id="show_out_site"');
            ?>
        </td>
    </tr>

    <tr>
        <td width="40%"><label for="active"><?=GetMessage("BANNER_SHOW_TIME")?></label></td>
        <td width="60%">
            <?
            echo InputType("checkbox", "SHOW_TIME", "Y", $str_SHOW_TIME, false, "", 'id="show_time"');
            ?>
        </td>
    </tr>

    

    <tr valign="top" id="eFile">
        <td><?=GetMessage("BANNER_FILE")?></td>
        <td><?echo CFile::InputFile("arIMAGE", 25, $str_IMAGE_ID);?></td>
    </tr>
    <? if(intval($str_IMAGE_ID)>0):?>
        <tr valign="top" id="eFile">
            <td><input type="hidden" name="IMAGE_ID" value="<?=$str_IMAGE_ID?>"/></td>
            <td width="40%"><img src="<?=CFile::GetPath($str_IMAGE_ID);?>" width="300"></td>
        </tr>

    <?endif;?>

    <?
    $tabControl->Buttons(
        array(
            "back_url" => "nb_s_banner_list.php?lang=".LANG,
        )
    );
    ?>

    <?
    $tabControl->End();
    ?>
    <?
    $tabControl->ShowWarnings("post_form", $message);
    ?>
    <?echo BeginNote();?>
    <span class="required">*</span><?echo GetMessage("REQUIRED_FIELDS")?>
    <?echo EndNote();?>
    <?
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
    ?>
