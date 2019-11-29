<?
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_admin_before.php");
CJSCore::Init(array("jquery"));

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Seo\RobotsFile;
use Sotbit\Seometa\SitemapTable;
use Bitrix\Main\Loader;
Loc::loadMessages( __FILE__ );
Loader::includeModule('sotbit.seometa');

$POST_RIGHT = $APPLICATION->GetGroupRight("sotbit.seometa");

if($POST_RIGHT == "D")
{
	$APPLICATION->AuthForm( Loc::getMessage( "ACCESS_DENIED" ) );
}

$ID = intval( $_REQUEST['ID'] );
$SITE_ID = trim( $_REQUEST['site_id'] );
$bDefaultHttps = false;

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
			$dbSitemap = SitemapTable::getById( $ID );
			$arSitemap = $dbSitemap->fetch();
			$arSitemap['SETTINGS'] = unserialize( $arSitemap['SETTINGS'] );
			$arSites = array();
			$rsSites = CSite::GetById( $arSitemap['SITE_ID'] );
			$arSite = $rsSites->Fetch();
			$arSite['ABS_DOC_ROOT'] . $arSite['DIR'];
			if (file_exists( $arSite['ABS_DOC_ROOT'] . $arSite['DIR'] . 'sitemap_seometa_' . $ID . '.xml' ))
				unlink( $arSite['ABS_DOC_ROOT'] . $arSite['DIR'] . 'sitemap_seometa_' . $ID . '.xml' );
			if (file_exists( $arSite['ABS_DOC_ROOT'] . $arSite['DIR'] . 'sitemap.xml' ))
			{
				$SiteUrl = "";
				if (isset( $arSitemap['SETTINGS']['PROTO'] ) && $arSitemap['SETTINGS']['PROTO'] == 1)
				{
					$SiteUrl .= 'https://';
				}
				elseif (isset( $arSitemap['SETTINGS']['PROTO'] ) && $arSitemap['SETTINGS']['PROTO'] == 0)
				{
					$SiteUrl .= 'http://';
				}
				if (isset( $arSitemap['SETTINGS']['DOMAIN'] ) && !empty( $arSitemap['SETTINGS']['DOMAIN'] ))
					$SiteUrl .= $arSitemap['SETTINGS']['DOMAIN'];
				
				$xml = simplexml_load_file( $arSite['ABS_DOC_ROOT'] . $arSite['DIR'] . 'sitemap.xml' );
				for($i = 0; $i < count( $xml->sitemap ); $i++)
				{
					if (isset( $xml->sitemap[$i]->loc ) && $xml->sitemap[$i]->loc == $SiteUrl . '/sitemap_seometa_' . $ID . '.xml')
					{
						unset( $xml->sitemap[$i] );
					}
				}
				file_put_contents( $arSite['ABS_DOC_ROOT'] . $arSite['DIR'] . 'sitemap.xml', $xml->asXML() );
			}
			SitemapTable::delete( $ID );
			LocalRedirect( BX_ROOT . "/admin/sotbit.seometa_sitemap_list.php?lang=" . LANGUAGE_ID );
		}
		$arSitemap['SETTINGS'] = unserialize( $arSitemap['SETTINGS'] );
		$SITE_ID = $arSitemap['SITE_ID'];
	}
}

if (strlen( $SITE_ID ) > 0)
{
	$dbSite = Main\SiteTable::getByPrimary( $SITE_ID );
	$arSite = $dbSite->fetch();
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
					$host = substr( $host, 8 );
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
		$arSite['DOMAINS'][] = \Bitrix\Main\Config\Option::get( 'main', 'server_name', '' );
		$arSite['DOMAINS'] = array_unique( $arSite['DOMAINS'] );
	}
}

if (strlen( $SITE_ID ) <= 0)
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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid() && (strlen( $_POST["save"] ) > 0 || strlen( $_POST['apply'] ) > 0 || strlen( $_POST['save_and_add'] ) > 0))
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
	elseif ($_REQUEST['FILTER_TYPE'] == 4)
		$FilterType = array(
			'COMBOX' => 0
		);

	if (empty( $errors ))
	{
		$arSettings = array(
            'PROTO' => $_REQUEST['PROTO'],
            'DOMAIN' => $_REQUEST['DOMAIN'],
            'FILENAME_INDEX' => trim($_REQUEST['FILENAME_INDEX']),
            'FILTER_TYPE' => $FilterType,
            'EXCLUDE_NOT_SEF' => (!isset($_REQUEST['EXCLUDE_NOT_SEF'])) ? 'N' : 'Y',
            /*'CHECKBOX_GENERATE' => $_REQUEST['CHECKBOX_GENERATE']*/
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
                    $hdc = CBXPunycode::ToUnicode($domain, $e = null);
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
			<option value="4"
				<?=($key == 'COMBOX' && $value == 0) ? ' selected="selected"' : ''?>><?=Loc::getMessage("SEO_META_SITEMAP_FILTER_TYPE_4")?></option>
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

<!--
<tr>
	<td width="40%" valign="top"><?="regenerate:"?></td>
	<td width="60%">
		<input type="checkbox" name="CHECKBOX_GENERATE" value="N" <?=($arSitemap['SETTINGS']['CHECKBOX_GENERATE'] == "Y" ) ? 'checked="checked"':'';?> class="adm-designed-checkbox" id="CHECKBOX_GENERATE">
		<label class="adm-designed-checkbox-label" for="CHECKBOX_GENERATE" title=""></label>
		<div class="adm-info-message-wrap">
			<div class="adm-info-message">
				<?="some description"?>
			</div>
		</div>
	</td>
</tr>
-->

<?
$tabControl->Buttons(array());
?>

<input type="submit" name="save_and_add" value="<?=Loc::getMessage('SEO_META_SITEMAP_SAVEANDRUN')?>" />
<?=bitrix_sessid_post();?>
</form>
<?

$tabControl->End();
require_once ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");?>