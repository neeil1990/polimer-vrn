<?
//<title>OZON</title>
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @global string $ACTION */
/** @global array $arOldSetupVars */
/** @global int $IBLOCK_ID */
/** @global string $SETUP_FILE_NAME */
/** @global string $SETUP_SERVER_NAME */
/** @global mixed $V */
/** @global mixed $XML_DATA */
/** @global string $SETUP_PROFILE_NAME */

use Bitrix\Main,
	Bitrix\Iblock,
	Bitrix\Catalog,
	Bitrix\Main\Loader;

if (!Loader::includeModule('wbs24.ozonexport')) return;
$ozon = new Wbs24\Ozonexport;
$ozonAdmin = $ozon->getAdminObject();
$ozonAdmin->loadJs();

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/wbs24.ozonexport/export_ozon.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/wbs24.ozonexport/export_setup_templ.php');
IncludeModuleLangFile(__FILE__);

global $APPLICATION, $USER;

$arSetupErrors = array();

$strAllowExportPath = COption::GetOptionString("catalog", "export_default_path", "/bitrix/catalog_export/");

if (($ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY') && $STEP == 1)
{
	if (isset($arOldSetupVars['IBLOCK_ID']))
		$IBLOCK_ID = $arOldSetupVars['IBLOCK_ID'];
	if (isset($arOldSetupVars['SITE_ID']))
		$SITE_ID = $arOldSetupVars['SITE_ID'];
	if (isset($arOldSetupVars['SETUP_FILE_NAME']))
		$SETUP_FILE_NAME = str_replace($strAllowExportPath,'',$arOldSetupVars['SETUP_FILE_NAME']);
	if (isset($arOldSetupVars['COMPANY_NAME']))
		$COMPANY_NAME = $arOldSetupVars['COMPANY_NAME'];
	if (isset($arOldSetupVars['SETUP_PROFILE_NAME']))
		$SETUP_PROFILE_NAME = $arOldSetupVars['SETUP_PROFILE_NAME'];
	if (isset($arOldSetupVars['V']))
		$V = $arOldSetupVars['V'];
	if (isset($arOldSetupVars['XML_DATA']))
	{
		$XML_DATA = base64_encode($arOldSetupVars['XML_DATA']);
	}
	if (isset($arOldSetupVars['SETUP_SERVER_NAME']))
		$SETUP_SERVER_NAME = $arOldSetupVars['SETUP_SERVER_NAME'];
	if (isset($arOldSetupVars['USE_HTTPS']))
		$USE_HTTPS = $arOldSetupVars['USE_HTTPS'];
	if (isset($arOldSetupVars['FILTER_AVAILABLE']))
		$filterAvalable = $arOldSetupVars['FILTER_AVAILABLE'];
	if (isset($arOldSetupVars['DISABLE_REFERERS']))
		$disableReferers = $arOldSetupVars['DISABLE_REFERERS'];
	if (isset($arOldSetupVars['EXPORT_CHARSET']))
		$exportCharset = $arOldSetupVars['EXPORT_CHARSET'];
	if (isset($arOldSetupVars['MAX_EXECUTION_TIME']))
		$maxExecutionTime = $arOldSetupVars['MAX_EXECUTION_TIME'];
	if (isset($arOldSetupVars['SET_ID']))
		$setId = $arOldSetupVars['SET_ID'];
	if (isset($arOldSetupVars['SET_OFFER_ID']))
		$setOfferId = $arOldSetupVars['SET_OFFER_ID'];
	if (isset($arOldSetupVars['MIN_STOCK']))
		$minStock = $arOldSetupVars['MIN_STOCK'];
	if (isset($arOldSetupVars['IGNORE_SALE']))
		$ignoreSale = $arOldSetupVars['IGNORE_SALE'];
	if (isset($arOldSetupVars['BLOB']))
		$blob = $arOldSetupVars['BLOB'];
    if (isset($arOldSetupVars['CONDITIONS']))
		$CONDITIONS = unserialize(base64_decode($arOldSetupVars['CONDITIONS']));
	if (isset($arOldSetupVars['CHECK_PERMISSIONS']))
		$checkPermissions = $arOldSetupVars['CHECK_PERMISSIONS'];
}

if ($STEP > 1)
{
	$IBLOCK_ID = (int)$IBLOCK_ID;
	$rsIBlocks = CIBlock::GetByID($IBLOCK_ID);
	if ($IBLOCK_ID <= 0 || !($arIBlock = $rsIBlocks->Fetch()))
	{
		$arSetupErrors[] = GetMessage("CET_ERROR_NO_IBLOCK1")." #".$IBLOCK_ID." ".GetMessage("CET_ERROR_NO_IBLOCK2");
	}
	else
	{
		$bRightBlock = !CIBlockRights::UserHasRightTo($IBLOCK_ID, $IBLOCK_ID, "iblock_admin_display");
		if ($bRightBlock)
		{
			$arSetupErrors[] = str_replace('#IBLOCK_ID#',$IBLOCK_ID,GetMessage("CET_ERROR_IBLOCK_PERM"));
		}
	}

	$SITE_ID = trim($SITE_ID);
	if ($SITE_ID === '')
	{
		$arSetupErrors[] = GetMessage('BX_CATALOG_EXPORT_YANDEX_ERR_EMPTY_SITE');
	}
	else
	{
		$iterator = Main\SiteTable::getList(array(
			'select' => array('LID'),
			'filter' => array('=LID' => $SITE_ID, '=ACTIVE' => 'Y')
		));
		$site = $iterator->fetch();
		if (empty($site))
		{
			$arSetupErrors[] = GetMessage('BX_CATALOG_EXPORT_YANDEX_ERR_BAD_SITE');
		}
	}

	if (!isset($SETUP_FILE_NAME) || $SETUP_FILE_NAME == '')
	{
		$arSetupErrors[] = GetMessage("CET_ERROR_NO_FILENAME");
	}
	elseif (preg_match(BX_CATALOG_FILENAME_REG, $strAllowExportPath.$SETUP_FILE_NAME))
	{
		$arSetupErrors[] = GetMessage("CES_ERROR_BAD_EXPORT_FILENAME");
	}
	elseif ($APPLICATION->GetFileAccessPermission($strAllowExportPath.$SETUP_FILE_NAME) < "W")
	{
		$arSetupErrors[] = str_replace("#FILE#", $strAllowExportPath.$SETUP_FILE_NAME, GetMessage('CET_YAND_RUN_ERR_SETUP_FILE_ACCESS_DENIED'));
	}

	$SETUP_SERVER_NAME = (isset($SETUP_SERVER_NAME) ? trim($SETUP_SERVER_NAME) : '');
	$COMPANY_NAME = (isset($COMPANY_NAME) ? trim($COMPANY_NAME) : '');

	if (empty($arSetupErrors))
	{
		$bAllSections = false;
		$arSections = array();
		if (!empty($V) && is_array($V))
		{
			foreach ($V as $key => $value)
			{
				if (trim($value) == "0")
				{
					$bAllSections = true;
					break;
				}
				$value = (int)$value;
				if ($value > 0)
					$arSections[] = $value;
			}
		}

		if (!$bAllSections && !empty($arSections))
		{
			$arCheckSections = array();
			$rsSections = CIBlockSection::GetList(array(), array('IBLOCK_ID' => $IBLOCK_ID, 'ID' => $arSections), false, array('ID'));
			while ($arOneSection = $rsSections->Fetch())
			{
				$arCheckSections[] = $arOneSection['ID'];
			}
			$arSections = $arCheckSections;
		}

		if (!$bAllSections && empty($arSections))
		{
			$arSetupErrors[] = GetMessage("CET_ERROR_NO_GROUPS");
			$V = array();
		}
	}

	if (is_array($V))
	{
		$V = array_unique(array_values($V));
		$_REQUEST['V'] = $V;
	}

	$arCatalog = CCatalogSku::GetInfoByIBlock($IBLOCK_ID);
	if (CCatalogSku::TYPE_PRODUCT == $arCatalog['CATALOG_TYPE'] || CCatalogSku::TYPE_FULL == $arCatalog['CATALOG_TYPE'])
	{
		if (!isset($XML_DATA) || $XML_DATA == '')
		{
			$arSetupErrors[] = GetMessage('YANDEX_ERR_SKU_SETTINGS_ABSENT');
		}
	}

	if (!isset($USE_HTTPS) || $USE_HTTPS != 'Y')
		$USE_HTTPS = 'N';
	if (isset($_POST['FILTER_AVAILABLE']) && is_string($_POST['FILTER_AVAILABLE']))
		$filterAvalable = $_POST['FILTER_AVAILABLE'];
	if (!isset($filterAvalable) || $filterAvalable != 'Y')
		$filterAvalable = 'N';
	if (isset($_POST['DISABLE_REFERERS']) && is_string($_POST['DISABLE_REFERERS']))
		$disableReferers = $_POST['DISABLE_REFERERS'];
	if (!isset($disableReferers) || $disableReferers != 'Y')
		$disableReferers = 'N';
	if (isset($_POST['EXPORT_CHARSET']) && is_string($_POST['EXPORT_CHARSET']))
		$exportCharset = $_POST['EXPORT_CHARSET'];
	if (!isset($exportCharset) || $exportCharset !== 'UTF-8')
		$exportCharset = 'windows-1251';
	if (isset($_POST['MAX_EXECUTION_TIME']) && is_string($_POST['MAX_EXECUTION_TIME']))
		$maxExecutionTime = $_POST['MAX_EXECUTION_TIME'];
	if (isset($_POST['SET_ID']) && is_string($_POST['SET_ID']))
		$setId = $_POST['SET_ID'];
	if (isset($_POST['SET_OFFER_ID']) && is_string($_POST['SET_OFFER_ID']))
		$setOfferId = $_POST['SET_OFFER_ID'];
	if (isset($_POST['MIN_STOCK']) && is_string($_POST['MIN_STOCK']))
		$minStock = (int)$_POST['MIN_STOCK'];
	if (isset($_POST['IGNORE_SALE']) && is_string($_POST['IGNORE_SALE']))
		$ignoreSale = $_POST['IGNORE_SALE'];
	if (isset($_POST['BLOB']))
		$blob = $_POST['BLOB'];
	if (isset($_POST['rule'])) {
        $obCond2 = new CCatalogCondTree();
        $boolCond = $obCond2->Init(BT_COND_MODE_PARSE, BT_COND_BUILD_CATALOG, []);
        $CONDITIONS = $boolCond ? $obCond2->Parse() : [];
        $CONDITIONS = base64_encode(serialize($CONDITIONS));
	}

	$maxExecutionTime = (!isset($maxExecutionTime) ? 0 : (int)$maxExecutionTime);
	if ($maxExecutionTime < 0)
		$maxExecutionTime = 0;

	if ($ACTION=="EXPORT_SETUP" || $ACTION=="EXPORT_EDIT" || $ACTION=="EXPORT_COPY")
	{
		if (!isset($SETUP_PROFILE_NAME) || $SETUP_PROFILE_NAME == '')
			$arSetupErrors[] = GetMessage("CET_ERROR_NO_PROFILE_NAME");
	}

	if (!empty($arSetupErrors))
	{
		$STEP = 1;
	}
}

$blob = $ozon->cleanKeysFromQuotes($blob);

$aMenu = array(
	array(
		"TEXT"=>GetMessage("CATI_ADM_RETURN_TO_LIST"),
		"TITLE"=>GetMessage("CATI_ADM_RETURN_TO_LIST_TITLE"),
		"LINK"=>"/bitrix/admin/cat_export_setup.php?lang=".LANGUAGE_ID,
		"ICON"=>"btn_list",
	)
);

$context = new CAdminContextMenu($aMenu);

$context->Show();

if (!empty($arSetupErrors))
	ShowError(implode('<br>', $arSetupErrors));

$actionParams = "";
if ($adminSidePanelHelper->isSidePanel())
{
	$actionParams = "?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER";
}
?>
<!--suppress JSUnresolvedVariable -->
<form method="post" action="<?echo $APPLICATION->GetCurPage().$actionParams ?>" name="yandex_setup_form" id="yandex_setup_form">
<?
$aTabs = array(
	array("DIV" => "yand_edit1", "TAB" => GetMessage("CAT_ADM_MISC_EXP_TAB1"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_MISC_EXP_TAB1_TITLE")),
    array("DIV" => "yand_edit_offerlog", "TAB" => GetMessage("CAT_ADM_MISC_EXP_TAB_OFFERS_LOG"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_MISC_EXP_TAB_OFFERS_LOG_TITLE")),
	array("DIV" => "yand_edit_extprice", "TAB" => GetMessage("CAT_ADM_MISC_EXP_TAB_EXTPRICE"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_MISC_EXP_TAB_EXTPRICE_TITLE")),
	array("DIV" => "yand_edit_warehouse", "TAB" => GetMessage("CAT_ADM_MISC_EXP_TAB_WAREHOUSE"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_MISC_EXP_TAB_WAREHOUSE_TITLE")),
	array("DIV" => "yand_edit_limitations", "TAB" => GetMessage("CAT_ADM_MISC_EXP_TAB_LIMITATIONS"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_MISC_EXP_TAB_LIMITATIONS_TITLE")),
    array("DIV" => "yand_edit_filter", "TAB" => GetMessage("CAT_ADM_MISC_EXP_TAB_FILTER"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_MISC_EXP_TAB_FILTER_TITLE")),
	array("DIV" => "yand_edit2", "TAB" => GetMessage("CAT_ADM_MISC_EXP_TAB2"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_MISC_EXP_TAB2_TITLE")),
);

$tabControl = new CAdminTabControl("tabYandex", $aTabs, false, true);
$tabControl->Begin();

$tabControl->BeginNextTab();

if ($STEP == 1)
{
	if (!isset($SITE_ID))
		$SITE_ID = '';
	if (!isset($XML_DATA))
		$XML_DATA = '';
	if (!isset($filterAvalable) || $filterAvalable != 'Y')
		$filterAvalable = 'N';
	if (!isset($USE_HTTPS) || $USE_HTTPS != 'Y')
		$USE_HTTPS = 'N';
	if (!isset($disableReferers) || $disableReferers != 'Y')
		$disableReferers = 'N';
	if (!isset($exportCharset) || $exportCharset !== 'UTF-8')
		$exportCharset = 'windows-1251';
	if (!isset($SETUP_SERVER_NAME))
		$SETUP_SERVER_NAME = '';
	if (!isset($COMPANY_NAME))
		$COMPANY_NAME = '';
	if (!isset($SETUP_FILE_NAME))
		$SETUP_FILE_NAME = 'ozon_'.mt_rand(0, 999999).'.php';
	if (!isset($checkPermissions) || $checkPermissions != 'Y')
		$checkPermissions = 'N';

	$siteList = array();
	$iterator = Main\SiteTable::getList(array(
		'select' => array('LID', 'NAME', 'SORT'),
		'filter' => array('=ACTIVE' => 'Y'),
		'order' => array('SORT' => 'ASC')
	));
	while ($row = $iterator->fetch())
		$siteList[$row['LID']] = $row['NAME'];
	unset($row, $iterator);
	$iblockIds = array();
	$iblockSites = array();
	$iblockMultiSites = array();
	$iterator = Catalog\CatalogIblockTable::getList(array(
		'select' => array(
			'IBLOCK_ID',
			'PRODUCT_IBLOCK_ID',
			'IBLOCK_ACTIVE' => 'IBLOCK.ACTIVE',
			'PRODUCT_IBLOCK_ACTIVE' => 'PRODUCT_IBLOCK.ACTIVE'
		),
		'filter' => array('')
	));
	while ($row = $iterator->fetch())
	{
		$row['PRODUCT_IBLOCK_ID'] = (int)$row['PRODUCT_IBLOCK_ID'];
		$row['IBLOCK_ID'] = (int)$row['IBLOCK_ID'];
		if ($row['PRODUCT_IBLOCK_ID'] > 0)
		{
			if ($row['PRODUCT_IBLOCK_ACTIVE'] == 'Y')
				$iblockIds[$row['PRODUCT_IBLOCK_ID']] = true;
		}
		else
		{
			if ($row['IBLOCK_ACTIVE'] == 'Y')
				$iblockIds[$row['IBLOCK_ID']] = true;
		}
	}
	unset($row, $iterator);
	if (!empty($iblockIds))
	{
		$activeIds = array();
		$iterator = Iblock\IblockSiteTable::getList(array(
			'select' => array('IBLOCK_ID', 'SITE_ID', 'SITE_SORT' => 'SITE.SORT'),
			'filter' => array('@IBLOCK_ID' => array_keys($iblockIds), '=SITE.ACTIVE' => 'Y'),
			'order' => array('IBLOCK_ID' => 'ASC', 'SITE_SORT' => 'ASC')
		));
		while ($row = $iterator->fetch())
		{
			$id = (int)$row['IBLOCK_ID'];

			if (!isset($iblockSites[$id]))
				$iblockSites[$id] = array(
					'ID' => $id,
					'SITES' => array()
				);
			$iblockSites[$id]['SITES'][] = array(
				'ID' => $row['SITE_ID'],
				'NAME' => $siteList[$row['SITE_ID']]
			);

			if (!isset($iblockMultiSites[$id]))
				$iblockMultiSites[$id] = false;
			else
				$iblockMultiSites[$id] = true;

			$activeIds[$id] = true;
		}
		unset($id, $row, $iterator);
		if (empty($activeIds))
		{
			$iblockIds = array();
			$iblockSites = array();
			$iblockMultiSites = array();
		}
		else
		{
			$iblockIds = array_intersect_key($iblockIds, $activeIds);
		}
		unset($activeIds);
	}
	if (empty($iblockIds))
	{

	}

	$currentList = array();
	if ($IBLOCK_ID > 0 && isset($iblockIds[$IBLOCK_ID]))
	{
		$currentList = $iblockSites[$IBLOCK_ID]['SITES'];
		if ($SITE_ID === '')
		{
			$firstSite = reset($currentList);
			$SITE_ID = $firstSite['ID'];
		}
	}

?><tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_IBLOCK'); ?></td>
	<td width="60%"><?
	echo GetIBlockDropDownListEx(
		$IBLOCK_ID, 'IBLOCK_TYPE_ID', 'IBLOCK_ID',
		array(
			'ID' => array_keys($iblockIds),
			'CHECK_PERMISSIONS' => 'Y',
			'MIN_PERMISSION' => 'U'
		),
		"ClearSelected(); changeIblockSites(0); BX('id_ifr').src='/bitrix/tools/catalog_export/ozon_util.php?IBLOCK_ID=0&'+'".bitrix_sessid_get()."';",
		"ClearSelected(); changeIblockSites(this[this.selectedIndex].value); BX('id_ifr').src='/bitrix/tools/catalog_export/ozon_util.php?IBLOCK_ID='+this[this.selectedIndex].value+'&'+'".bitrix_sessid_get()."';",
		'class="adm-detail-iblock-types"',
		'class="adm-detail-iblock-list"'
	);
	?>
		<script>
		var TreeSelected = [];
		<?
		$intCountSelected = 0;
		if (!empty($V) && is_array($V))
		{
			foreach ($V as $oneKey)
			{
				?>TreeSelected[<? echo $intCountSelected ?>] = <? echo (int)$oneKey; ?>;
				<?
				$intCountSelected++;
			}
		}
		?>
		function ClearSelected()
		{
			BX.showWait();
			TreeSelected = [];
		}
		</script>
	</td>
</tr>
<tr id="tr_SITE_ID" style="display: <?=(count($currentList) > 1 ? 'table-row' : 'none' ); ?>;">
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_YANDEX_SITE'); ?></td>
	<td width="60%">
		<script>
		function changeIblockSites(iblockId)
		{
            let ozon = new Wbs24Ozonexport();
            let iblockIdsToOfferIblockIds = <?=CUtil::PhpToJSObject($ozonAdmin->getCatalogIblockIdsToOffersIblockIds()); ?>;
            let offerIblockId = iblockIdsToOfferIblockIds[iblockId] || 0;
            ozon.activateOptionsForCurrentIblock('SET_ID', iblockId);
            ozon.activateOptionsForCurrentIblock('SET_OFFER_ID', offerIblockId);

			var iblockSites = <?=CUtil::PhpToJSObject($iblockSites); ?>,
				iblockMultiSites = <?=CUtil::PhpToJSObject($iblockMultiSites); ?>,
				tableRow = null,
				siteControl = null,
				i,
				currentSiteList;

			tableRow = BX('tr_SITE_ID');
			siteControl = BX('SITE_ID');
			if (!BX.type.isElementNode(tableRow) || !BX.type.isElementNode(siteControl))
				return;

			for (i = siteControl.length-1; i >= 0; i--)
				siteControl.remove(i);
			if (typeof(iblockSites[iblockId]) !== 'undefined')
			{
				currentSiteList = iblockSites[iblockId]['SITES'];
				for (i = 0; i < currentSiteList.length; i++)
				{
					siteControl.appendChild(BX.create(
						'option',
						{
							props: {value: BX.util.htmlspecialchars(currentSiteList[i].ID)},
							html: BX.util.htmlspecialchars('[' + currentSiteList[i].ID + '] ' + currentSiteList[i].NAME)
						}
					));
				}
			}
			if (siteControl.length > 0)
				siteControl.selectedIndex = 0;
			else
				siteControl.selectedIndex = -1;
			BX.style(tableRow, 'display', (siteControl.length > 1 ? 'table-row' : 'none'));
		}
		</script>
		<select id="SITE_ID" name="SITE_ID">
		<?
		foreach ($currentList as $site)
		{
			$selected = ($site['ID'] == $SITE_ID ? ' selected' : '');
			$name = '['.$site['ID'].'] '.$site['NAME'];
			?><option value="<?=htmlspecialcharsbx($site['ID']); ?>"<?=$selected; ?>><?=htmlspecialcharsbx($name); ?></option><?
		}
		unset($name, $selected, $site);
		?>
		</select>
	</td>
</tr>
<tr>
	<td width="40%" valign="top"><?echo GetMessage("CET_SELECT_GROUP");?></td>
	<td width="60%"><?
	if ($intCountSelected)
	{
		foreach ($V as $oneKey)
		{
			$oneKey = (int)$oneKey;
			?><input type="hidden" value="<? echo $oneKey; ?>" name="V[]" id="oldV<? echo $oneKey; ?>"><?
		}
		unset($oneKey);
	}
	?><div id="tree"></div>
	<script>
	BX.showWait();
	clevel = 0;

	function delOldV(obj)
	{
		if (!!obj)
		{
			var intSelKey = BX.util.array_search(obj.value, TreeSelected);
			if (obj.checked == false)
			{
				if (-1 < intSelKey)
				{
					TreeSelected = BX.util.deleteFromArray(TreeSelected, intSelKey);
				}

				var objOldVal = BX('oldV'+obj.value);
				if (!!objOldVal)
				{
					objOldVal.parentNode.removeChild(objOldVal);
					objOldVal = null;
				}
			}
			else
			{
				if (-1 == intSelKey)
				{
					TreeSelected[TreeSelected.length] = obj.value;
				}
			}
		}
	}

	function buildNoMenu()
	{
		var buffer;
		buffer = '<?echo GetMessageJS("CET_FIRST_SELECT_IBLOCK");?>';
		BX('tree', true).innerHTML = buffer;
		BX.closeWait();
	}

	function buildMenu()
	{
		var i,
			buffer,
			imgSpace,
			space;

		buffer = '<table border="0" cellspacing="0" cellpadding="0">';
		buffer += '<tr>';
		buffer += '<td colspan="2" valign="top" align="left"><input type="checkbox" name="V[]" value="0" id="v0"'+(BX.util.in_array(0,TreeSelected) ? ' checked' : '')+' onclick="delOldV(this);"><label for="v0"><font class="text"><b><?echo CUtil::JSEscape(GetMessage("CET_ALL_GROUPS"));?></b></font></label></td>';
		buffer += '</tr>';

		for (i in Tree[0])
		{
			if (!Tree[0][i])
			{
				space = '<input type="checkbox" name="V[]" value="'+i+'" id="V'+i+'"'+(BX.util.in_array(i,TreeSelected) ? ' checked' : '')+' onclick="delOldV(this);"><label for="V'+i+'"><span class="text">' + Tree[0][i][0] + '</span></label>';
				imgSpace = '';
			}
			else
			{
				space = '<input type="checkbox" name="V[]" value="'+i+'"'+(BX.util.in_array(i,TreeSelected) ? ' checked' : '')+' onclick="delOldV(this);"><a href="javascript: collapse(' + i + ')"><span class="text"><b>' + Tree[0][i][0] + '</b></span></a>';
				imgSpace = '<img src="/bitrix/images/catalog/load/plus.gif" width="13" height="13" id="img_' + i + '" OnClick="collapse(' + i + ')">';
			}

			buffer += '<tr>';
			buffer += '<td width="20" valign="top" align="center">' + imgSpace + '</td>';
			buffer += '<td id="node_' + i + '">' + space + '</td>';
			buffer += '</tr>';
		}

		buffer += '</table>';

		BX('tree', true).innerHTML = buffer;
		BX.adminPanel.modifyFormElements('yandex_setup_form');
		BX.closeWait();
	}

	function collapse(node)
	{
		if (!BX('table_' + node))
		{
			var i,
				buffer,
				imgSpace,
				space;

			buffer = '<table border="0" id="table_' + node + '" cellspacing="0" cellpadding="0">';

			for (i in Tree[node])
			{
				if (!Tree[node][i])
				{
					space = '<input type="checkbox" name="V[]" value="'+i+'" id="V'+i+'"'+(BX.util.in_array(i,TreeSelected) ? ' checked' : '')+' onclick="delOldV(this);"><label for="V'+i+'"><font class="text">' + Tree[node][i][0] + '</font></label>';
					imgSpace = '';
				}
				else
				{
					space = '<input type="checkbox" name="V[]" value="'+i+'"'+(BX.util.in_array(i,TreeSelected) ? ' checked' : '')+' onclick="delOldV(this);"><a href="javascript: collapse(' + i + ')"><font class="text"><b>' + Tree[node][i][0] + '</b></font></a>';
					imgSpace = '<img src="/bitrix/images/catalog/load/plus.gif" width="13" height="13" id="img_' + i + '" OnClick="collapse(' + i + ')">';
				}

				buffer += '<tr>';
				buffer += '<td width="20" align="center" valign="top">' + imgSpace + '</td>';
				buffer += '<td id="node_' + i + '">' + space + '</td>';
				buffer += '</tr>';
			}

			buffer += '</table>';

			BX('node_' + node).innerHTML += buffer;
			BX('img_' + node).src = '/bitrix/images/catalog/load/minus.gif';
		}
		else
		{
			var tbl = BX('table_' + node);
			tbl.parentNode.removeChild(tbl);
			BX('img_' + node).src = '/bitrix/images/catalog/load/plus.gif';
		}
		BX.adminPanel.modifyFormElements('yandex_setup_form');
	}
	</script>
	<iframe src="/bitrix/tools/catalog_export/ozon_util.php?IBLOCK_ID=<?=intval($IBLOCK_ID)?>&<? echo bitrix_sessid_get(); ?>" id="id_ifr" name="ifr" style="display:none"></iframe>
	</td>
</tr>

<?
// wbs24 массив параметров
$arXMLData = [
	'TYPE' => 'none',
	'XML_DATA' => [],
	'CURRENCY' => [
		'RUB' => [
			'rate' => 'SITE',
			'plus' => NULL,
		],
		'USD'=> [
			'rate' => 'SITE',
			'plus' => NULL,
		],
		'EUR' => [
			'rate' => 'SITE',
			'plus' => NULL,
		],
		'UAH' => [
			'rate' => 'SITE',
			'plus' => NULL,
		],
		'BYR' => [
			'rate' => 'SITE',
			'plus' => NULL,
		],
	],
	'PRICE' => 0,
	'SKU_EXPORT' => [
		'SKU_EXPORT_COND' => '1',
		'SKU_PROP_COND' => [
			'PROP_ID' => 0,
			'COND' => '',
			'VALUES' => [],
		],
	],
	'VAT_EXPORT' => [
		'ENABLE' => 'N',
		'BASE_VAT' => '',
	],
	'COMMON_FIELDS' => [
		'DESCRIPTION' => 'PREVIEW_TEXT',
	],
];
?>
<input type="hidden" id="XML_DATA" name="XML_DATA" value="<?=CUtil::JSEscape(base64_encode(serialize($arXMLData)));?>">
<input type="hidden" name="FILTER_AVAILABLE" value="N">
<input type="hidden" name="DISABLE_REFERERS" value="N">
<input type="hidden" name="EXPORT_CHARSET" value="UTF-8">

<tr>
	<td width="40%"><? echo GetMessage('CAT_YANDEX_CHECK_PERMISSIONS'); ?></td>
	<td width="60%">
		<input type="hidden" name="CHECK_PERMISSIONS" value="N">
		<input type="checkbox" name="CHECK_PERMISSIONS" value="Y"<?=($checkPermissions == 'Y' ? ' checked' : ''); ?>>
	</td>
</tr>
<tr>
	<td width="40%"><? echo GetMessage('CAT_YANDEX_USE_HTTPS'); ?></td>
	<td width="60%">
		<input type="hidden" name="USE_HTTPS" value="N">
		<input type="checkbox" name="USE_HTTPS" value="Y"<? echo ($USE_HTTPS == 'Y' ? ' checked' : ''); ?>>
	</td>
</tr>
</tr>
	<?
	$maxExecutionTime = (isset($maxExecutionTime) ? (int)$maxExecutionTime : 0);
?><tr>
	<td width="40%"><?=GetMessage('CAT_MAX_EXECUTION_TIME');?></td>
	<td width="60%">
		<input type="text" name="MAX_EXECUTION_TIME" size="5" value="<?=$maxExecutionTime; ?>">
	</td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("CAT_MAX_EXECUTION_TIME_NOTE");?></small></td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_SET_ID');?></td>
	<td width="60%">
        <?=$ozonAdmin->getSelectForOfferId($IBLOCK_ID, 'SET_ID', $setId);?>
	</td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_SET_ID_NOTE");?></small></td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_SET_OFFER_ID');?></td>
	<td width="60%">
        <?=$ozonAdmin->getSelectForOfferId($IBLOCK_ID, 'SET_OFFER_ID', $setOfferId);?>
	</td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_SET_OFFER_ID_NOTE");?></small></td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_MIN_STOCK');?></td>
	<td width="60%">
		<input type="text" name="MIN_STOCK" size="5" value="<?=$minStock; ?>">
	</td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_MIN_STOCK_NOTE");?></small></td>
</tr>
<tr>
	<td width="40%"><?echo GetMessage("CET_SERVER_NAME");?></td>
	<td width="60%">
		<input type="text" name="SETUP_SERVER_NAME" value="<?=htmlspecialcharsbx($SETUP_SERVER_NAME); ?>" size="50"> <input type="button" onclick="this.form['SETUP_SERVER_NAME'].value = window.location.host;" value="<?echo htmlspecialcharsbx(GetMessage('CET_SERVER_NAME_SET_CURRENT'))?>">
	</td>
</tr>
<tr>
	<td width="40%"><?=GetMessage("BX_CATALOG_EXPORT_YANDEX_COMPANY_NAME");?></td>
	<td width="60%">
		<input type="text" name="COMPANY_NAME" value="<?=htmlspecialcharsbx($COMPANY_NAME); ?>" size="50">
	</td>
</tr>
<tr>
	<td width="40%"><?echo GetMessage("CET_SAVE_FILENAME");?></td>
	<td width="60%">
		<b><? echo htmlspecialcharsbx(COption::GetOptionString("catalog", "export_default_path", "/bitrix/catalog_export/"));?></b><input type="text" name="SETUP_FILE_NAME" value="<?=htmlspecialcharsbx($SETUP_FILE_NAME); ?>" size="50">
	</td>
</tr>
<?
	if ($ACTION=="EXPORT_SETUP" || $ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY')
	{
?><tr>
	<td width="40%"><?echo GetMessage("CET_PROFILE_NAME");?></td>
	<td width="60%">
		<input type="text" name="SETUP_PROFILE_NAME" value="<?echo htmlspecialcharsbx($SETUP_PROFILE_NAME) ?>" size="30">
	</td>
</tr><?
	}
}

$tabControl->EndTab();

$tabControl->BeginNextTab();

// вкладка "Обнуление остатков"
?>
<tr>
    <td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_OFFERS_LOG_ON');?></td>
    <td width="60%">
        <input type="hidden" name="BLOB[offersLogOn]" value="N">
        <input type="checkbox" name="BLOB[offersLogOn]" value="Y"<?=($blob['offersLogOn'] == 'Y' ? ' checked' : '');?>>
    </td>
</tr>
<tr>
    <td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_OFFERS_LOG_LIFETIME');?></td>
    <td width="60%">
        <input type="text" name="BLOB[nullOfferLifetimeDays]" size="5" value="<?=$blob['nullOfferLifetimeDays']; ?>">
    </td>
</tr>
<tr>
    <td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_OFFERS_LOG_CLEAN');?></td>
    <td width="60%">
        <button id="offerLogCleanBtn"><?=GetMessage('BX_CATALOG_EXPORT_OFFERS_LOG_CLEAN_BUTTON');?></button>
    </td>
</tr>
<tr>
    <td width="40%" style="padding-top: 0;">&nbsp;</td>
    <td width="60%" style="padding-top: 0;"><small id="offerLogCleanedMsg" style="display: none;"><?=GetMessage('BX_CATALOG_EXPORT_OFFERS_LOG_CLEANED_MESSAGE');?></small></td>
</tr>
<tr>
    <td colspan="2" align="center">
        <div class="adm-info-message-wrap" align="left">
            <div class="adm-info-message"><?=GetMessage('BX_CATALOG_EXPORT_OFFERS_LOG_NOTE');?></div>
        </div>
    </td>
</tr>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        offerLogCleanBtn.onclick = async function(e) {
            e.preventDefault();
            let response = await fetch('/bitrix/tools/wbs24.ozonexport/ajax.php?ACTION=clean&PROFILE_ID=<?=$PROFILE_ID;?>');
            let responseText = await response.text();
            if (responseText == 'success') {
                offerLogCleanedMsg.style.display = 'inline';
            }
        }
    });
</script>
<?

$tabControl->EndTab();

$tabControl->BeginNextTab();

// вкладка "Ценообразование"
?>
<tr class="heading">
	<td colspan="2"><?=GetMessage('BX_CATALOG_EXPORT_COMMONPRICE_SUBTITLE');?></td>
</tr>
<tr>
    <td width="40%"><?=GetMessage('YANDEX_PRICE_TYPE');?>: </td>
    <td width="60%">
        <?=$ozonAdmin->getSelectForPriceTypes($blob['priceType'], GetMessage('YANDEX_PRICE_TYPE_NONE'));?>
    </td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_IGNORE_SALE');?></td>
	<td width="60%">
		<input type="hidden" name="IGNORE_SALE" value="N">
		<input type="checkbox" name="IGNORE_SALE" value="Y"<? echo ($ignoreSale == 'Y' ? ' checked' : ''); ?>>
	</td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_IGNORE_SALE_NOTE");?></small></td>
</tr>

<tr class="heading">
	<td colspan="2"><?=GetMessage('BX_CATALOG_EXPORT_EXTPRICE_SUBTITLE');?></td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_EXTPRICE_ON');?></td>
	<td width="60%">
		<input type="hidden" name="BLOB[extendPrice]" value="N">
		<input type="checkbox" name="BLOB[extendPrice]" value="Y"<?=($blob['extendPrice'] == 'Y' ? ' checked' : '');?>>
	</td>
</tr>

<tr>
	<td width="40%" style="padding-top: 0;"><strong><?=GetMessage("BX_CATALOG_EXPORT_EXTPRICE_PRICE");?></strong></td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_EXTPRICE_PRICE_NOTE");?></small></td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_EXTPRICE_PLUS_PERCENT');?></td>
	<td width="60%">
		<input type="text" name="BLOB[plusPercent]" size="5" value="<?=$blob['plusPercent']; ?>"> %
	</td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_EXTPRICE_PLUS_PERCENT_NOTE");?></small></td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_EXTPRICE_PLUS_ADDITIONAL_SUM');?></td>
	<td width="60%">
		<input type="text" name="BLOB[plusAdditionalSum]" size="5" value="<?=$blob['plusAdditionalSum']; ?>">
	</td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_EXTPRICE_PLUS_ADDITIONAL_SUM_NOTE");?></small></td>
</tr>

<tr>
	<td width="40%" style="padding-top: 0;"><strong><?=GetMessage("BX_CATALOG_EXPORT_EXTPRICE_OLD_PRICE");?></strong></td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_EXTPRICE_OLD_PRICE_NOTE");?></small></td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_EXTPRICE_OLD_PRICE_PLUS_PERCENT');?></td>
	<td width="60%">
		<input type="text" name="BLOB[oldPricePlusPercent]" size="5" value="<?=$blob['oldPricePlusPercent']; ?>"> %
	</td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_EXTPRICE_OLD_PRICE_PLUS_PERCENT_NOTE");?></small></td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_EXTPRICE_OLD_PRICE_MORE10K_PLUS_PERCENT');?></td>
	<td width="60%">
		<input type="text" name="BLOB[oldPrice10kPlusPercent]" size="5" value="<?=$blob['oldPrice10kPlusPercent']; ?>"> %
	</td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_EXTPRICE_OLD_PRICE_MORE10K_PLUS_PERCENT_NOTE");?></small></td>
</tr>

<tr>
	<td width="40%" style="padding-top: 0;"><strong><?=GetMessage("BX_CATALOG_EXPORT_EXTPRICE_MIN_PRICE");?></strong></td>
	<td width="60%" style="padding-top: 0;">&nbsp;</td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_EXTPRICE_MIN_PRICE_MINUS_PERCENT');?></td>
	<td width="60%">
		<input type="text" name="BLOB[newMinPriceMinusPercent]" size="5" value="<?=$blob['newMinPriceMinusPercent']; ?>"> %
	</td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_EXTPRICE_MIN_PRICE_MINUS_PERCENT_NOTE");?></small></td>
</tr>

<tr>
    <td width="40%" style="padding-top: 0;"><strong><?=GetMessage("BX_CATALOG_EXPORT_EXTPRICE_PREMIUM_PRICE");?></strong></td>
    <td width="60%" style="padding-top: 0;">&nbsp;</td>
</tr>
<tr>
    <td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_EXTPRICE_PREMIUM_PRICE_MINUS_PERCENT');?></td>
    <td width="60%">
        <input type="text" name="BLOB[premiumPriceMinusPercent]" size="5" value="<?=$blob['premiumPriceMinusPercent']; ?>"> %
    </td>
</tr>
<tr>
    <td width="40%" style="padding-top: 0;">&nbsp;</td>
    <td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_EXTPRICE_PREMIUM_PRICE_MINUS_PERCENT_NOTE");?></small></td>
</tr>
<?

$tabControl->EndTab();

$tabControl->BeginNextTab();

// вкладка "Склады"
?>
<tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_WAREHOUSE_DEFAULT_NAME');?></td>
	<td width="60%">
		<input type="text"
			id="warehouseDefaultName"
			name="BLOB[warehouseDefaultName]"
			size="50"
			value="<?=$blob['warehouseDefaultName'];?>"
			<?=($blob['extendWarehouse'] == 'Y') ? 'disabled' : ''?>
		>
	</td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_WAREHOUSE_DEFAULT_NAME_NOTE");?></small></td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_WAREHOUSE_EXTEND_ON');?></td>
	<td width="60%">
		<input type="hidden" name="BLOB[extendWarehouse]" value="N">
		<input id="extendWarehouse" type="checkbox" name="BLOB[extendWarehouse]" value="Y"<?=($blob['extendWarehouse'] == 'Y' ? ' checked' : '');?>>
	</td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_WAREHOUSE_FILTER_ON');?></td>
	<td width="60%">
		<input type="hidden" name="BLOB[extendWarehouseFilter]" value="N">
		<input type="checkbox"
            id="extendWarehouseFilter"
            name="BLOB[extendWarehouseFilter]"
            value="Y"<?=($blob['extendWarehouseFilter'] == 'Y' ? ' checked' : '');?>
            <?=($blob['extendWarehouse'] != 'Y') ? 'disabled' : ''?>
        >
	</td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_WAREHOUSE_FILTER_ON_NOTE");?></small></td>
</tr>
<?
// список складов
$ozonWarehouse = $ozon->getWarehouseObject(['extendWarehouse' => true]);
$allWarehouses = $ozonWarehouse->getAllWarehouses();
?>
<?foreach($allWarehouses as $warehouse):?>
    <tr class="warehouse_element">
        <td width="40%"><?=$warehouse['TITLE'].' (ID='.$warehouse['ID'].')';?></td>
        <td width="60%">
            <input type="hidden" name="BLOB[warehouseId<?=$warehouse['ID']?>Active]" value="N">
            <?=GetMessage("BX_CATALOG_EXPORT_WAREHOUSE_ELEMENT_ACTIVE");?>
            <input type="checkbox"
                class="warehouse_element__settings"
                name="BLOB[warehouseId<?=$warehouse['ID']?>Active]"
                value="Y"<?=($blob['warehouseId'.$warehouse['ID'].'Active'] == 'Y' ? ' checked' : '');?>
                <?=($blob['extendWarehouseFilter'] != 'Y' || $blob['extendWarehouse'] != 'Y') ? 'disabled' : ''?>
            >
            <?=GetMessage("BX_CATALOG_EXPORT_WAREHOUSE_ELEMENT_NAME");?>
            <input type="text"
                class="warehouse_element__settings"
                name="BLOB[warehouseId<?=$warehouse['ID']?>Name]"
                size="45"
                value="<?=$blob['warehouseId'.$warehouse['ID'].'Name']; ?>"
                <?=($blob['extendWarehouseFilter'] != 'Y' || $blob['extendWarehouse'] != 'Y') ? 'disabled' : ''?>
            >
        </td>
    </tr>
<?endforeach;?>

<script>
	document.addEventListener('DOMContentLoaded', function() {
        let disableWarehouseList = function (disabled) {
            let settings = document.querySelectorAll('.warehouse_element__settings');
            for (let elem of settings) {
                elem.disabled = disabled;
            }
		}

		extendWarehouse.onchange = function() {
			warehouseDefaultName.disabled = this.checked;
            extendWarehouseFilter.disabled = !this.checked;

			let disabled = (!this.checked || !extendWarehouseFilter.checked);
			disableWarehouseList(disabled);
		}

		extendWarehouseFilter.onchange = function() {
			let disabled = (!this.checked || !extendWarehouse.checked);
			disableWarehouseList(disabled);
		}
	});
</script>
<?

$tabControl->EndTab();

$tabControl->BeginNextTab();

// вкладка "Ограничения по цене"
?>

<tr>
	<td width="40%"><strong><?=GetMessage('BX_CATALOG_EXPORT_PRICE_LIMIT_ON');?></strong></td>
	<td width="60%">
		<input type="hidden" name="BLOB[limitPriceOn]" value="N">
		<input type="checkbox" name="BLOB[limitPriceOn]" value="Y"<?=($blob['limitPriceOn'] == 'Y' ? ' checked' : '');?>>
	</td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_PRICE_LIMIT');?></td>
	<td width="60%">
		<?=GetMessage('BX_CATALOG_EXPORT_PRICE_LIMIT_MIN');?>
		<input type="text" name="BLOB[limitMinPrice]" size="5" value="<?=$blob['limitMinPrice']; ?>">
		<?=GetMessage('BX_CATALOG_EXPORT_PRICE_LIMIT_MAX');?>
		<input type="text" name="BLOB[limitMaxPrice]" size="5" value="<?=$blob['limitMaxPrice']; ?>">
	</td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_PRICE_LIMIT_NOTE");?></small></td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_PRICE_LIMIT_BEFORE_EXTPRICE');?></td>
	<td width="60%">
		<input type="hidden" name="BLOB[limitPriceBeforeExtPrice]" value="N">
		<input type="checkbox" name="BLOB[limitPriceBeforeExtPrice]" value="Y"<?=($blob['limitPriceBeforeExtPrice'] == 'Y' ? ' checked' : '');?>>
	</td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_PRICE_LIMIT_BEFORE_EXTPRICE_NOTE");?></small></td>
</tr>
<?

$tabControl->EndTab();

$tabControl->BeginNextTab();

// вкладка "Фильтры"
?>

<tr>
	<td width="40%"><strong><?=GetMessage('BX_CATALOG_EXPORT_FILTER_ON');?></strong></td>
	<td width="60%">
		<input type="hidden" name="BLOB[filterOn]" value="N">
		<input type="checkbox" name="BLOB[filterOn]" value="Y"<?=($blob['filterOn'] == 'Y' ? ' checked' : '');?>>
	</td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("BX_CATALOG_EXPORT_FILTER_ON_NOTE");?></small></td>
</tr>
<tr id="tr_CONDITIONS">
    <td valign="top" colspan="2">
        <div id="ozontree" style="position: relative; z-index: 1;"></div>
        <?
        $CONDITIONS = $CONDITIONS ?? [];
        $obCond = new CCatalogCondTree();
        $boolCond = $obCond->Init(
            BT_COND_MODE_DEFAULT,
            BT_COND_BUILD_CATALOG,
            [
                'FORM_NAME' => 'yandex_setup_form',
                'CONT_ID' => 'ozontree',
                'JS_NAME' => 'JSCatCond',
            ]
        );
        if ($boolCond) $obCond->Show($CONDITIONS);
        ?>
    </td>
</tr>
<?

$tabControl->EndTab();

$tabControl->BeginNextTab();

if ($STEP==2)
{
	$SETUP_FILE_NAME = $strAllowExportPath.$SETUP_FILE_NAME;
	if (strlen($XML_DATA) > 0)
	{
		$XML_DATA = base64_decode($XML_DATA);
	}
	$SETUP_SERVER_NAME = htmlspecialcharsbx($SETUP_SERVER_NAME);
	$_POST['SETUP_SERVER_NAME'] = htmlspecialcharsbx($_POST['SETUP_SERVER_NAME']);
	$_REQUEST['SETUP_SERVER_NAME'] = htmlspecialcharsbx($_REQUEST['SETUP_SERVER_NAME']);

	$FINITE = true;
}
$tabControl->EndTab();

$tabControl->Buttons();

?><? echo bitrix_sessid_post();?><?
if ($ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY')
{
	?><input type="hidden" name="PROFILE_ID" value="<? echo intval($PROFILE_ID); ?>"><?
}

if (2 > $STEP)
{
	?><input type="hidden" name="lang" value="<?echo LANGUAGE_ID ?>">
	<input type="hidden" name="ACT_FILE" value="<?echo htmlspecialcharsbx($_REQUEST["ACT_FILE"]) ?>">
	<input type="hidden" name="ACTION" value="<?echo htmlspecialcharsbx($ACTION) ?>">
	<input type="hidden" name="STEP" value="<?echo intval($STEP) + 1 ?>">
	<input type="hidden" name="SETUP_FIELDS_LIST" value="V,IBLOCK_ID,SITE_ID,SETUP_SERVER_NAME,COMPANY_NAME,SETUP_FILE_NAME,XML_DATA,USE_HTTPS,FILTER_AVAILABLE,DISABLE_REFERERS,EXPORT_CHARSET,MAX_EXECUTION_TIME,SET_ID,SET_OFFER_ID,MIN_STOCK,IGNORE_SALE,BLOB,CONDITIONS,CHECK_PERMISSIONS">
	<input type="submit" value="<?echo ($ACTION=="EXPORT")?GetMessage("CET_EXPORT"):GetMessage("CET_SAVE")?>"><?
}

$tabControl->End();
?></form>
<script>
<?if ($STEP < 2):?>
tabYandex.SelectTab("yand_edit1");
tabYandex.DisableTab("yand_edit2");
<?elseif ($STEP == 2):?>
tabYandex.SelectTab("yand_edit2");
tabYandex.DisableTab("yand_edit1");
<?endif;?>
</script>
