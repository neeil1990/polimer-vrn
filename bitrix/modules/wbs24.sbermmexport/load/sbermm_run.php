<?
//<title>SberMegaMarket</title>
/** @global CUser $USER */
/** @var int $IBLOCK_ID */
/** @var string $SETUP_SERVER_NAME */
/** @var string $SETUP_FILE_NAME */
/** @var array $V */
/** @var array|string $XML_DATA */
/** @var bool $firstStep */
/** @var int $CUR_ELEMENT_ID */
/** @var bool $finalExport */
/** @var bool $boolNeedRootSection */
/** @var int $intMaxSectionID */

use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Currency,
	Bitrix\Iblock,
	Bitrix\Catalog,
	Bitrix\Sale;

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/wbs24.sbermmexport/export_sbermm.php');
IncludeModuleLangFile(__FILE__);

if (!Loader::includeModule('wbs24.sbermmexport')) return;
$sbermm = new Wbs24\Sbermmexport();

$MAX_EXECUTION_TIME = (isset($MAX_EXECUTION_TIME) && !$sbermm->isDemoMode() ? (int)$MAX_EXECUTION_TIME : 0);
if ($MAX_EXECUTION_TIME <= 0)
	$MAX_EXECUTION_TIME = 0;
if (defined('BX_CAT_CRON') && BX_CAT_CRON == true)
{
	$MAX_EXECUTION_TIME = 0;
	$firstStep = true;
}
if (defined("CATALOG_EXPORT_NO_STEP") && CATALOG_EXPORT_NO_STEP == true)
{
	$MAX_EXECUTION_TIME = 0;
	$firstStep = true;
}
if ($MAX_EXECUTION_TIME == 0)
	set_time_limit(0);

// доп. параметры
$SET_ID = (isset($SET_ID) ? $SET_ID : 'ID');
$SET_OFFER_ID = (isset($SET_OFFER_ID) ? $SET_OFFER_ID : 'ID');
$MIN_STOCK = (isset($MIN_STOCK) ? (int)$MIN_STOCK : 0);
$ORDER_BEFORE = (isset($ORDER_BEFORE) ? (int)$ORDER_BEFORE : 12);
$DELIVERY_DAYS = (isset($DELIVERY_DAYS) && $DELIVERY_DAYS != '' ? (int)$DELIVERY_DAYS : 3);
$STORE_ID = (isset($STORE_ID) ? (int)$STORE_ID : 1);
$BLOB = $sbermm->cleanKeysFromQuotes($BLOB);
$CONDITIONS = $CONDITIONS ?? '';

// параметры для объектов-модификаторов
$BLOB['minStock'] = $MIN_STOCK;
$BLOB['orderBefore'] = $ORDER_BEFORE;
$BLOB['deliveryDays'] = $DELIVERY_DAYS;
$BLOB['storeId'] = $STORE_ID;
$BLOB['conditions'] = $CONDITIONS;
$param = $sbermm->getParams($BLOB);

// получение объекта формирования цен
$sbermmPrice = $sbermm->getPriceObject($param);

// получение объекта для вывода остатков по складам
$sbermmWarehouse = $sbermm->getWarehouseObject($param);

// получение объекта, который определяет, выводить ли позицию или нет
$sbermmLimitations = $sbermm->getLimitationObject($param);

// получение объекта, который проводит окончательную фильтрацию
$sbermmFilter = $sbermm->getFilterObject($param);

// получение объекта, который выводит параметры отгрузки оффера
$sbermmShipment = $sbermm->getShipmentObject($param);

// Передача параметров в обьект базовой авторизации
$sbermm->setParamToBasicAuthObject($param);

$CHECK_PERMISSIONS = (isset($CHECK_PERMISSIONS) && $CHECK_PERMISSIONS == 'Y' ? 'Y' : 'N');
if ($CHECK_PERMISSIONS == 'Y')
	$permissionFilter = array('CHECK_PERMISSIONS' => 'Y', 'MIN_PERMISSION' => 'R', 'PERMISSIONS_BY' => 0);
else
	$permissionFilter = array('CHECK_PERMISSIONS' => 'N');

if (!isset($firstStep))
	$firstStep = true;

$pageSize = 100;
$navParams = array('nTopCount' => $pageSize);

$SETUP_VARS_LIST = 'IBLOCK_ID,SITE_ID,V,XML_DATA,SETUP_SERVER_NAME,COMPANY_NAME,SETUP_FILE_NAME,USE_HTTPS,FILTER_AVAILABLE,DISABLE_REFERERS,EXPORT_CHARSET,MAX_EXECUTION_TIME,SET_ID,SET_OFFER_ID,MIN_STOCK,ORDER_BEFORE,DELIVERY_DAYS,STORE_ID,BLOB,CONDITIONS,CHECK_PERMISSIONS';
$INTERNAL_VARS_LIST = 'intMaxSectionID,boolNeedRootSection,arSectionIDs,arAvailGroups';

global $USER;
$bTmpUserCreated = false;
if (!CCatalog::IsUserExists())
{
	$bTmpUserCreated = true;
	if (isset($USER))
		$USER_TMP = $USER;
	$USER = new CUser();
}

$saleIncluded = Loader::includeModule('sale');
if ($saleIncluded)
	Sale\DiscountCouponsManager::freezeCouponStorage();
CCatalogDiscountSave::Disable();

$arYandexFields = array(
	'typePrefix', 'picture', 'offers-picture', 'vendor', 'vendorCode', 'model',
	'author', 'name', 'publisher', 'series', 'year',
	'ISBN', 'volume', 'part', 'language', 'binding',
	'page_extent', 'table_of_contents', 'performed_by', 'performance_type',
	'storage', 'format', 'recording_length', 'artist', 'title', 'year', 'media',
	'starring', 'director', 'originalName', 'country', 'aliases',
	'description', 'sales_notes', 'promo', 'provider', 'tarifplan',
	'xCategory', 'additional', 'worldRegion', 'region', /* 'days', */ 'dataTour',
	'hotel_stars', 'room', 'meal', 'included', 'transport', 'price_min', 'price_max',
	'options', 'manufacturer_warranty', 'country_of_origin', 'downloadable', 'adult', 'param',
	'place', 'hall', 'hall_part', 'is_premiere', 'is_kids', 'date',
    // sbermm
    'barcode',
);

$formatList = array(
	'none' => array(
		'picture', 'offers-picture', 'vendor', 'vendorCode', 'model', 'sales_notes', 'manufacturer_warranty', 'country_of_origin', 'barcode',
		'adult'
	),
	'vendor.model' => array(
		'typePrefix', 'vendor', 'vendorCode', 'model', 'sales_notes', 'manufacturer_warranty', 'country_of_origin',
		'adult'
	),
	'book' => array(
		'author', 'publisher', 'series', 'year', 'ISBN', 'volume', 'part', 'language', 'binding',
		'page_extent', 'table_of_contents', 'sales_notes'
	),
	'audiobook' => array(
		'author', 'publisher', 'series', 'year', 'ISBN', 'performed_by', 'performance_type',
		'language', 'volume', 'part', 'format', 'storage', 'recording_length', 'table_of_contents'
	),
	'artist.title' => array(
		'title', 'artist', 'director', 'starring', 'originalName', 'country', 'year', 'media', 'adult'
	)
);

$arRunErrors = array();

if (isset($XML_DATA))
{
	if (is_string($XML_DATA) && CheckSerializedData($XML_DATA))
		$XML_DATA = unserialize(stripslashes($XML_DATA));
}
if (!isset($XML_DATA) || !is_array($XML_DATA))
	$arRunErrors[] = GetMessage('YANDEX_ERR_BAD_XML_DATA');

$yandexFormat = 'none';
if (isset($XML_DATA['TYPE']) && isset($formatList[$XML_DATA['TYPE']]))
	$yandexFormat = $XML_DATA['TYPE'];

$productFormat = ($yandexFormat != 'none' ? ' type="'.htmlspecialcharsbx($yandexFormat).'"' : '');

$fields = array();
$parametricFields = array();
$fieldsExist = !empty($XML_DATA['XML_DATA']) && is_array($XML_DATA['XML_DATA']);
$parametricFieldsExist = false;
if ($fieldsExist)
{
	foreach ($XML_DATA['XML_DATA'] as $key => $value)
	{
		if ($key == 'PARAMS')
			$parametricFieldsExist = (!empty($value) && is_array($value));
		if (is_array($value))
			continue;
		$value = (string)$value;
		if ($value == '')
			continue;
		$fields[$key] = $value;
	}
	unset($key, $value);
}

$fields = $sbermm->loadPropertiesForOfferIds([
    'setId' => $SET_ID,
    'setOfferId' => $SET_OFFER_ID,
    'iblockId' => $IBLOCK_ID,
], $fields);

$fieldsExist = !empty($fields);

if ($parametricFieldsExist)
{
	$parametricFields = $XML_DATA['XML_DATA']['PARAMS'];
	if (!empty($parametricFields))
	{
		foreach (array_keys($parametricFields) as $index)
		{
			if ((string)$parametricFields[$index] === '')
				unset($parametricFields[$index]);
		}
	}
	$parametricFieldsExist = !empty($parametricFields);
}

