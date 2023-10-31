<?
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_admin_before.php");
CJSCore::Init(array("jquery"));

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Seo\RobotsFile;
use Sotbit\Seometa\Orm\SeometaUrlTable;
use Sotbit\Seometa\Orm\SitemapTable;

$id_module = 'sotbit.seometa';
Loc::loadMessages( __FILE__ );
Loader::includeModule($id_module);

$POST_RIGHT = $APPLICATION->GetGroupRight($id_module);

if($POST_RIGHT == "D")
{
	$APPLICATION->AuthForm( Loc::getMessage( "ACCESS_DENIED" ) );
}

$ID = intval( $_REQUEST['ID'] );
$SITE_ID = trim( $_REQUEST['site_id'] );
$bDefaultHttps = false;

if(!Loader::includeModule('seo')){
    require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
    CAdminMessage::ShowMessage(["MESSAGE"=>Loc::getMessage('SOTBIT_SEOMETA_MODULE_SEO_NOT_FOUND'), "TYPE"=>"ERROR"]);
    require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
    return;
}

if($ID > 0)
{
	$dbSitemap = SitemapTable::getById( $ID );
	$arSitemap = $dbSitemap->fetch();
	
	if (!is_array( $arSitemap ))
	{
		require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
		ShowError( Loc::getMessage( "SOTBIT_SEOMETA_SEO_ERROR_SITEMAP_NOT_FOUND" ) );
		require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
	}
	else
	{
		if ($_REQUEST['action'] == 'delete' && check_bitrix_sessid())
		{
            SitemapTable::delete($ID);
			LocalRedirect( BX_ROOT . "/admin/sotbit.seometa_sitemap_list.php?lang=" . LANGUAGE_ID );
		}
		$arSitemap['SETTINGS'] = unserialize( $arSitemap['SETTINGS'] );
		$SITE_ID = $arSitemap['SITE_ID'];
	}
}

if (mb_strlen( $SITE_ID ) > 0)
{
    $rsSites = CSite::GetById( $SITE_ID );
    $arSite = $rsSites->Fetch();
	if (!is_array( $arSite ))
	{
		$SITE_ID = '';
	}
	else
	{
		$SITE_ID = $arSite['LID'];
		$arSite['DOMAINS'] = array();
		
		$robotsFile = new RobotsFile( $SITE_ID );
		if ($robotsFile->isExists())
		{
			$arHostsList = $robotsFile->getRules( 'Host' );
			foreach ( $arHostsList as $rule )
			{
				$host = $rule[1];
				if (strncmp( $host, 'https://', 8 ) === 0)
				{
					$host = mb_substr( $host, 8 );
					$bDefaultHttps = true;
				}
				$arSite['DOMAINS'][] = $host;
			}
		}
		
		if ($arSite['SERVER_NAME'] != '')
			$arSite['DOMAINS'][] = $arSite['SERVER_NAME'];
		
		$dbDomains = Bitrix\Main\SiteDomainTable::getList( array(
				'filter' => array(
						'LID' => $SITE_ID 
				),
				'select' => array(
						'DOMAIN' 
				) 
		) );
		while ( $arDomain = $dbDomains->fetch() )
		{
			$arSite['DOMAINS'][] = $arDomain['DOMAIN'];
		}
		$arSite['DOMAINS'][] = Option::get( 'main', 'server_name', '' );
		$arSite['DOMAINS'] = array_unique( $arSite['DOMAINS'] );
	}
}

if (mb_strlen( $SITE_ID ) <= 0)
{
	require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
	ShowError( Loc::getMessage( "SOTBIT_SEOMETA_SEO_ERROR_SITEMAP_NO_SITE" ) );
	require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
}

$aTabs = array(
	array(
		"DIV" => "seo_sitemap_common",
		"TAB" => Loc::getMessage( 'SEO_META_EDIT_TAB_SETTINGS' ),
		"ICON" => "main_settings",
		"TITLE" => Loc::getMessage( 'SEO_META_EDIT_TAB_SETTINGS_TITLE' )
	)
);

