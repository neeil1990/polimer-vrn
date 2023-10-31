<?php
/**
 * @link      http://wsrubi.ru/dev/bitrixsmtp/
 * @author Sergey Blazheev <s.blazheev@gmail.com>
 * @copyright Copyright (c) 2011-2017 Altair TK. (http://www.wsrubi.ru)
 */
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mail\Message;
use Zend\Mail\Headers;
global	$module_id;
global	$USER;
global	$APPLICATION;
$module_id='wsrubi.smtp';
$APPLICATION->AddHeadScript('/bitrix/modules/wsrubi.smtp/js/common.js');
include_once(dirname(__FILE__) . '/classes/general/IdnaConvert.php');
if(COption::GetOptionString($module_id,"settings_smtp_log")&&(!empty($_REQUEST['table_id'])||!empty($_REQUEST['bxsender']))) {
	include(dirname(__FILE__) . '/loglist.php');
	return;
}
CJSCore::Init(array('bx','jquery','wsrubismtpoptions'));
IncludeModuleLangFile($DOCUMENT_ROOT."/bitrix/modules/main/options.php");
IncludeModuleLangFile(__FILE__);

global $DOCUMENT_ROOT;
require_once($DOCUMENT_ROOT."/bitrix/modules/".$module_id."/prolog.php");

CModule::IncludeModule($module_id);
$arAllOptions = array(
	"main" => array(
		array("active", GetMessage("smtp_active"),"N", array("checkbox", "Y")),
        array("settings_smtp_log", GetMessage("settings_smtp_log"),"N", array("checkbox", "Y")),
		array("posting", GetMessage("posting"),"N", array("checkbox", "Y")),
        array("onlyposting", GetMessage("onlyposting"),"N", array("checkbox", "Y")),
		array("addrtovalidation", GetMessage("addrtovalidation"),"N", array("checkbox", "Y")),
		array("convert_to_utf8", GetMessage("settings_smtp_convert_to_utf8"),"N", array("checkbox", "Y")),
        array("save_email_error", GetMessage("save_email_error"),"N", array("checkbox", "Y")),
	),
    "type_profile" => Array(
        array("type_profile", GetMessage("settings_smtp_type_profile"), "type_profile", Array("selectbox",
            Array(
                ""=>GetMessage("settings_smtp_type_profile"),
                "yandex"=>GetMessage("settings_smtp_type_profile_yandex"),
                "google"=>GetMessage("settings_smtp_type_profile_google"),
                "mail.ru"=>GetMessage("settings_smtp_type_profile_mail_ru")
            ))),
    ),
	"connection_settings" => Array(
		array("settings_smtp_type_auth", GetMessage("settings_smtp_type_auth"), "login", Array("selectbox",
			Array("smtp"=>GetMessage("settings_smtp_type_auth_smtp"),
				"login"=>GetMessage("settings_smtp_type_auth_login"),
				"plain"=>GetMessage("settings_smtp_type_auth_plain"),
				"crammd5"=>GetMessage("settings_smtp_type_auth_crammd5"),
			))),

		array("settings_smtp_login", GetMessage("settings_smtp_login"), "", Array("text", 35)),
		array("settings_smtp_password", GetMessage("settings_smtp_password"), "", Array("password", 35)),
		array("settings_smtp_host", GetMessage("settings_smtp_host"), "", Array("text", 35)),
		array("settings_smtp_port", GetMessage("settings_smtp_port"), "25", array("text", 5)),
		array("settings_smtp_type_encryption", GetMessage("settings_smtp_type_encryption"), "", Array("selectbox", Array("no"=>GetMessage("settings_smtp_type_encryption_no"),"ssl"=>GetMessage("settings_smtp_type_encryption_ssl"), "tls"=>GetMessage("settings_smtp_type_encryption_tls")))),
	),
	'headers' => Array(
        array("remove_headers", GetMessage("wsrubismtp_remove_headers"), "", array("text-list", 3, 64)),
    ),
	"adv_settings" => Array(
		array("type_auth", GetMessage("settings_smtp_type_auth"), "login", Array("selectbox",
			Array("smtp"=>GetMessage("settings_smtp_type_auth_smtp"),
				"login"=>GetMessage("settings_smtp_type_auth_login"),
				"plain"=>GetMessage("settings_smtp_type_auth_plain"),
				"crammd5"=>GetMessage("settings_smtp_type_auth_crammd5"),
			))),

		array("login", GetMessage("settings_smtp_login"), "", Array("text", 35)),
		array("password", GetMessage("settings_smtp_password"), "", Array("password", 35)),
		array("host", GetMessage("settings_smtp_host"), "", Array("text", 35)),
		array("port", GetMessage("settings_smtp_port"), "25", array("text", 5)),
		array("encryption", GetMessage("settings_smtp_type_encryption"), "", Array("selectbox", Array("no"=>GetMessage("settings_smtp_type_encryption_no"),"ssl"=>GetMessage("settings_smtp_type_encryption_ssl"), "tls"=>GetMessage("settings_smtp_type_encryption_tls")))),


	),
	"test_settings" => Array(
        array("settings_smtp_testing_from", GetMessage("settings_smtp_from"), "", array("text", 35)),
		array("settings_smtp_testing_email", GetMessage("settings_smtp_testing_email"), "", array("text", 35)),
	)
);