$yandexNeedPropertyIds = array();
if ($fieldsExist)
{
	foreach ($fields as $id)
		$yandexNeedPropertyIds[$id] = true;
	unset($id);
}
if ($parametricFieldsExist)
{
	foreach ($parametricFields as $id)
		$yandexNeedPropertyIds[$id] = true;
	unset($id);
}

$commonFields = [
    'PICTURE' => 'AUTO',
	'DESCRIPTION' => 'PREVIEW_TEXT',
];
if (!empty($XML_DATA['COMMON_FIELDS']) && is_array($XML_DATA['COMMON_FIELDS'])) {
    $yandexNeedPropertyIds = $sbermm->updateNeedProperties($yandexNeedPropertyIds, $XML_DATA['COMMON_FIELDS']);
    $commonFields = array_merge($commonFields, $XML_DATA['COMMON_FIELDS']);
}

$needProperties = $fieldsExist || $parametricFieldsExist || $sbermm->checkNeedProperties();

$pictureField = $commonFields['PICTURE'];
$descrField = $commonFields['DESCRIPTION'];

$propertyFields = array(
	'ID', 'PROPERTY_TYPE', 'MULTIPLE', 'USER_TYPE'
);

$itemUrlConfig = [
	'USE_DOMAIN' => true,
	'REFERRER_SEPARATOR' => '?'
];
$offerUrlConfig = [
	'USE_DOMAIN' => true,
	'REFERRER_SEPARATOR' => '?'
];