$tabControl = new \CAdminTabControl( "tabControl", $aTabs, true, true );

$errors = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid() && (mb_strlen( $_POST["save"] ) > 0 || mb_strlen( $_POST['apply'] ) > 0 || mb_strlen( $_POST['save_and_add'] ) > 0))
{
	$Name = $_POST['NAME'];
	if ($Name == '')
	{
		$errors[] = Loc::getMessage( 'SEO_META_ERROR_SITEMAP_NO_VALUE', array(
            '#FIELD#' => Loc::getMessage( 'SEO_META_SITEMAP_NAME' )
		) );
	}
	if (trim( $_REQUEST['FILENAME_INDEX'] ) == '')
	{
		$errors[] = Loc::getMessage( 'SEO_META_ERROR_SITEMAP_NO_VALUE', array(
            '#FIELD#' => Loc::getMessage( 'SEO_META_SITEMAP_FILENAME_ADDRESS' )
		) );
	}
    $seometaSitemap = new CSeoMetaSitemapLight();
    $mainSitemapUrl = $arSite['ABS_DOC_ROOT'] . $arSite['DIR'] .  $_REQUEST['FILENAME_INDEX'];
    if(!file_exists($mainSitemapUrl)){
        $errors[] = Loc::getMessage('SEO_META_SITEMAP_SITEMAP_NOT_FOUND');
    }
    if($_REQUEST['DATE']){
        if(!$DB->IsDate($_REQUEST['DATE'], false, LANG, "FULL")){
            $errors[] = Loc::getMessage('SEO_META_SITEMAP_DATE_WRONG');
        }
    }
    if($_REQUEST['AGENT'] === 'Y' && $_REQUEST['INTERVAL'] === ''){
        $errors[] = Loc::getMessage('SEO_META_SITEMAP_INTERVAL_WRONG');
    }
	$FilterType = array();
	if ($_REQUEST['FILTER_TYPE'] == 0)
		$FilterType = array(
			'BITRIX' => 1
		);
	elseif ($_REQUEST['FILTER_TYPE'] == 1)
		$FilterType = array(
			'BITRIX' => 0
		);
    elseif ($_REQUEST['FILTER_TYPE'] == 2)
        $FilterType = array(
			'MISSSHOP' => 1
        );
    elseif ($_REQUEST['FILTER_TYPE'] == 3)
        $FilterType = array(
			'COMBOX' => 1
        );
//	elseif ($_REQUEST['FILTER_TYPE'] == 4)
//		$FilterType = array(
//			'COMBOX' => 0
//		);

	if (empty( $errors ))
	{
		$arSettings = array(
            'PROTO' => $_REQUEST['PROTO'],
            'DOMAIN' => $_REQUEST['DOMAIN'],
            'FILENAME_INDEX' => trim($_REQUEST['FILENAME_INDEX']),
            'FILTER_TYPE' => $FilterType,
            'EXCLUDE_NOT_SEF' => (!isset($_REQUEST['EXCLUDE_NOT_SEF'])) ? 'N' : 'Y',
            'AGENT' => (!isset($_REQUEST['AGENT'])) ? 'N' : 'Y',
            'GENERATE_ALL_CONDITIONS' => (!isset($_REQUEST['GENERATE_ALL_CONDITIONS'])) ? 'N' : 'Y',
            'DATE' => $_REQUEST['DATE'],
            'INTERVAL' => ($_REQUEST['INTERVAL'] ? (is_numeric($_REQUEST['INTERVAL']) ? $_REQUEST['INTERVAL'] : 86400) : '')
		);

		$arSiteMapFields = array(
			'NAME' => trim( $Name ),
			'SITE_ID' => $SITE_ID,
			'SETTINGS' => serialize( $arSettings )
		);
		
		if ($ID > 0)
		{
			$result = SitemapTable::update( $ID, $arSiteMapFields );
		}
		else
		{
			$result = SitemapTable::add( $arSiteMapFields );
			$ID = $result->getId();
		}
		
		if ($result->isSuccess())
		{
            $nameAgentChpu = "\Sotbit\Seometa\Agent::xmlWriterAgentChpuWithRegenerate({$ID});";
            $arAgentChpu = CAgent::GetList(array(), array("NAME"=>$nameAgentChpu))->Fetch();
            if($arSettings['AGENT'] === 'Y' && $arSettings['GENERATE_ALL_CONDITIONS'] === 'Y' && !$arAgentChpu) {
                CAgent::AddAgent(
                    $nameAgentChpu,
                    $id_module,
                    "N",
                    $arSettings['INTERVAL'],
                    "",
                    "Y",
                    $arSettings['DATE']
                );
            }elseif($arAgentChpu){
                CAgent::Update($arAgentChpu['ID'], array(
                    "AGENT_INTERVAL"=>$arSettings['INTERVAL'],
                    "ACTIVE"=> ($arSettings['GENERATE_ALL_CONDITIONS'] === 'N') ? 'N' : $arSettings['AGENT']
                ));
            }
            $nameAgentNonChpu = "\Sotbit\Seometa\Agent::xmlWriterAgentChpuNotRegenerate($ID);";
            $arAgentNonChpu = CAgent::GetList(array(), array("NAME"=>$nameAgentNonChpu))->Fetch();
            if($arSettings['AGENT'] === 'Y' && $arSettings['GENERATE_ALL_CONDITIONS'] === 'N' && !$arAgentNonChpu){
                CAgent::AddAgent(
                    $nameAgentNonChpu,
                    $id_module,
                    "N",
                    $arSettings['INTERVAL'],
                    "",
                    "Y",
                    $arSettings['DATE']
                );
            }elseif($arAgentNonChpu){
                CAgent::Update($arAgentNonChpu['ID'], array(
                    "AGENT_INTERVAL"=>$arSettings['INTERVAL'],
                    "ACTIVE"=> ($arSettings['GENERATE_ALL_CONDITIONS'] === 'Y') ? 'N' : $arSettings['AGENT']
                ));
            }

			if ($_REQUEST["save"] != '')
			{
				LocalRedirect( BX_ROOT . "/admin/sotbit.seometa_sitemap_list.php?lang=" . LANGUAGE_ID );
			}
			elseif ($_REQUEST["save_and_add"] != '')
			{
				LocalRedirect( BX_ROOT . "/admin/sotbit.seometa_sitemap_list.php?lang=" . LANGUAGE_ID . "&run=" . $ID . "&" . bitrix_sessid_get() );
			}
			else
			{
				LocalRedirect( BX_ROOT . "/admin/sotbit.seometa_sitemap_edit.php?lang=" . LANGUAGE_ID . "&ID=" . $ID . "&" . $tabControl->ActiveTabParam() );
			}
		}
		else
		{
			$errors = $result->getErrorMessages();
		}
	}
}