$message = null;
if($_SERVER["REQUEST_METHOD"]=="POST" && !empty($_POST["Update"]) && $USER->CanDoOperation('edit_other_settings') && check_bitrix_sessid())
{
    if(!empty($_POST["onlyposting"])&&$_POST["onlyposting"]=='Y')
    {
        $_POST["posting"] = 'N';
    }
    if(!empty($_POST["remove_headers"])&&count($_POST["remove_headers"])>0)
    {
        $_POST["remove_headers"] = array_filter($_POST["remove_headers"], function ($val){
           if(!empty($val))
               return true;
        });
        $remove_headers = json_encode($_POST["remove_headers"]);
    }else{
        $remove_headers = json_encode([]);
    }

	COption::SetOptionString($module_id, "posting", $_POST["posting"]);
    COption::SetOptionString($module_id, "onlyposting", $_POST["onlyposting"]);
	COption::SetOptionString($module_id, "addrtovalidation", $_POST["addrtovalidation"], 'N');
	COption::SetOptionString($module_id, "settings_smtp_type_auth", $_POST["settings_smtp_type_auth"],'smtp');
	COption::SetOptionString($module_id, "active", $_POST["active"]);
	COption::SetOptionString($module_id, "settings_smtp_login", $_POST["settings_smtp_login"]);
	COption::SetOptionString($module_id, "settings_smtp_password", $_POST["settings_smtp_password"]);
	COption::SetOptionString($module_id, "settings_smtp_log", $_POST["settings_smtp_log"]);
	COption::SetOptionString($module_id, "settings_smtp_host", $_POST["settings_smtp_host"]);
	COption::SetOptionString($module_id, "settings_smtp_port", intval($_POST["settings_smtp_port"]),25);
	COption::SetOptionString($module_id, "settings_smtp_type_encryption", $_POST["settings_smtp_type_encryption"],'no');
	COption::SetOptionString($module_id, "settings_smtp_testing_email", $_POST["settings_smtp_testing_email"]);
    COption::SetOptionString($module_id, "settings_smtp_testing_from", $_POST["settings_smtp_testing_from"]);
	COption::SetOptionString($module_id, "convert_to_utf8", $_POST["convert_to_utf8"]);
    COption::SetOptionString($module_id, "save_email_error", $_POST["save_email_error"]);
    COption::SetOptionString($module_id, "remove_headers", $remove_headers);

	if(!empty($_REQUEST["advs"])){
        $email = COption::GetOptionString($module_id, "advemail");
        if(!empty($email)){
            $email = explode(',', $email);
        }else{
            $email = array();
        }
		foreach ($_REQUEST["advs"] as $key => $value){
            $email[] = $key;
			foreach ($value as $vKey => $vValue) {
				COption::SetOptionString ( $module_id, "advs[$key][$vKey]", $vValue );
			}
		}
        $email = array_unique($email, SORT_LOCALE_STRING);
        $email = implode(",", $email);
        COption::SetOptionString ( $module_id, "advemail", $email );
	}
}