$IBLOCK_ID = (int)$IBLOCK_ID;
$db_iblock = CIBlock::GetByID($IBLOCK_ID);
if (!($ar_iblock = $db_iblock->Fetch()))
{
	$arRunErrors[] = str_replace('#ID#', $IBLOCK_ID, GetMessage('YANDEX_ERR_NO_IBLOCK_FOUND_EXT'));
}
else
{
	$ar_iblock['PROPERTY'] = array();
	$rsProps = \CIBlockProperty::GetList(
		array('SORT' => 'ASC', 'NAME' => 'ASC'),
		array('IBLOCK_ID' => $IBLOCK_ID, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N')
	);
	while ($arProp = $rsProps->Fetch())
	{
		$arProp['ID'] = (int)$arProp['ID'];
		$arProp['USER_TYPE'] = (string)$arProp['USER_TYPE'];
		$arProp['CODE'] = (string)$arProp['CODE'];
		if ($arProp['CODE'] == '')
			$arProp['CODE'] = $arProp['ID'];
		$arProp['LINK_IBLOCK_ID'] = (int)$arProp['LINK_IBLOCK_ID'];
		$ar_iblock['PROPERTY'][$arProp['ID']] = $arProp;
	}
	unset($arProp, $rsProps);

	$ar_iblock['DETAIL_PAGE_URL'] = (string)$ar_iblock['DETAIL_PAGE_URL'];
	$itemUrlConfig['USE_DOMAIN'] = !(preg_match("/^(http|https):\\/\\//i", $ar_iblock['DETAIL_PAGE_URL']));
	$itemUrlConfig['REFERRER_SEPARATOR'] = (strpos($ar_iblock['DETAIL_PAGE_URL'], '?') === false ? '?' : '&amp;');
}

$SETUP_SERVER_NAME = (isset($SETUP_SERVER_NAME) ? trim($SETUP_SERVER_NAME) : '');
$COMPANY_NAME = (isset($COMPANY_NAME) ? trim($COMPANY_NAME) : '');
$SITE_ID = (isset($SITE_ID) ? (string)$SITE_ID : '');
if ($SITE_ID === '')
	$SITE_ID = $ar_iblock['LID'];
$iterator = Main\SiteTable::getList(array(
	'select' => array('LID', 'SERVER_NAME', 'SITE_NAME', 'DIR'),
	'filter' => array('=LID' => $SITE_ID, '=ACTIVE' => 'Y')
));
$site = $iterator->fetch();
unset($iterator);
if (empty($site))
{
	$arRunErrors[] = GetMessage('BX_CATALOG_EXPORT_YANDEX_ERR_BAD_SITE');
}
else
{
	$site['SITE_NAME'] = (string)$site['SITE_NAME'];
	if ($site['SITE_NAME'] === '')
		$site['SITE_NAME'] = (string)Main\Config\Option::get('main', 'site_name');
	$site['COMPANY_NAME'] = $COMPANY_NAME;
	if ($site['COMPANY_NAME'] === '')
		$site['COMPANY_NAME'] = (string)Main\Config\Option::get('main', 'site_name');
	$site['SERVER_NAME'] = (string)$site['SERVER_NAME'];
	if ($SETUP_SERVER_NAME !== '')
		$site['SERVER_NAME'] = $SETUP_SERVER_NAME;
	if ($site['SERVER_NAME'] === '')
	{
		$site['SERVER_NAME'] = (defined('SITE_SERVER_NAME')
			? SITE_SERVER_NAME
			: (string)Main\Config\Option::get('main', 'server_name')
		);
	}
	if ($site['SERVER_NAME'] === '')
	{
		$arRunErrors[] = GetMessage('BX_CATALOG_EXPORT_YANDEX_ERR_BAD_SERVER_NAME');
	}
}

$arProperties = array();
if (isset($ar_iblock['PROPERTY']))
	$arProperties = $ar_iblock['PROPERTY'];

$boolOffers = false;
$arOffers = false;
$arOfferIBlock = false;
$intOfferIBlockID = 0;
$offersCatalog = false;
$arSelectOfferProps = array();
$arSelectedPropTypes = array(
	Iblock\PropertyTable::TYPE_STRING,
	Iblock\PropertyTable::TYPE_NUMBER,
	Iblock\PropertyTable::TYPE_LIST,
	Iblock\PropertyTable::TYPE_ELEMENT,
	Iblock\PropertyTable::TYPE_SECTION
);
$arOffersSelectKeys = array(
	YANDEX_SKU_EXPORT_ALL,
	YANDEX_SKU_EXPORT_MIN_PRICE,
	YANDEX_SKU_EXPORT_PROP,
);
$arCondSelectProp = array(
	'ZERO',
	'NONZERO',
	'EQUAL',
	'NONEQUAL',
);
$arSKUExport = array();

$arCatalog = CCatalogSku::GetInfoByIBlock($IBLOCK_ID);
if (empty($arCatalog))
{
	$arRunErrors[] = str_replace('#ID#', $IBLOCK_ID, GetMessage('YANDEX_ERR_NO_IBLOCK_IS_CATALOG'));
}
else
{
	$arCatalog['VAT_ID'] = (int)$arCatalog['VAT_ID'];
	$arOffers = CCatalogSku::GetInfoByProductIBlock($IBLOCK_ID);
	if (!empty($arOffers['IBLOCK_ID']))
	{
		$intOfferIBlockID = $arOffers['IBLOCK_ID'];
		$rsOfferIBlocks = CIBlock::GetByID($intOfferIBlockID);
		if (($arOfferIBlock = $rsOfferIBlocks->Fetch()))
		{
			$boolOffers = true;
			$rsProps = \CIBlockProperty::GetList(
				array('SORT' => 'ASC', 'NAME' => 'ASC'),
				array('IBLOCK_ID' => $intOfferIBlockID, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N')
			);
			while ($arProp = $rsProps->Fetch())
			{
				$arProp['ID'] = (int)$arProp['ID'];
				if ($arOffers['SKU_PROPERTY_ID'] != $arProp['ID'])
				{
					$arProp['USER_TYPE'] = (string)$arProp['USER_TYPE'];
					$arProp['CODE'] = (string)$arProp['CODE'];
					if ($arProp['CODE'] == '')
						$arProp['CODE'] = $arProp['ID'];
					$arProp['LINK_IBLOCK_ID'] = (int)$arProp['LINK_IBLOCK_ID'];

					$ar_iblock['OFFERS_PROPERTY'][$arProp['ID']] = $arProp;
					$arProperties[$arProp['ID']] = $arProp;
					if (in_array($arProp['PROPERTY_TYPE'], $arSelectedPropTypes))
						$arSelectOfferProps[] = $arProp['ID'];
				}
			}
			unset($arProp, $rsProps);
			$arOfferIBlock['LID'] = $site['LID'];

			$arOfferIBlock['DETAIL_PAGE_URL'] = (string)$arOfferIBlock['DETAIL_PAGE_URL'];
			if ($arOfferIBlock['DETAIL_PAGE_URL'] == '#PRODUCT_URL#')
			{
				$offerUrlConfig = $itemUrlConfig;
			}
			else
			{
				$offerUrlConfig['USE_DOMAIN'] = !(preg_match("/^(http|https):\\/\\//i", $arOfferIBlock['DETAIL_PAGE_URL']));
				$offerUrlConfig['REFERRER_SEPARATOR'] = (strpos($arOfferIBlock['DETAIL_PAGE_URL'], '?') === false ? '?' : '&amp;');
			}
		}
		else
		{
			$arRunErrors[] = GetMessage('YANDEX_ERR_BAD_OFFERS_IBLOCK_ID');
		}
		unset($rsOfferIBlocks);
	}
	if ($boolOffers)
	{
		$offersCatalog = \CCatalog::GetByID($intOfferIBlockID);
		$offersCatalog['VAT_ID'] = (int)$offersCatalog['VAT_ID'];
		if (empty($XML_DATA['SKU_EXPORT']))
		{
			$arRunErrors[] = GetMessage('YANDEX_ERR_SKU_SETTINGS_ABSENT');
		}
		else
		{
			$arSKUExport = $XML_DATA['SKU_EXPORT'];;
			if (empty($arSKUExport['SKU_EXPORT_COND']) || !in_array($arSKUExport['SKU_EXPORT_COND'],$arOffersSelectKeys))
			{
				$arRunErrors[] = GetMessage('YANDEX_SKU_EXPORT_ERR_CONDITION_ABSENT');
			}
			if (YANDEX_SKU_EXPORT_PROP == $arSKUExport['SKU_EXPORT_COND'])
			{
				if (empty($arSKUExport['SKU_PROP_COND']) || !is_array($arSKUExport['SKU_PROP_COND']))
				{
					$arRunErrors[] = GetMessage('YANDEX_SKU_EXPORT_ERR_PROPERTY_ABSENT');
				}
				else
				{
					if (empty($arSKUExport['SKU_PROP_COND']['PROP_ID']) || !in_array($arSKUExport['SKU_PROP_COND']['PROP_ID'],$arSelectOfferProps))
					{
						$arRunErrors[] = GetMessage('YANDEX_SKU_EXPORT_ERR_PROPERTY_ABSENT');
					}
					if (empty($arSKUExport['SKU_PROP_COND']['COND']) || !in_array($arSKUExport['SKU_PROP_COND']['COND'],$arCondSelectProp))
					{
						$arRunErrors[] = GetMessage('YANDEX_SKU_EXPORT_ERR_PROPERTY_COND_ABSENT');
					}
					else
					{
						if ($arSKUExport['SKU_PROP_COND']['COND'] == 'EQUAL' || $arSKUExport['SKU_PROP_COND']['COND'] == 'NONEQUAL')
						{
							if (empty($arSKUExport['SKU_PROP_COND']['VALUES']))
							{
								$arRunErrors[] = GetMessage('YANDEX_SKU_EXPORT_ERR_PROPERTY_VALUES_ABSENT');
							}
						}
					}
				}
			}
		}
	}
}

$propertyIdList = array_keys($arProperties);
if (empty($arRunErrors))
{
	if (
		$arCatalog['CATALOG_TYPE'] == CCatalogSku::TYPE_FULL
		|| $arCatalog['CATALOG_TYPE'] == CCatalogSku::TYPE_PRODUCT
	)
		$propertyIdList[] = $arCatalog['SKU_PROPERTY_ID'];
}

$arUserTypeFormat = array();
foreach($arProperties as $key => $arProperty)
{
	$arUserTypeFormat[$arProperty['ID']] = false;
	if ($arProperty['USER_TYPE'] == '')
		continue;

	$arUserType = \CIBlockProperty::GetUserType($arProperty['USER_TYPE']);
	if (isset($arUserType['GetPublicViewHTML']))
	{
		$arUserTypeFormat[$arProperty['ID']] = $arUserType['GetPublicViewHTML'];
		$arProperties[$key]['PROPERTY_TYPE'] = 'USER_TYPE';
	}
}
unset($arUserType, $key, $arProperty);

$bAllSections = false;
$arSections = array();
if (empty($arRunErrors))
{
	if (is_array($V))
	{
		foreach ($V as $key => $value)
		{
			if (trim($value)=="0")
			{
				$bAllSections = true;
				break;
			}
			$value = (int)$value;
			if ($value > 0)
			{
				$arSections[] = $value;
			}
		}
	}
	if (!$bAllSections && !empty($arSections) && $CHECK_PERMISSIONS == 'Y')
	{
		$clearedValues = array();
		$filter = array(
			'IBLOCK_ID' => $IBLOCK_ID,
			'ID' => $arSections
		);
		$iterator = CIBlockSection::GetList(
			array(),
			array_merge($filter, $permissionFilter),
			false,
			array('ID')
		);
		while ($row = $iterator->Fetch())
			$clearedValues[] = (int)$row['ID'];
		unset($row, $iterator);
		$arSections = $clearedValues;
		unset($clearedValues);
	}

	if (!$bAllSections && empty($arSections))
	{
		$arRunErrors[] = GetMessage('YANDEX_ERR_NO_SECTION_LIST');
	}
}

$selectedPriceType = 0;
$priceFromProps = false;
if (!empty($XML_DATA['PRICE']))
{
	$XML_DATA['PRICE'] = (int)$XML_DATA['PRICE'];
	if ($XML_DATA['PRICE'] > 0)
	{
		$priceIterator = Catalog\GroupAccessTable::getList([
			'select' => ['CATALOG_GROUP_ID'],
			'filter' => ['=CATALOG_GROUP_ID' => $XML_DATA['PRICE']/* , '=GROUP_ID' => 2 */]
		]);
		$priceType = $priceIterator->fetch();
		if (empty($priceType))
			$arRunErrors[] = GetMessage('YANDEX_ERR_BAD_PRICE_TYPE');
		else
			$selectedPriceType = $XML_DATA['PRICE'];
		unset($priceType, $priceIterator);
	}
	elseif ($XML_DATA['PRICE'] == -1)
    {
        $priceFromProps = true;
    }
    else
	{
		$arRunErrors[] = GetMessage('YANDEX_ERR_BAD_PRICE_TYPE');
	}
}
$priceTypeList = [];
if (empty($arRunErrors))
{
	if ($selectedPriceType > 0)
	{
		$priceTypeList = [$selectedPriceType];
	}
	else
	{
		$priceTypeList = [];
		$priceIterator = Catalog\GroupAccessTable::getList([
			'select' => ['CATALOG_GROUP_ID'],
			'filter' => ['=GROUP_ID' => 2],
			'order' => ['CATALOG_GROUP_ID' => 'ASC']
		]);
		while ($priceType = $priceIterator->fetch())
		{
			$priceTypeId = (int)$priceType['CATALOG_GROUP_ID'];
			$priceTypeList[$priceTypeId] = $priceTypeId;
			unset($priceTypeId);
		}
		unset($priceType, $priceIterator);
		if (empty($priceTypeList))
			$arRunErrors[] = GetMessage('BX_CATALOG_EXPORT_YANDEX_ERR_NO_AVAILABLE_PRICE_TYPES');
	}
}

$usedProtocol = (isset($USE_HTTPS) && $USE_HTTPS == 'Y' ? 'https://' : 'http://');
$filterAvailable = (isset($FILTER_AVAILABLE) && $FILTER_AVAILABLE == 'Y');
$disableReferers = true; //(isset($DISABLE_REFERERS) && $DISABLE_REFERERS == 'Y');
$exportCharset = (isset($EXPORT_CHARSET) && is_string($EXPORT_CHARSET) ? $EXPORT_CHARSET : '');
if ($exportCharset != 'UTF-8')
	$exportCharset = 'windows-1251';

$vatExportSettings = array(
	'ENABLE' => 'N',
	'BASE_VAT' => ''
);

$vatRates = array();
$vatList = array();

if (!empty($XML_DATA['VAT_EXPORT']) && is_array($XML_DATA['VAT_EXPORT']))
	$vatExportSettings = array_merge($vatExportSettings, $XML_DATA['VAT_EXPORT']);
$vatExport = $vatExportSettings['ENABLE'] == 'Y';
if ($vatExport)
{
	if ($vatExportSettings['BASE_VAT'] == '')
	{
		$vatExport = false;
	}
	else
	{
		if ($vatExportSettings['BASE_VAT'] != '-')
			$vatList[0] = 'NO_VAT';

		$iterator = Catalog\VatTable::getList(array(
			'select' => array('ID', 'RATE'),
			'filter' => array(),
			'order' => array('ID' => 'ASC')
		));
		while ($row = $iterator->fetch())
		{
			$row['ID'] = (int)$row['ID'];
			$row['RATE'] = (float)$row['RATE'];
			$index = $row['RATE'].'%';
			$vatList[$row['ID']] = 'VAT_'.$row['RATE'];
		}
		unset($index, $row, $iterator);
	}
}

// получение объекта для вычисления налога
$param['vatEnable'] = $vatExport;
$param['vatBase'] = $vatExportSettings['BASE_VAT'];
$param['vatList'] = $vatList;
$sbermmTax = $sbermm->getTaxObject($param);

$itemOptions = array(
	'PROTOCOL' => $usedProtocol,
	'CHARSET' => $exportCharset,
	'SITE_NAME' => $site['SERVER_NAME'],
	'SITE_DIR' => $site['DIR'],
    'PICTURE' => $pictureField,
	'DESCRIPTION' => $descrField,
	'MAX_DESCRIPTION_LENGTH' => 3000
);

$sectionFileName = '';
//$itemFileName = '';
if (strlen($SETUP_FILE_NAME) <= 0)
{
	$arRunErrors[] = GetMessage("CATI_NO_SAVE_FILE");
}
elseif (preg_match(BX_CATALOG_FILENAME_REG,$SETUP_FILE_NAME))
{
	$arRunErrors[] = GetMessage("CES_ERROR_BAD_EXPORT_FILENAME");
}
else
{
	$SETUP_FILE_NAME = Rel2Abs("/", $SETUP_FILE_NAME);
}
if (empty($arRunErrors))
{
	$sectionFileName = $SETUP_FILE_NAME.'_import_'.date('Ymd_Hi').'.php';
	//$itemFileName = $SETUP_FILE_NAME.'_items';
}

$itemsFile = null;

$BASE_CURRENCY = Currency\CurrencyManager::getBaseCurrency();

if ($firstStep)
{
	if (empty($arRunErrors))
	{
		CheckDirPath($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME);

		if (!$fp = @fopen($_SERVER["DOCUMENT_ROOT"].$sectionFileName, "wb"))
		{
			$arRunErrors[] = str_replace('#FILE#', $sectionFileName, GetMessage('YANDEX_ERR_FILE_OPEN_WRITING'));
		}
		else
		{
			if (!@fwrite($fp, '<? $disableReferers = '.($disableReferers ? 'true' : 'false').';'."\n"))
			{
				$arRunErrors[] = str_replace('#FILE#', $sectionFileName, GetMessage('YANDEX_ERR_SETUP_FILE_WRITE'));
				@fclose($fp);
			}
			else
			{
				if (!$disableReferers)
				{
					fwrite($fp, 'if (!isset($_GET["referer1"]) || strlen($_GET["referer1"])<=0) $_GET["referer1"] = "sbermm";'."\n");
					fwrite($fp, '$strReferer1 = htmlspecialchars($_GET["referer1"]);'."\n");
					fwrite($fp, 'if (!isset($_GET["referer2"]) || strlen($_GET["referer2"]) <= 0) $_GET["referer2"] = "";'."\n");
					fwrite($fp, '$strReferer2 = htmlspecialchars($_GET["referer2"]);'."\n");
				}
			}
		}
	}

	if (empty($arRunErrors))
	{
		/** @noinspection PhpUndefinedVariableInspection */
		fwrite($fp, 'header("Content-Type: text/xml; charset='.$itemOptions['CHARSET'].'");'."\n");
		fwrite($fp, 'echo "<"."?xml version=\"1.0\" encoding=\"'.$itemOptions['CHARSET'].'\"?".">"?>');
		fwrite($fp, "\n".'<!DOCTYPE yml_catalog SYSTEM "shops.dtd">'."\n");
		fwrite($fp, '<yml_catalog date="'.date("Y-m-d H:i").'">'."\n");
		fwrite($fp, '<shop>'."\n");

		$charsetError = '';

		fwrite($fp,
			'<name>'.Main\Text\Encoding::convertEncoding(
				htmlspecialcharsbx($site['SITE_NAME'], ENT_QUOTES|ENT_XML1),
				LANG_CHARSET,
				$itemOptions['CHARSET'],
				$charsetError).
			"</name>\n"
		);
		fwrite($fp,
			'<company>'.Main\Text\Encoding::convertEncoding(
				htmlspecialcharsbx($site['COMPANY_NAME'], ENT_QUOTES|ENT_XML1),
				LANG_CHARSET,
				$itemOptions['CHARSET'],
				$charsetError).
			"</company>\n"
		);
		fwrite($fp, '<url>'.$usedProtocol.htmlspecialcharsbx($site['SERVER_NAME'])."</url>\n");
        fwrite($fp, $sbermmShipment->getHeaderXml());

		$strTmp = '<currencies>'."\n";

		$RUR = 'RUB';
		$currencyIterator = Currency\CurrencyTable::getList(array(
			'select' => array('CURRENCY'),
			'filter' => array('=CURRENCY' => 'RUR')
		));
		if ($currency = $currencyIterator->fetch())
			$RUR = 'RUR';
		unset($currency, $currencyIterator);

		$arCurrencyAllowed = array($RUR, 'USD', 'EUR', 'UAH', 'BYR', 'BYN', 'KZT');

		if (is_array($XML_DATA['CURRENCY']))
		{
			foreach ($XML_DATA['CURRENCY'] as $CURRENCY => $arCurData)
			{
				if (in_array($CURRENCY, $arCurrencyAllowed))
				{
					$strTmp .= '<currency id="'.$CURRENCY.'"'
						.' rate="'.($arCurData['rate'] == 'SITE' ? CCurrencyRates::ConvertCurrency(1, $CURRENCY, $RUR) : $arCurData['rate']).'"'
						.($arCurData['plus'] > 0 ? ' plus="'.(int)$arCurData['plus'].'"' : '')
						." />\n";
				}
			}
			unset($CURRENCY, $arCurData);
		}
		else
		{
			$currencyIterator = Currency\CurrencyTable::getList(array(
				'select' => array('CURRENCY', 'SORT'),
				'filter' => array('@CURRENCY' => $arCurrencyAllowed),
				'order' => array('SORT' => 'ASC', 'CURRENCY' => 'ASC')
			));
			while ($currency = $currencyIterator->fetch())
				$strTmp .= '<currency id="'.$currency['CURRENCY'].'" rate="'.(CCurrencyRates::ConvertCurrency(1, $currency['CURRENCY'], $RUR)).'" />'."\n";
			unset($currency, $currencyIterator);
		}
		$strTmp .= "</currencies>\n";

		fwrite($fp, $strTmp);
		unset($strTmp);

		//*****************************************//

		$intMaxSectionID = 0;

		$strTmpCat = '';
		$strTmpOff = '';

		$arSectionIDs = array();
		$arAvailGroups = array();
		if (!$bAllSections)
		{
			for ($i = 0, $intSectionsCount = count($arSections); $i < $intSectionsCount; $i++)
			{
				$sectionIterator = CIBlockSection::GetNavChain($IBLOCK_ID, $arSections[$i], array('ID', 'IBLOCK_SECTION_ID', 'NAME', 'LEFT_MARGIN', 'RIGHT_MARGIN'));
				$curLEFT_MARGIN = 0;
				$curRIGHT_MARGIN = 0;
				while ($section = $sectionIterator->Fetch())
				{
					$section['ID'] = (int)$section['ID'];
					$section['IBLOCK_SECTION_ID'] = (int)$section['IBLOCK_SECTION_ID'];
					if ($arSections[$i] == $section['ID'])
					{
						$curLEFT_MARGIN = (int)$section['LEFT_MARGIN'];
						$curRIGHT_MARGIN = (int)$section['RIGHT_MARGIN'];
						$arSectionIDs[$section['ID']] = $section['ID'];
					}
					$arAvailGroups[$section['ID']] = array(
						'ID' => $section['ID'],
						'IBLOCK_SECTION_ID' => $section['IBLOCK_SECTION_ID'],
						'NAME' => $section['NAME']
					);
					if ($intMaxSectionID < $section['ID'])
						$intMaxSectionID = $section['ID'];
				}
				unset($section, $sectionIterator);

				$filter = array(
					'IBLOCK_ID' => $IBLOCK_ID,
					'>LEFT_MARGIN' => $curLEFT_MARGIN,
					'<RIGHT_MARGIN' => $curRIGHT_MARGIN,
					'GLOBAL_ACTIVE' => 'Y'
				);
				$sectionIterator = CIBlockSection::GetList(
					array('LEFT_MARGIN' => 'ASC'),
					array_merge($filter, $permissionFilter),
					false,
					array('ID', 'IBLOCK_SECTION_ID', 'NAME')
				);
				while ($section = $sectionIterator->Fetch())
				{
					$section['ID'] = (int)$section['ID'];
					$section['IBLOCK_SECTION_ID'] = (int)$section['IBLOCK_SECTION_ID'];
					$arAvailGroups[$section['ID']] = $section;
					if ($intMaxSectionID < $section['ID'])
						$intMaxSectionID = $section['ID'];
				}
				unset($section, $sectionIterator);
			}
		}
		else
		{
			$filter = array(
				'IBLOCK_ID' => $IBLOCK_ID,
				'GLOBAL_ACTIVE' => 'Y'
			);
			$sectionIterator = CIBlockSection::GetList(
				array('LEFT_MARGIN' => 'ASC'),
				array_merge($filter, $permissionFilter),
				false,
				array('ID', 'IBLOCK_SECTION_ID', 'NAME')
			);
			while ($section = $sectionIterator->Fetch())
			{
				$section['ID'] = (int)$section['ID'];
				$section['IBLOCK_SECTION_ID'] = (int)$section['IBLOCK_SECTION_ID'];
				$arAvailGroups[$section['ID']] = $section;
				$arSectionIDs[$section['ID']] = $section['ID'];
				if ($intMaxSectionID < $section['ID'])
					$intMaxSectionID = $section['ID'];
			}
			unset($section, $sectionIterator);
		}

		foreach ($arAvailGroups as $value)
			$strTmpCat .= '<category id="'.$value['ID'].'"'.($value['IBLOCK_SECTION_ID'] > 0 ? ' parentId="'.$value['IBLOCK_SECTION_ID'].'"' : '').'>'.$sbermm->text2xml($value['NAME'], $itemOptions).'</category>'."\n";
		unset($value);

		$intMaxSectionID += 100000000;

		fwrite($fp, "<categories>\n");
		fwrite($fp, $strTmpCat);
        fwrite($fp, "</categories>\n");
        fwrite($fp, "<offers>\n");
		fclose($fp);
		unset($strTmpCat);

		$boolNeedRootSection = false;

        $itemsFile = @fopen($_SERVER["DOCUMENT_ROOT"].$sectionFileName, 'ab');
		//$itemsFile = @fopen($_SERVER["DOCUMENT_ROOT"].$itemFileName, 'wb');
		if (!$itemsFile)
		{
			$arRunErrors[] = str_replace('#FILE#', $sectionFileName, GetMessage('YANDEX_ERR_FILE_OPEN_WRITING'));
            //$arRunErrors[] = str_replace('#FILE#', $itemFileName, GetMessage('YANDEX_ERR_FILE_OPEN_WRITING'));
		}
	}
}
else
{
    $itemsFile = @fopen($_SERVER["DOCUMENT_ROOT"].$sectionFileName, 'ab');
	//$itemsFile = @fopen($_SERVER["DOCUMENT_ROOT"].$itemFileName, 'ab');
	if (!$itemsFile)
	{
		$arRunErrors[] = str_replace('#FILE#', $sectionFileName, GetMessage('YANDEX_ERR_FILE_OPEN_WRITING'));
        //$arRunErrors[] = str_replace('#FILE#', $itemFileName, GetMessage('YANDEX_ERR_FILE_OPEN_WRITING'));
	}
}
unset($arSections);

if (empty($arRunErrors))
{
	//*****************************************//
	$saleDiscountOnly = false;
	$calculationConfig = [
		'CURRENCY' => $BASE_CURRENCY,
		'USE_DISCOUNTS' => true,
		'RESULT_WITH_VAT' => true,
		'RESULT_MODE' => Catalog\Product\Price\Calculation::RESULT_MODE_COMPONENT
	];
	if ($saleIncluded)
	{
		$saleDiscountOnly = (string)Main\Config\Option::get('sale', 'use_sale_discount_only') == 'Y';
		if ($saleDiscountOnly)
			$calculationConfig['PRECISION'] = (int)Main\Config\Option::get('sale', 'value_precision');
	}
	Catalog\Product\Price\Calculation::setConfig($calculationConfig);
	unset($calculationConfig);

	$needDiscountCache = \CIBlockPriceTools::SetCatalogDiscountCache($priceTypeList, array(2), $site['LID']);

	// wbs24 замена CATALOG_QUANTITY на QUANTITY
	$itemFields = array(
		'ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'NAME',
		'PREVIEW_PICTURE', $descrField, $descrField.'_TYPE', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL',
		'AVAILABLE', 'TYPE', 'VAT_ID', 'VAT_INCLUDED',
			"CATALOG_AVAILABLE", "QUANTITY"
	);
	$offerFields = array(
		'ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'NAME',
		'PREVIEW_PICTURE', $descrField, $descrField.'_TYPE', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL',
		'AVAILABLE', 'TYPE', 'VAT_ID', 'VAT_INCLUDED',
			"CATALOG_AVAILABLE", "QUANTITY", "CATALOG_TYPE"
	);

	$allowedTypes = array();
	switch ($arCatalog['CATALOG_TYPE'])
	{
		case CCatalogSku::TYPE_CATALOG:
			$allowedTypes = array(
				Catalog\ProductTable::TYPE_PRODUCT => true,
				Catalog\ProductTable::TYPE_SET => true
			);
			break;
		case CCatalogSku::TYPE_OFFERS:
			$allowedTypes = array(
				Catalog\ProductTable::TYPE_OFFER => true
			);
			break;
		case CCatalogSku::TYPE_FULL:
			$allowedTypes = array(
				Catalog\ProductTable::TYPE_PRODUCT => true,
				Catalog\ProductTable::TYPE_SET => true,
				Catalog\ProductTable::TYPE_SKU => true
			);
			break;
		case CCatalogSku::TYPE_PRODUCT:
			$allowedTypes = array(
				Catalog\ProductTable::TYPE_SKU => true
			);
			break;
	}

	$filter = array('IBLOCK_ID' => $IBLOCK_ID);
	if (!$bAllSections && !empty($arSectionIDs))
	{
		$filter['INCLUDE_SUBSECTIONS'] = 'Y';
		$filter['SECTION_ID'] = $arSectionIDs;
	}
	$filter['ACTIVE'] = 'Y';
	$filter['ACTIVE_DATE'] = 'Y';
	if ($filterAvailable)
		$filter['AVAILABLE'] = 'Y';
	$filter = array_merge($filter, $permissionFilter);

	$offersFilter = array('ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y');
	if ($filterAvailable)
		$offersFilter['AVAILABLE'] = 'Y';
	$offersFilter = array_merge($offersFilter, $permissionFilter);

	if (isset($allowedTypes[Catalog\ProductTable::TYPE_SKU]))
	{
		if ($arSKUExport['SKU_EXPORT_COND'] == YANDEX_SKU_EXPORT_PROP)
		{
			$strExportKey = '';
			$mxValues = false;
			if ($arSKUExport['SKU_PROP_COND']['COND'] == 'NONZERO' || $arSKUExport['SKU_PROP_COND']['COND'] == 'NONEQUAL')
				$strExportKey = '!';
			$strExportKey .= 'PROPERTY_'.$arSKUExport['SKU_PROP_COND']['PROP_ID'];
			if ($arSKUExport['SKU_PROP_COND']['COND'] == 'EQUAL' || $arSKUExport['SKU_PROP_COND']['COND'] == 'NONEQUAL')
				$mxValues = $arSKUExport['SKU_PROP_COND']['VALUES'];
			$offersFilter[$strExportKey] = $mxValues;
		}
	}

	do
	{
		if (isset($CUR_ELEMENT_ID) && $CUR_ELEMENT_ID > 0)
			$filter['>ID'] = $CUR_ELEMENT_ID;

		$existItems = false;

		$itemIdsList = array();
		$items = array();

		$skuIdsList = array();
		$simpleIdsList = array();
		//$filter[">QUANTITY"] = 0; // wbs24 выводить пустые тоже

		$iterator = CIBlockElement::GetList(
			array('ID' => 'ASC'),
			$filter,
			false,
			$navParams,
			$itemFields
		);
		while ($row = $iterator->Fetch())
		{
            if ($sbermm->isLimitOfElementsExpired()) break;

			$finalExport = false; // items exist
			$existItems = true;

			$id = (int)$row['ID'];
			$CUR_ELEMENT_ID = $id;

			$row['TYPE'] = (int)$row['TYPE'];
			$elementType = $row['TYPE'];
			if (!isset($allowedTypes[$elementType]))
				continue;

			$row['SECTIONS'] = array();
			if ($needProperties || $needDiscountCache)
				$row['PROPERTIES'] = array();
			$row['PRICES'] = array();

			$items[$id] = $row;
			$itemIdsList[$id] = $id;

			if ($elementType == Catalog\ProductTable::TYPE_SKU)
				$skuIdsList[$id] = $id;
			else
				$simpleIdsList[$id] = $id;
		}
		unset($row, $iterator);

		if (!empty($items))
		{
			$sbermm->prepareItems($items, array(), $itemOptions);

			foreach (array_chunk($itemIdsList, 500) as $pageIds)
			{
				$iterator = Iblock\SectionElementTable::getList(array(
					'select' => array('IBLOCK_ELEMENT_ID', 'IBLOCK_SECTION_ID'),
					'filter' => array('@IBLOCK_ELEMENT_ID' => $pageIds, '==ADDITIONAL_PROPERTY_ID' => null),
					'order' => array('IBLOCK_ELEMENT_ID' => 'ASC')
				));
				while ($row = $iterator->fetch())
				{
					$id = (int)$row['IBLOCK_ELEMENT_ID'];
					$sectionId = (int)$row['IBLOCK_SECTION_ID'];
					$items[$id]['SECTIONS'][$sectionId] = $sectionId;
					unset($sectionId, $id);
				}
				unset($row, $iterator);
			}
			unset($pageIds);

			if ($needProperties || $needDiscountCache)
			{
				if (!empty($propertyIdList))
				{
					\CIBlockElement::GetPropertyValuesArray(
						$items,
						$IBLOCK_ID,
						array(
							'ID' => $itemIdsList,
							'IBLOCK_ID' => $IBLOCK_ID
						),
						array('ID' => $propertyIdList),
						array('USE_PROPERTY_ID' => 'Y', 'PROPERTY_FIELDS' => $propertyFields)
					);
				}

				if ($needDiscountCache)
				{
					foreach ($itemIdsList as $id)
						\CCatalogDiscount::SetProductPropertiesCache($id, $items[$id]['PROPERTIES']);
					unset($id);
				}

				if (!$needProperties)
				{
					foreach ($itemIdsList as $id)
						$items[$id]['PROPERTIES'] = array();
					unset($id);
				}
				elseif (!$sbermm->checkNeedProperties()) // удалить свойства, только если они не нужны в доп. классах
				{
					foreach ($itemIdsList as $id)
					{
						if (empty($items[$id]['PROPERTIES']))
							continue;
						foreach (array_keys($items[$id]['PROPERTIES']) as $index)
						{
							$propertyId = $items[$id]['PROPERTIES'][$index]['ID'];
							if (!isset($yandexNeedPropertyIds[$propertyId]))
								unset($items[$id]['PROPERTIES'][$index]);
						}
						unset($propertyId, $index);
					}
					unset($id);
				}
			}

			if ($needDiscountCache)
			{
				\CCatalogDiscount::SetProductSectionsCache($itemIdsList);
				\CCatalogDiscount::SetDiscountProductCache($itemIdsList, array('IBLOCK_ID' => $IBLOCK_ID, 'GET_BY_ID' => 'Y'));
			}

			if (!empty($skuIdsList))
			{
				$offerPropertyFilter = array();
				if ($needProperties || $needDiscountCache)
				{
					if (!empty($propertyIdList))
						$offerPropertyFilter = array('ID' => $propertyIdList);
				}

				$offers = \CCatalogSku::getOffersList(
					$skuIdsList,
					$IBLOCK_ID,
					$offersFilter,
					$offerFields,
					$offerPropertyFilter,
					array('USE_PROPERTY_ID' => 'Y', 'PROPERTY_FIELDS' => $propertyFields)
				);
				unset($offerPropertyFilter);

				if (!empty($offers))
				{
					$offerLinks = array();
					$offerIdsList = array();
					$parentsUrl = array();
					foreach (array_keys($offers) as $productId)
					{
						unset($skuIdsList[$productId]);
						$items[$productId]['OFFERS'] = array();
						$parentsUrl[$productId] = $items[$productId]['DETAIL_PAGE_URL'];
						foreach (array_keys($offers[$productId]) as $offerId)
						{
							$productOffer = $offers[$productId][$offerId];
							$productOffer['VAT_ID'] = (int)$productOffer['VAT_ID'];
							//if ($productOffer['VAT_ID'] == 0)
							//	$productOffer['VAT_ID'] = $offersCatalog['VAT_ID'];

							$productOffer['PRICES'] = array();
							if ($needDiscountCache)
								\CCatalogDiscount::SetProductPropertiesCache($offerId, $productOffer['PROPERTIES']);
							if (!$needProperties)
							{
								$productOffer['PROPERTIES'] = array();
							}
							else
							{
								if (!empty($productOffer['PROPERTIES']))
								{
									foreach (array_keys($productOffer['PROPERTIES']) as $index)
									{
										$propertyId = $productOffer['PROPERTIES'][$index]['ID'];
										if (!isset($yandexNeedPropertyIds[$propertyId]))
											unset($productOffer['PROPERTIES'][$index]);
									}
									unset($propertyId, $index);
								}
							}
							$items[$productId]['OFFERS'][$offerId] = $productOffer;
							unset($productOffer);

							$offerLinks[$offerId] = &$items[$productId]['OFFERS'][$offerId];
							$offerIdsList[$offerId] = $offerId;
						}
						unset($offerId);
					}
					if (!empty($offerIdsList))
					{
						$sbermm->prepareItems($offerLinks, $parentsUrl, $itemOptions);

						foreach (array_chunk($offerIdsList, 500) as $pageIds)
						{
							if ($needDiscountCache)
							{
								\CCatalogDiscount::SetProductSectionsCache($pageIds);
								\CCatalogDiscount::SetDiscountProductCache(
									$pageIds,
									array('IBLOCK_ID' => $arCatalog['IBLOCK_ID'], 'GET_BY_ID' => 'Y')
								);
							}

							// load vat cache
							//$vatList = CCatalogProduct::GetVATDataByIDList($pageIds);
							//unset($vatList);

							$priceFilter = [
								'@PRODUCT_ID' => $pageIds,
								[
									'LOGIC' => 'OR',
									'<=QUANTITY_FROM' => 1,
									'=QUANTITY_FROM' => null
								],
								[
									'LOGIC' => 'OR',
									'>=QUANTITY_TO' => 1,
									'=QUANTITY_TO' => null
								]
							];
							if ($selectedPriceType > 0)
								$priceFilter['=CATALOG_GROUP_ID'] = $selectedPriceType;
							else
								$priceFilter['@CATALOG_GROUP_ID'] = $priceTypeList;

							$iterator = Catalog\PriceTable::getList([
								'select' => ['ID', 'PRODUCT_ID', 'CATALOG_GROUP_ID', 'PRICE', 'CURRENCY'],
								'filter' => $priceFilter
							]);

							while ($price = $iterator->fetch())
							{
								$id = (int)$price['PRODUCT_ID'];
								$priceTypeId = (int)$price['CATALOG_GROUP_ID'];
								$offerLinks[$id]['PRICES'][$priceTypeId] = $price;
								unset($priceTypeId, $id);
							}
							unset($price, $iterator);

							if ($saleDiscountOnly)
							{
								Catalog\Discount\DiscountManager::preloadPriceData(
									$pageIds,
									($selectedPriceType > 0 ? [$selectedPriceType] : $priceTypeList)
								);
							}
						}
						unset($pageIds);
					}
					unset($parentsUrl, $offerIdsList, $offerLinks);
				}
				unset($offers);

				if (!empty($skuIdsList))
				{
					foreach ($skuIdsList as $id)
					{
						unset($items[$id]);
						unset($itemIdsList[$id]);
					}
					unset($id);
				}
			}

			if (!empty($simpleIdsList))
			{
				foreach (array_chunk($simpleIdsList, 500) as $pageIds)
				{
					// load vat cache
					//$vatList = CCatalogProduct::GetVATDataByIDList($pageIds);
					//unset($vatList);

					$priceFilter = [
						'@PRODUCT_ID' => $pageIds,
						[
							'LOGIC' => 'OR',
							'<=QUANTITY_FROM' => 1,
							'=QUANTITY_FROM' => null
						],
						[
							'LOGIC' => 'OR',
							'>=QUANTITY_TO' => 1,
							'=QUANTITY_TO' => null
						]
					];
					if ($selectedPriceType > 0)
						$priceFilter['=CATALOG_GROUP_ID'] = $selectedPriceType;
					else
						$priceFilter['@CATALOG_GROUP_ID'] = $priceTypeList;

					$iterator = Catalog\PriceTable::getList([
						'select' => ['ID', 'PRODUCT_ID', 'CATALOG_GROUP_ID', 'PRICE', 'CURRENCY'],
						'filter' => $priceFilter
					]);

					while ($price = $iterator->fetch())
					{
						$id = (int)$price['PRODUCT_ID'];
						$priceTypeId = (int)$price['CATALOG_GROUP_ID'];
						$items[$id]['PRICES'][$priceTypeId] = $price;
						unset($priceTypeId, $id);
					}
					unset($price, $iterator);

					if ($saleDiscountOnly)
					{
						Catalog\Discount\DiscountManager::preloadPriceData(
							$pageIds,
							($selectedPriceType > 0 ? [$selectedPriceType] : $priceTypeList)
						);
					}
				}
				unset($pageIds);
			}
		}

		$itemsContent = '';
		if (!empty($items))
		{
			foreach ($itemIdsList as $id)
			{
				$CUR_ELEMENT_ID = $id;

				$row = $items[$id];

				if (!empty($row['SECTIONS']))
				{
					foreach ($row['SECTIONS'] as $sectionId)
					{
						if (!isset($arAvailGroups[$sectionId]))
							continue;
						$row['CATEGORY_ID'] = $sectionId;
					}
					unset($sectionId);
				}
                /* else
                {
                    $boolNeedRootSection = true;
                    $row['CATEGORY_ID'] = $intMaxSectionID;
                } */
				if (!isset($row['CATEGORY_ID']))
					continue;

				if ($row['TYPE'] == Catalog\ProductTable::TYPE_SKU && !empty($row['OFFERS']))
				{
					$minOfferId = null;
					$minOfferPrice = null;

					foreach (array_keys($row['OFFERS']) as $offerId)
					{
						if (empty($row['OFFERS'][$offerId]['PRICES']) && !$priceFromProps)
						{
							unset($row['OFFERS'][$offerId]);
							continue;
						}

						$fullPrice = 0;
						$minPrice = 0;
						$minPriceCurrency = '';

                        if ($priceFromProps) {
                            $calculatePrice = $sbermmPrice->getPriceFromProps(
                                $row['OFFERS'][$offerId],
                                $commonFields
                            );
                        } else {
                            $calculatePrice = CCatalogProduct::GetOptimalPrice(
                                $row['OFFERS'][$offerId]['ID'],
                                1,
                                array(2),
                                'N',
                                $row['OFFERS'][$offerId]['PRICES'],
                                $site['LID'],
                                array()
                            );
                        }

						if (!empty($calculatePrice))
						{
							$minPrice = $calculatePrice['RESULT_PRICE']['DISCOUNT_PRICE'];
							$fullPrice = $calculatePrice['RESULT_PRICE']['BASE_PRICE'];
							$minPriceCurrency = $calculatePrice['RESULT_PRICE']['CURRENCY'];
						}
						unset($calculatePrice);
						if ($minPrice <= 0)
						{
							unset($row['OFFERS'][$offerId]);
							continue;
						}
						$row['OFFERS'][$offerId]['RESULT_PRICE'] = array(
							'MIN_PRICE' => $minPrice,
							'FULL_PRICE' => $fullPrice,
							'CURRENCY' => $minPriceCurrency
						);
						if ($minOfferPrice === null || $minOfferPrice > $minPrice)
						{
							$minOfferId = $offerId;
							$minOfferPrice = $minPrice;
						}
					}
					unset($offerId);

					if ($arSKUExport['SKU_EXPORT_COND'] == YANDEX_SKU_EXPORT_MIN_PRICE)
					{
						if ($minOfferId === null)
							$row['OFFERS'] = array();
						else
							$row['OFFERS'] = array($minOfferId => $row['OFFERS'][$minOfferId]);
					}
					if (empty($row['OFFERS']))
						continue;

					foreach ($row['OFFERS'] as $offer)
					{
						$minPrice = $offer['RESULT_PRICE']['MIN_PRICE'];
						$fullPrice = $offer['RESULT_PRICE']['FULL_PRICE'];

                        $sbermmPrice->setProperties($offer['PROPERTIES'] ?? [], 'offer', $commonFields, $row['PROPERTIES'] ?? []);
                        $price = $sbermmPrice->getPrice($minPrice, $fullPrice);
                        $oldPrice = $sbermmPrice->getOldPrice($price, $fullPrice);
                        if ($price <= 0) continue;
                        $allowShow = $sbermmLimitations->verifyElementShowing($offer, [
                            'minPrice' => $minPrice,
                            'fullPrice' => $fullPrice,
                            'price' => $price,
                            'oldPrice' => $oldPrice,
                        ]);
                        if ($allowShow) {
                            $offer['SECTIONS'] = $row['SECTIONS']; // добавить разделы товара в ТП
                            $allowShow = $sbermmFilter->verifyElementShowing($offer, $row);
                        }
						if (!$allowShow) continue;

                        $offerId = $sbermm->getElementId($offer, $SET_OFFER_ID);
						$available = ' available="'.($offer['AVAILABLE'] == 'Y' ? 'true' : 'false').'"';
						$itemsContent .= '<offer id="'.$offerId.'"'.$productFormat.$available.'>'."\n";
						//$itemsContent .= '<delivery>true</delivery>'."\n";
						unset($available);

						$referer = '';
						if (!$disableReferers)
							$referer = $offerUrlConfig['REFERRER_SEPARATOR'].'r1=<?=$strReferer1; ?>&amp;r2=<?=$strReferer2; ?>';

                        $url = ($offerUrlConfig['USE_DOMAIN'] ? $usedProtocol.$site['SERVER_NAME'] : '').htmlspecialcharsbx($offer['DETAIL_PAGE_URL']).$referer;
                        $customUrl = $sbermm->getUrl($offer, $XML_DATA['COMMON_FIELDS']);
                        $url = $customUrl ?: $url;
						$itemsContent .= "<url>".$url."</url>\n";
						unset($referer);

						$itemsContent .= "<price>".$price."</price>\n";
						if ($oldPrice) $itemsContent .= "<oldprice>".$oldPrice."</oldprice>\n";
						//$itemsContent .= "<currencyId>".$offer['RESULT_PRICE']['CURRENCY']."</currencyId>\n";

                        $vat = $sbermmTax->getVat($offer);
                        if ($vat) $itemsContent .= "<vat>".$vat."</vat>\n";

                        $itemsContent .= $sbermmShipment->getXml($row, $fields);

						$itemsContent .= "<categoryId>".$row['CATEGORY_ID']."</categoryId>\n";

						$picture = (!empty($offer['PICTURE']) ? $offer['PICTURE'] : $row['PICTURE']);
						if (!empty($picture))
							$itemsContent .= "<picture>".$picture."</picture>\n";
						unset($picture);

						$y = 0;
						foreach ($arYandexFields as $key)
						{
							switch ($key)
							{
								case 'name':
									if ($yandexFormat == 'vendor.model' || $yandexFormat == 'artist.title')
										continue;

                                    $offerName = $sbermm->getName($offer, $XML_DATA['COMMON_FIELDS'], $row['PROPERTIES'] ?? []);
									$itemsContent .= "<name>".$sbermm->text2xml($offerName, $itemOptions)."</name>\n";
									break;
								case 'description':
									$itemsContent .= "<description>".
										($offer['DESCRIPTION'] !== '' ? $offer['DESCRIPTION'] : $row['DESCRIPTION']).
										"</description>\n";
									break;
								case 'param':
									if ($parametricFieldsExist)
									{
										foreach ($parametricFields as $paramKey => $prop_id)
										{
											$value = $sbermm->getValue(
												$offer,
												'PARAM_'.$paramKey,
												$prop_id,
												$arProperties,
												$arUserTypeFormat,
												$itemOptions
											);
											if ($value == '')
											{
												$value = $sbermm->getValue(
													$row,
													'PARAM_'.$paramKey,
													$prop_id,
													$arProperties,
													$arUserTypeFormat,
													$itemOptions
												);
											}
											if ($value != '')
												$itemsContent .= $value."\n";
											unset($value);
										}
										unset($paramKey, $prop_id);
									}
									break;
								case 'model':
								case 'title':
									if (!$fieldsExist || !isset($fields[$key]))
									{
										if (
											$key == 'model' && $yandexFormat == 'vendor.model'
											||
											$key == 'title' && $yandexFormat == 'artist.title'
										)
											$itemsContent .= "<".$key.">".$sbermm->text2xml($offer['NAME'], $itemOptions)."</".$key.">\n";
									}
									else
									{
										$value = $sbermm->getValue(
											$offer,
											$key,
											$fields[$key],
											$arProperties,
											$arUserTypeFormat,
											$itemOptions
										);
										if ($value == '')
										{
											$value = $sbermm->getValue(
												$row,
												$key,
												$fields[$key],
												$arProperties,
												$arUserTypeFormat,
												$itemOptions
											);
										}
										if ($value != '')
											$itemsContent .= $value."\n";
										unset($value);
									}
									break;
								case 'year':
								default:
									if ($key == 'year')
									{
										$y++;
										if ($yandexFormat == 'artist.title')
										{
											if ($y == 1)
												continue;
										}
										else
										{
											if ($y > 1)
												continue;
										}
									}
									if ($fieldsExist && isset($fields[$key]))
									{
										$value = $sbermm->getValue(
											$offer,
											$key,
											$fields[$key],
											$arProperties,
											$arUserTypeFormat,
											$itemOptions
										);
										if ($value == '')
										{
											$value = $sbermm->getValue(
												$row,
												$key,
												$fields[$key],
												$arProperties,
												$arUserTypeFormat,
												$itemOptions
											);
										}
										if ($value != '')
											$itemsContent .= $value."\n";
										unset($value);
									}
							}
						}

                        // wbs24 количество (остаток)
                        $sbermmWarehouse->setProperties($offer['PROPERTIES'], 'offer', $commonFields, $row['PROPERTIES']);
                        $itemsContent .= $sbermmWarehouse->getXml($offer);

						$itemsContent .= '</offer>'."\n";
					}
					unset($offer);
				}
				elseif (isset($simpleIdsList[$id]) && !empty($row['PRICES']))
				{
					$row['VAT_ID'] = (int)$row['VAT_ID'];
					//if ($row['VAT_ID'] == 0)
					//	$row['VAT_ID'] = $arCatalog['VAT_ID'];

					$fullPrice = 0;
					$minPrice = 0;
					$minPriceCurrency = '';

                    if ($priceFromProps) {
                        $calculatePrice = $sbermmPrice->getPriceFromProps(
                            $row,
                            $commonFields
                        );
                    } else {
                        $calculatePrice = CCatalogProduct::GetOptimalPrice(
                            $row['ID'],
                            1,
                            array(2),
                            'N',
                            $row['PRICES'],
                            $site['LID'],
                            array()
                        );
                    }

					if (!empty($calculatePrice))
					{
						$minPrice = $calculatePrice['RESULT_PRICE']['DISCOUNT_PRICE'];
						$fullPrice = $calculatePrice['RESULT_PRICE']['BASE_PRICE'];
						$minPriceCurrency = $calculatePrice['RESULT_PRICE']['CURRENCY'];
					}
					unset($calculatePrice);

                    $sbermmPrice->setProperties($row['PROPERTIES'] ?? [], 'simpleProduct', $commonFields);
                    $price = $sbermmPrice->getPrice($minPrice, $fullPrice);
                    $oldPrice = $sbermmPrice->getOldPrice($price, $fullPrice);
                    if ($price <= 0) continue;
                    $allowShow = $sbermmLimitations->verifyElementShowing($row, [
                        'minPrice' => $minPrice,
                        'fullPrice' => $fullPrice,
                        'price' => $price,
                        'oldPrice' => $oldPrice,
                    ]);
                    if ($allowShow) $allowShow = $sbermmFilter->verifyElementShowing($row);
                    if (!$allowShow) continue;

                    $offerId = $sbermm->getElementId($row, $SET_ID);
					$available = ' available="'.($row['AVAILABLE'] == 'Y' ? 'true' : 'false').'"';
					$itemsContent .= '<offer id="'.$offerId.'"'.$productFormat.$available.'>'."\n";
					//$itemsContent .= '<delivery>true</delivery>'."\n";

					unset($available);

					$referer = '';
					if (!$disableReferers)
						$referer = $itemUrlConfig['REFERRER_SEPARATOR'].'r1=<?=$strReferer1; ?>&amp;r2=<?=$strReferer2; ?>';

                    $url = ($itemUrlConfig['USE_DOMAIN'] ? $usedProtocol.$site['SERVER_NAME'] : '').htmlspecialcharsbx($row['DETAIL_PAGE_URL']).$referer;
                    $customUrl = $sbermm->getUrl($row, $XML_DATA['COMMON_FIELDS']);
                    $url = $customUrl ?: $url;
					$itemsContent .= "<url>".$url."</url>\n";
					unset($referer);

					$itemsContent .= "<price>".$price."</price>\n";
					if ($oldPrice) $itemsContent .= "<oldprice>".$oldPrice."</oldprice>\n";
                    //$itemsContent .= "<currencyId>".$minPriceCurrency."</currencyId>\n";

                    $vat = $sbermmTax->getVat($row);
                    if ($vat) $itemsContent .= "<vat>".$vat."</vat>\n";

                    $itemsContent .= $sbermmShipment->getXml($row, $fields);

					$itemsContent .= "<categoryId>".$row['CATEGORY_ID']."</categoryId>\n";

					if (!empty($row['PICTURE']))
						$itemsContent .= "<picture>".$row['PICTURE']."</picture>\n";

					$y = 0;
					foreach ($arYandexFields as $key)
					{
						switch ($key)
						{
							case 'name':
								if ($yandexFormat == 'vendor.model' || $yandexFormat == 'artist.title')
									continue;

                                $productName = $sbermm->getName($row, $XML_DATA['COMMON_FIELDS']);
								$itemsContent .= "<name>".$sbermm->text2xml($productName, $itemOptions)."</name>\n";
								break;
							case 'description':
								$itemsContent .= "<description>".$row['DESCRIPTION']."</description>\n";
								break;
							case 'param':
								if ($parametricFieldsExist)
								{
									foreach ($parametricFields as $paramKey => $prop_id)
									{
										$value = $sbermm->getValue(
											$row,
											'PARAM_'.$paramKey,
											$prop_id,
											$arProperties,
											$arUserTypeFormat,
											$itemOptions
										);
										if ($value != '')
											$itemsContent .= $value."\n";
										unset($value);
									}
									unset($paramKey, $prop_id);
								}
								break;
							case 'model':
							case 'title':
								if (!$fieldsExist || !isset($fields[$key]))
								{
									if (
										$key == 'model' && $yandexFormat == 'vendor.model'
										||
										$key == 'title' && $yandexFormat == 'artist.title'
									)
										$itemsContent .= "<".$key.">".$sbermm->text2xml($row['NAME'], $itemOptions)."</".$key.">\n";
								}
								else
								{
									$value = $sbermm->getValue(
										$row,
										$key,
										$fields[$key],
										$arProperties,
										$arUserTypeFormat,
										$itemOptions
									);
									if ($value != '')
										$itemsContent .= $value."\n";
									unset($value);
								}
								break;
							case 'year':
							default:
								if ($key == 'year')
								{
									$y++;
									if ($yandexFormat == 'artist.title')
									{
										if ($y == 1)
											continue;
									}
									else
									{
										if ($y > 1)
											continue;
									}
								}
								if ($fieldsExist && isset($fields[$key]))
								{
									$value = $sbermm->getValue(
										$row,
										$key,
										$fields[$key],
										$arProperties,
										$arUserTypeFormat,
										$itemOptions
									);
									if ($value != '')
										$itemsContent .= $value."\n";
									unset($value);
								}
						}
					}

                    // wbs24 количество (остаток)
                    $sbermmWarehouse->setProperties($row['PROPERTIES'], 'simpleProduct', $commonFields);
                    $itemsContent .= $sbermmWarehouse->getXml($row);

					$itemsContent .= "</offer>\n";
				}

				unset($row);

				if ($MAX_EXECUTION_TIME > 0 && (getmicrotime() - START_EXEC_TIME) >= $MAX_EXECUTION_TIME)
					break;
			}
			unset($id);

			\CCatalogDiscount::ClearDiscountCache(array(
				'PRODUCT' => true,
				'SECTIONS' => true,
				'SECTION_CHAINS' => true,
				'PROPERTIES' => true
			));
			/** @noinspection PhpDeprecationInspection */
			\CCatalogProduct::ClearCache();
		}

		if ($itemsContent !== '')
			fwrite($itemsFile, $itemsContent);
		unset($itemsContent);

		unset($simpleIdsList, $skuIdsList);
		unset($items, $itemIdsList);
	}
	while ($MAX_EXECUTION_TIME == 0 && $existItems);
}

if (empty($arRunErrors))
{
	if (is_resource($itemsFile))
		@fclose($itemsFile);
	unset($itemsFile);
}

if (empty($arRunErrors))
{
	if ($MAX_EXECUTION_TIME == 0)
		$finalExport = true;
	if ($finalExport)
	{
		$process = true;

        /* $content = '';
        if ($boolNeedRootSection)
            $content .= '<category id="'.$intMaxSectionID.'">'.$sbermm->text2xml(GetMessage('YANDEX_ROOT_DIRECTORY'), $itemOptions).'</category>'."\n";
        $content .= "</categories>\n";
        $content .= "<offers>\n";

        if (file_put_contents($_SERVER["DOCUMENT_ROOT"].$sectionFileName, $content, FILE_APPEND) === false)
        {
            $arRunErrors[] = str_replace('#FILE#', $sectionFileName, GetMessage('YANDEX_ERR_SETUP_FILE_WRITE'));
            $process = false;
        }

        // запись items
        if ($process) {
            $process = $sbermm->appendFile(
                $_SERVER["DOCUMENT_ROOT"].$itemFileName,
                $_SERVER["DOCUMENT_ROOT"].$sectionFileName
            );

            if (!$process) {
                $arRunErrors[] = GetMessage('YANDEX_STEP_ERR_DATA_FILE_NOT_READ');
            }
        } */

		if ($process)
		{
			$content = "</offers>\n"."</shop>\n"."</yml_catalog>\n";

			if (file_put_contents($_SERVER["DOCUMENT_ROOT"].$sectionFileName, $content, FILE_APPEND) === false)
			{
				$arRunErrors[] = str_replace('#FILE#', $sectionFileName, GetMessage('YANDEX_ERR_SETUP_FILE_WRITE'));
				$process = false;
			}
		}

		if ($process)
		{
			//unlink($_SERVER["DOCUMENT_ROOT"].$itemFileName);

			if (file_exists($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME))
			{
				if (!unlink($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME))
				{
					$arRunErrors[] = str_replace('#FILE#', $SETUP_FILE_NAME, GetMessage('BX_CATALOG_EXPORT_YANDEX_ERR_UNLINK_FILE'));
					$process = false;
				}
			}
		}

		if ($process)
		{
			//if (!rename($_SERVER["DOCUMENT_ROOT"].$sectionFileName, $_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME))
			if (!$sbermm->updateExportFile($SETUP_FILE_NAME, $sectionFileName))
			{
				$arRunErrors[] = str_replace('#FILE#', $sectionFileName, GetMessage('BX_CATALOG_EXPORT_YANDEX_ERR_UNLINK_FILE'));
			}
		}
		unset($process);
	}
}

CCatalogDiscountSave::Enable();
if ($saleIncluded)
	Sale\DiscountCouponsManager::unFreezeCouponStorage();

if (!empty($arRunErrors)) {
    $strExportErrorMessage = implode('<br />',$arRunErrors);

    if ($strExportErrorMessage) {
        $handle = @fopen($_SERVER['DOCUMENT_ROOT']."/upload/wbs24_sbermmexport_error_log.txt", "a");
        fwrite($handle, $strExportErrorMessage);
        fclose($handle);
    }
}

if ($bTmpUserCreated)
{
	if (isset($USER_TMP))
	{
		$USER = $USER_TMP;
		unset($USER_TMP);
	}
}