require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

$aMenu = array();

$aMenu[] = array(
		"TEXT" => Loc::getMessage( "SEO_META_SITEMAP_LIST" ),
		"LINK" => "/bitrix/admin/sotbit.seometa_sitemap_list.php?lang=" . LANGUAGE_ID,
		"ICON" => "btn_list",
		"TITLE" => Loc::getMessage( "SEO_META_SITEMAP_LIST_TITLE" ) 
);

if ($ID > 0)
{
	$aMenu[] = array(
		"TEXT" => Loc::getMessage( "SEO_META_SITEMAP_DELETE" ),
		"LINK" => "javascript:if(confirm('" . Loc::getMessage( "SEO_META_SITEMAP_DELETE_CONFIRM" ) . "')) window.location='/bitrix/admin/sotbit.seometa_sitemap_edit.php?action=delete&ID=" . $ID . "&lang=" . LANGUAGE_ID . "&" . bitrix_sessid_get() . "';",
		"ICON" => "btn_delete",
		"TITLE" => Loc::getMessage( "SEO_META_SITEMAP_DELETE_TITLE" )
	);
}

$context = new CAdminContextMenu( $aMenu );
$context->Show();

if (!empty( $errors ))
{
	CAdminMessage::ShowMessage( join( "\n", $errors ) );
}

?>