if(!empty($_GET["DeleteOptionsEmail"])){
	$email = COption::GetOptionString($module_id, "advemail");
	$deleteEmail = $_GET["DeleteOptionsEmail"];
	if(!empty($email)){
		$email = explode(',', $email);
		$key = array_search($deleteEmail, $email);
		if($key!==FALSE){
			unset($email[$key]);
			$email = array_unique($email, SORT_LOCALE_STRING);
			$email = implode(",", $email);
			COption::SetOptionString ( $module_id, "advemail", $email );
			COption::RemoveOption ( $module_id, "advs[$deleteEmail][type_auth]", $vValue );
			COption::RemoveOption ( $module_id, "advs[$deleteEmail][login]", $vValue );
			COption::RemoveOption ( $module_id, "advs[$deleteEmail][password]", $vValue );
			COption::RemoveOption ( $module_id, "advs[$deleteEmail][host]", $vValue );
			COption::RemoveOption ( $module_id, "advs[$deleteEmail][port]", $vValue );
			COption::RemoveOption ( $module_id, "advs[$deleteEmail][encryption]", $vValue );
		}
	}
}

function ShowParams($arParams)
{
	global	$module_id;
	foreach($arParams as $Option)
	{
        $type = $Option[3];

        if($type[0]=="text-list")
        {
            $val = COption::GetOptionString($module_id, $Option[0]);
            $aVal = json_decode($val);
            ?>
            <tr>
                <td width="40%" <?if($type[0]=="textarea" || $type[0]=="text-list") echo 'class="adm-detail-valign-top"'?>>
                    <label for="<?echo htmlspecialcharsbx($Option[0])?>"><?echo $Option[1]?></label>
                <td width="60%">
                <?php
                foreach($aVal as $val)
                {
                    ?><input type="text" size="<?echo $type[2]?>" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0])."[]"?>"><br/><br/><?
                }
                for($j=0; $j<$type[1]; $j++)
                {
                    ?><input type="text" size="<?echo $type[2]?>" value="" name="<?echo htmlspecialcharsbx($Option[0])."[]"?>"><br/><br/><?
                }
                ?>
                </td>
            </tr>
           <?php
        }else {
            __AdmSettingsDrawRow($module_id, $Option);
        }
	}
}
function ShowParamsAdv($arParams, $key)
{
	global	$module_id;
	foreach($arParams as $Option)
	{

		$Option[0] = "advs[".$key."][".$Option[0]."]";
        if(1){
            __AdmSettingsDrawRow($module_id, $Option);
        }
	}
}
$aTabs = array();
$aTabs[] = array("DIV" => "smtplist0", "TAB" => GetMessage("WSRUBI_TAB_EXTRAMAIL_SETTINGSOUTSMTP"), "ICON" => "extramail_settings", "TITLE" => GetMessage("WSRUBI_TAB_TITLE_EXTRAMAIL_SETTINGSOUTSMTP"));
$aTabs[] = array("DIV" => "smtplist3", "TAB" => GetMessage("WSRUBI_SUPPORT_TAB"), "ICON" => "extramail_settings", "TITLE" => GetMessage("WSRUBI_SUPPORT_TITLE"));

if(COption::GetOptionString($module_id,"settings_smtp_log"))
	$aTabs[] = array("DIV" => "smtplist2", "TAB" => GetMessage("MAIN_TAB_LOG"), "ICON" => "main_settings", "TITLE" => GetMessage("MAIN_TAB_LOG"));