<form method="POST" action="<?=POST_FORM_ACTION_URI?>"
	name="sitemap_form">
	<input type="hidden" name="ID" value="<?=$ID?>"> <input type="hidden"
		name="site_id" value="<?=$SITE_ID?>">
<?
$tabControl->Begin();
$tabControl->BeginNextTab();
?>
	<tr class="adm-detail-required-field">
		<td width="40%"><?=Loc::getMessage("SEO_META_SITEMAP_NAME")?>:</td>
		<td width="60%"><input type="text" name="NAME" value="<?=$arSitemap["NAME"]?>" style="width: 51%"></td>
	</tr>
	<tr class="adm-detail-required-field">
		<td width="40%"><?=Loc::getMessage("SEO_META_SITEMAP_FILENAME_ADDRESS")?>:</td>
		<td width="60%">
		    <select name="PROTO">
                <option value="0" <?=$arSitemap['SETTINGS']['PROTO'] == 0 ? ' selected="selected"' : ''?>>http</option>
                <option value="1" <?=$arSitemap['SETTINGS']['PROTO'] == 1 ? ' selected="selected"' : ''?>>https</option>
            </select>
            <b>://</b>
            <select name="DOMAIN">
                <?
                foreach($arSite['DOMAINS'] as $domain)
                {
                    $hd = $domain;
                    $e = null;
                    $hdc = CBXPunycode::ToUnicode($domain, $e);
                    ?>
                    <option value="<?=$hd?>" <?=$domain == $arSitemap['SETTINGS']['DOMAIN'] ? ' selected="selected"' : ''?>><?=$hdc?></option>
                    <?
                }
                ?>
            </select>
            <b><?=$arSite['DIR'];?></b>
            <input type="text" name="FILENAME_INDEX" value="<?=(isset($arSitemap['SETTINGS']["FILENAME_INDEX"]) && !is_null($arSitemap['SETTINGS']["FILENAME_INDEX"]))?$arSitemap['SETTINGS']["FILENAME_INDEX"]:'sitemap.xml'?>">
        </td>
	</tr>
<?
if (isset( $arSitemap['SETTINGS']['FILTER_TYPE'] ))
{
	$key = key( $arSitemap['SETTINGS']['FILTER_TYPE'] );
	$value = $arSitemap['SETTINGS']['FILTER_TYPE'][$key];
}
?>
<tr class="adm-detail-required-field">
	<td width="40%"><?=Loc::getMessage("SEO_META_SITEMAP_FILTER_TYPE")?>:</td>
	<td width="60%">
		<select name="FILTER_TYPE">
			<option value="0"
				<?=($key == 'BITRIX' && $value == 1) ? ' selected="selected"' : ''?>><?=Loc::getMessage("SEO_META_SITEMAP_FILTER_TYPE_0")?></option>
			<option value="1"
				<?=($key == 'BITRIX' && $value == 0) ? ' selected="selected"' : ''?>><?=Loc::getMessage("SEO_META_SITEMAP_FILTER_TYPE_1")?></option>
			<option value="2"
				<?=($key == 'MISSSHOP' && $value == 1) ? ' selected="selected"' : ''?>><?=Loc::getMessage("SEO_META_SITEMAP_FILTER_TYPE_2")?></option>
			<option value="3"
				<?=($key == 'COMBOX' && $value == 1) ? ' selected="selected"' : ''?>><?=Loc::getMessage("SEO_META_SITEMAP_FILTER_TYPE_3")?></option>
<!--			<option value="4"-->
				<?//=($key == 'COMBOX' && $value == 0) ? ' selected="selected"' : ''?>
<!--                >-->
            <?//=Loc::getMessage("SEO_META_SITEMAP_FILTER_TYPE_4")?>
<!--            </option>-->
		</select>
	</td>
</tr>

<tr>
	<td width="40%" valign="top">
		<?=Loc::getMessage('SEO_META_SITEMAP_EXCLUDE_NOT_SEF')?>
		<?ShowJSHint(Loc::getMessage('SEO_META_SITEMAP_EXCLUDE_NOT_SEF_DESCRIPTION'));?>
	</td>
	<td width="60%">
		<input type="checkbox" name="EXCLUDE_NOT_SEF" value="<?=!isset($arSitemap['SETTINGS']['EXCLUDE_NOT_SEF']) ? 'N' : $arSitemap['SETTINGS']['EXCLUDE_NOT_SEF'];?>" <?=($arSitemap['SETTINGS']['EXCLUDE_NOT_SEF'] == 'Y') ? 'checked' : '';?> class="adm-designed-checkbox" id="EXCLUDE_NOT_SEF">
		<label class="adm-designed-checkbox-label" for="EXCLUDE_NOT_SEF" title=""></label>
	</td>
</tr>
<tr>
    <td width="40%" valign="top">
        <?=Loc::getMessage('SEO_META_SITEMAP_GENERATE_ALL_CONDITIONS')?>
    </td>
    <td width="60%">
        <input type="checkbox" name="GENERATE_ALL_CONDITIONS" value="<?=!isset($arSitemap['SETTINGS']['GENERATE_ALL_CONDITIONS']) ? 'N' : $arSitemap['SETTINGS']['GENERATE_ALL_CONDITIONS'];?>" <?=($arSitemap['SETTINGS']['GENERATE_ALL_CONDITIONS'] == 'Y') ? 'checked' : '';?> class="adm-designed-checkbox" id="GENERATE_ALL_CONDITIONS">
        <label class="adm-designed-checkbox-label" for="GENERATE_ALL_CONDITIONS" title=""></label>
    </td>
</tr>
<tr>
    <td align="center" colspan="2">
        <div align="center" class="adm-info-message-wrap">
            <div class="adm-info-message">
                <?= GetMessage('SEO_META_SITEMAP_GENERATE_ALL_CONDITIONS_NOTE'); ?>
            </div>
        </div>
    </td>
</tr>
<tr class="heading">
    <td colspan="2"><?=Loc::getMessage("SEO_META_SITEMAP_AGENT")?></td>
</tr>

<tr>
    <td width="40%" valign="top">
        <?=Loc::getMessage('SEO_META_SITEMAP_AGENT_RUN')?>
    </td>
    <td width="60%">
        <input type="checkbox" name="AGENT" value="<?=!isset($arSitemap['SETTINGS']['AGENT']) ? 'N' : $arSitemap['SETTINGS']['AGENT'];?>" <?=($arSitemap['SETTINGS']['AGENT'] == 'Y') ? 'checked' : '';?> class="adm-designed-checkbox" id="AGENT">
        <label class="adm-designed-checkbox-label" for="AGENT" title=""></label>
    </td>
</tr>
<tr>
    <td align="center" colspan="2">
        <div align="center" class="adm-info-message-wrap">
            <div class="adm-info-message">
                <?= GetMessage('SEO_META_SITEMAP_AGENT_NOTE'); ?>
            </div>
        </div>
    </td>
</tr>
<tr id="tr_ACTIVE_FROM">
    <td><?= Loc::getMessage("SEO_META_SITEMAP_DATE") ?></td>
    <td><?= CAdminCalendar::CalendarDate("DATE", $arSitemap['SETTINGS']['DATE'], 19, true)?></td>
</tr>
<td width="40%" valign="top">
    <?=Loc::getMessage('SEO_META_SITEMAP_INTERVAL')?>
</td>
<td width="60%">
    <input type="text" name="INTERVAL" value="<?=$arSitemap['SETTINGS']['INTERVAL']?>" id="INTERVAL">
</td>
<tr>
    <td align="center" colspan="2">
        <div align="center" class="adm-info-message-wrap">
            <div class="adm-info-message">
                <?= GetMessage('SEO_META_SITEMAP_AGENT_WORK'); ?>
            </div>
        </div>
    </td>
</tr>

<?
$tabControl->Buttons(array());
?>

<input type="submit" name="save_and_add" value="<?=Loc::getMessage('SEO_META_SITEMAP_SAVEANDRUN')?>" />
<?=bitrix_sessid_post();?>
</form>
<?

$tabControl->End();
require_once ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");?>