$aEmail = COption::GetOptionString($module_id, "advemail");
if(!empty($aEmail)){
	$aEmail = explode(',', $aEmail);
}else{
    $aEmail = Array();
}

if(!empty($_REQUEST["newemail"])&&(array_search($_REQUEST["newemail"], $aEmail)===FALSE||empty($aEmail))){
	$arAllOptions["advanc"][$_REQUEST["newemail"]]["advs"] = $arAllOptions["adv_settings"];
}
if(!empty($aEmail)&&is_array($aEmail)) {
    foreach ($aEmail as $sValue) {
        $aTabs[] = array("DIV" => "smtpset" . $sValue, "TAB" => $sValue, "ICON" => "main_settings", "TITLE" => $sValue);
        $arAllOptions["advanc"][$sValue]["advs"] = $arAllOptions["adv_settings"];
    }
}
if(!empty($_REQUEST["newemail"])&&(array_search($_REQUEST["newemail"], $aEmail)===FALSE||empty($aEmail))){
	$aTabs[] = array("DIV" => "smtpset".$_REQUEST["newemail"], "TAB" => $_REQUEST["newemail"], "ICON" => "main_settings", "TITLE" => $_REQUEST["newemail"]);
}

$tabControl = new CAdminTabControl("tabControlSmtp", $aTabs);
$tabControl->Begin();?>

<form method="POST" id="wsrubismtpoptionsform" action="<?=$APPLICATION->GetCurPage()?>?mid=<?=htmlspecialchars($mid)?>&lang=<?=LANG?>">

	<?$tabControl->BeginNextTab();?>
	<tr >
		<td colspan="2">
			<?
            global $modueInclude;
            $modueInclude = false;
			$arIncludeFile = get_included_files();
			array_filter($arIncludeFile, function($value, $key){
                global $modueInclude;
				if(stripos($value, 'wsrubismtp.php')!==FALSE) {
                    CAdminMessage::ShowNote(GetMessage("wsrubismtp_include_on"));
                    $modueInclude = true;
                }
			},ARRAY_FILTER_USE_BOTH);
            if(!$modueInclude){
                CAdminMessage::ShowMessage(GetMessage("wsrubismtp_include_off"));
                echo "<p>".GetMessage("wsrubismtp_how_include")."</p>";
            }
			
			?>
		</td>
	</tr>
	<tr class="heading">
		<td colspan="2"><b><?=GetMessage("WSRUBI_TAB_EXTRAMAIL_SETTINGSOUTSMTP")?></b></td>
	</tr>
	<? ShowParams($arAllOptions["main"]); ?>
    <tr class="heading">
        <td colspan="2"><b><?=GetMessage("heading_smtp_type_profile")?></b></td>
    </tr>
    <? ShowParams($arAllOptions["type_profile"]); ?>
	<tr class="heading">
		<td colspan="2"><b><?=GetMessage("heading_smtp_connection_settings")?></b></td>
	</tr>
    <tr>
        <td colspan="2"><center><?=GetMessage("settings_smtp_type_auth_login_info")?></center></td>
    </tr>
	<? ShowParams($arAllOptions["connection_settings"]); ?>

    <tr class="heading">
        <td colspan="2"><b><?=GetMessage("heading_headers")?></b></td>
    </tr>

    <? ShowParams($arAllOptions["headers"]); ?>

	<tr>
		<td colspan="2" align="center" >
			<input <?if (!$USER->CanDoOperation('edit_groups')) echo "disabled" ?> type="submit" name="Apply" value="<?echo GetMessage("MAIN_settings_APPLY")?>" title="<?echo GetMessage("MAIN_settings_APPLY_TITLE")?>"<?if($_REQUEST["back_url_settings"] == ""):?>  class="adm-btn-save"<?endif?>>
		</td>
	</tr>
	<tr class="heading">
		<td colspan="2"><b><?=GetMessage("heading_smtp_connection_settings_advanced")?></b></td>
	</tr>
	<tr >
		<td colspan="2" align="center">
			<label><?=GetMessage("header_settings_smtp_advanced")?></label>
			<input type="text" name="newemail" value="" />
			<input type="submit" name="addemal" <?if (!$USER->CanDoOperation('edit_other_settings')) echo "disabled" ?> value="<?=GetMessage("ADD")?>" title="<?=GetMessage("ADD")?>" />
		</td>
	</tr>
	<tr class="heading">
		<td colspan="2"><b><?=GetMessage("check_settings")?></b></td>
	</tr>

	<? ShowParams($arAllOptions["test_settings"]); ?>
	<tr>
		<td width="50%" class="adm-detail-content-cell-r">
		</td>
		<td width="50%" class="adm-detail-content-cell-r">
			<input type="button" name="" <?if (!$USER->CanDoOperation('edit_other_settings')) echo "disabled" ?> value="<?=GetMessage("settings_smtp_testing")?>" title="<?=GetMessage("settings_smtp_testing")?>"
                   onclick="window.location='/bitrix/admin/settings.php?lang=<?=LANGUAGE_ID?>&to=' + $('input[name=settings_smtp_testing_email]').val() + '&from=' + $('input[name=settings_smtp_testing_from]').val() + '&mid=<?=urlencode($module_id)?>&smtp_test=Y&<?=bitrix_sessid_get()?>'"
            />
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<?
			if($_SERVER["REQUEST_METHOD"] == "GET"  && !empty($_REQUEST["smtp_test"]) && check_bitrix_sessid()){
				include_once($DOCUMENT_ROOT.'/bitrix/modules/wsrubi.smtp/classes/general/wsrubismtp.php');

				$transport = WsRubiTools::GetSmtpTransport();

				$Message = new Message();
                if(empty($testEmail))
                    $testEmail=$_REQUEST["to"];

				if(empty($testEmail)){
					$testEmail=COption::GetOptionString("main","email_from");
				}
				if(empty($testEmail))
				    $testEmail=COption::GetOptionString($module_id,"settings_smtp_testing_email");

				$message = GetMessage("settings_test_message_body");
				$subject = GetMessage("settings_test_message_subject");
                $IDN = new IdnaConvert();
				if(COption::GetOptionString(_SmptModuleName_,"addrtovalidation", "N") != "Y")
					$validation = false;
				else
					$validation = true;
				try {
                    $testEmail = $IDN->encode($testEmail);
					$testEmail = new \Zend\Mail\MyAddress(trim($testEmail), null, $validation);
				}catch (Exception $e){
					WsRubiTools::WsrubiSMTPLog($testEmail->getEmail()."-".$e->getMessage(),'error');
				}
                try {
                    $EmailFromName = '';
                    if(empty($EmailFrom))
                        $EmailFrom=$_REQUEST["from"];
                    if(empty($EmailFrom))
                        $EmailFrom=COption::GetOptionString("main","email_from");
                    if(empty($EmailFrom))
                        $EmailFrom = trim(COption::GetOptionString(_SmptModuleName_,"settings_smtp_testing_from", "test@test.ru"));
                    $arrEmailFrom = WsRubiTools::parseEmail($EmailFrom);
                    if(!empty($arrEmailFrom['email']))
                        $EmailFrom = $arrEmailFrom['email'];
                    if(!empty($arrEmailFrom['name']))
                        $EmailFromName = $arrEmailFrom['name'];
                    $EmailFrom = $IDN->encode($EmailFrom);
                    $EmailFrom = new \Zend\Mail\MyAddress(trim($EmailFrom), $EmailFromName, $validation);
                }catch (Exception $e){
				    if(is_object($testEmail))
                        WsRubiTools::WsrubiSMTPLog($testEmail->getEmail()."<br/>Error: ".$e->getMessage() . "",'error');
				    else
                        WsRubiTools::WsrubiSMTPLog($testEmail."<br/>Error: ".$e->getMessage() . "",'error');
                }
				try {
					$Message->setTo($testEmail);

					$Message->setSubject($subject);

					$Message->setBody($message);

					if(!empty($EmailFrom))
						$Message->setFrom($EmailFrom, $EmailFromName);

    				$transport->send($Message);
					CAdminMessage::ShowNote(GetMessage("settings_smtp_connection_success"));

					$testEmail = $testEmail->getEmail();

					WsRubiTools::WsrubiSMTPLog($testEmail."\tOK");
				}catch (Exception $e){
					CAdminMessage::ShowMessage(GetMessage("settings_smtp_connection_error")."\t".$e->getMessage());
					WsRubiTools::WsrubiSMTPLog($testEmail."<br/>Error: ".$e->getMessage() . "",'error');
				}

			}

			?>
			<span class="info">
				<h3><?=GetMessage("settings_smtp_testing_info");?></h3>
			</span>
		</td>
	</tr>

    <?$tabControl->BeginNextTab();?>
    <?
        include_once(__DIR__.'/optionsupport.php');
    ?>

	<?if(COption::GetOptionString($module_id,"settings_smtp_log")):?>
		<?$tabControl->BeginNextTab();?>
		<tr>
			<td colspan="2">
				<div class="smtp_log_list">
					<?
					include(dirname(__FILE__).'/loglist.php');
					?>
				</div>
			</td>
		</tr>
	<?endif;?>
	<?
	foreach ($aEmail as $sValue){
		?>
		<? $tabControl->BeginNextTab (); ?>

		<tr class="heading">
			<td colspan="2"><b><?=  GetMessage ( "heading_smtp_connection_settings_for" ).$sValue; ?></b>
				<a href="?lang=<?=$_REQUEST["lang"];?>&mid=<?=$_REQUEST["mid"];?>&DeleteOptionsEmail=<?=$sValue;?>"   class="btn-save adm-btn-save" /><?echo GetMessage("DELETE")?></a>
			</td>
		</tr>
        <tr>
            <td colspan="2"><center><?=GetMessage("settings_smtp_type_auth_login_info")?></center></td>
        </tr>
		<tr>
			<td colspan="2">
				<? ShowParamsAdv ( $arAllOptions["advanc"][$sValue][ "advs" ],$sValue); ?>
			</td>
		</tr>
		<?
	}
	if(!empty($_REQUEST["newemail"])&&!array_search($_REQUEST["newemail"], $aEmail)) {
		?>
		<? $tabControl->BeginNextTab (); ?>

		<tr class="heading">
			<td colspan="2"><b><?=  GetMessage ( "heading_smtp_connection_settings_for" ).$_REQUEST["newemail"]; ?></b>
				<a href="?lang=<?=$_REQUEST["lang"];?>&mid=<?=$_REQUEST["mid"];?>&DeleteOptionsEmail=<?=$sValue;?>"   class="btn-save adm-btn-save" /><?echo GetMessage("DELETE")?></a>
			</td>
		</tr>
		<? ShowParamsAdv ( $arAllOptions["advanc"][$_REQUEST["newemail"]][ "advs" ],$_REQUEST["newemail"]); ?>

		<?
	}
	?>
	<?$tabControl->Buttons();?>
	<input <?if (!$USER->CanDoOperation('edit_other_settings')) echo "disabled" ?> type="submit" name="Apply" value="<?echo GetMessage("MAIN_settings_APPLY")?>" title="<?echo GetMessage("MAIN_settings_APPLY_TITLE")?>"<?if($_REQUEST["back_url_settings"] == ""):?>  class="adm-btn-save"<?endif?>>
	<input type="reset" name="reset" value="<?echo GetMessage("RESET")?>">
	<input type="hidden" name="Update" value="Y">

	<?=bitrix_sessid_post();?>
</form>

<?$tabControl->End();?>