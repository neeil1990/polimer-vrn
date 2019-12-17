<?
namespace Zverushki\Microm\Tools\Component;

use Bitrix\Main\Loader,
	Bitrix\Currency\CurrencyTable;

Loader::includeModule('catalog');
Loader::includeModule('iblock');

/**
* class Catalog
*
*
* @package Zverushki\Microm\Tools\Component\Catalog
*/
class CatalogDetail extends Component {

	protected static function __onPrepareComponentParams (&$params) {
		if (array_key_exists('DETAIL_PROPERTY_CODE', $params))
			$params["PROPERTY_CODE"] = array_unique($params["DETAIL_PROPERTY_CODE"]);

		if (array_key_exists('PRODUCT_ID_VARIABLE', $params))
			$params["PRODUCT_ID_VARIABLE"] = $params["PRODUCT_ID_VARIABLE"];

		if (array_key_exists('SECTION_ID_VARIABLE', $params))
			$params["SECTION_ID_VARIABLE"] = $params["SECTION_ID_VARIABLE"];

		if (array_key_exists('DETAIL_CHECK_SECTION_ID_VARIABLE', $params))
			$params["CHECK_SECTION_ID_VARIABLE"] = (isset($params["DETAIL_CHECK_SECTION_ID_VARIABLE"]) ? $params["DETAIL_CHECK_SECTION_ID_VARIABLE"] : '');

		if (array_key_exists('PRODUCT_QUANTITY_VARIABLE', $params))
			$params["PRODUCT_QUANTITY_VARIABLE"] = $params["PRODUCT_QUANTITY_VARIABLE"];

		if (array_key_exists('OFFERS_CART_PROPERTIES', $params))
			$params["OFFERS_CART_PROPERTIES"] = $params["OFFERS_CART_PROPERTIES"];

		if (array_key_exists('DETAIL_OFFERS_FIELD_CODE', $params))
			$params["OFFERS_FIELD_CODE"] = $params["DETAIL_OFFERS_FIELD_CODE"];

		if (array_key_exists('DETAIL_OFFERS_PROPERTY_CODE', $params))
			$params["OFFERS_PROPERTY_CODE"] = $params["DETAIL_OFFERS_PROPERTY_CODE"];

		if (array_key_exists('OFFERS_SORT_FIELD', $params))
			$params["OFFERS_SORT_FIELD"] = $params["OFFERS_SORT_FIELD"];

		if (array_key_exists('OFFERS_SORT_ORDER', $params))
			$params["OFFERS_SORT_ORDER"] = $params["OFFERS_SORT_ORDER"];

		if (array_key_exists('OFFERS_SORT_FIELD2', $params))
			$params["OFFERS_SORT_FIELD2"] = $params["OFFERS_SORT_FIELD2"];

		if (array_key_exists('OFFERS_SORT_ORDER2', $params))
			$params["OFFERS_SORT_ORDER2"] = $params["OFFERS_SORT_ORDER2"];

		if (array_key_exists('OFFERS_SORT_ORDER2', $params))
			$params["SHOW_BASIS_PRICE"] = (isset($params['DETAIL_SHOW_BASIS_PRICE']) ? $params['DETAIL_SHOW_BASIS_PRICE'] : 'Y');

		if (array_key_exists('OFFERS_SORT_ORDER2', $params))
			$params["SET_CANONICAL_URL"] = $params["DETAIL_SET_CANONICAL_URL"];

		if (array_key_exists('OFFERS_SORT_ORDER2', $params))
			$params["SET_CANONICAL_URL"] = $params["DETAIL_SHOW_MAX_QUANTITY"];

		if (array_key_exists('OFFERS_SORT_ORDER2', $params))
			$params["PARTIAL_PRODUCT_PROPERTIES"] = (isset($params["PARTIAL_PRODUCT_PROPERTIES"]) ? $params["PARTIAL_PRODUCT_PROPERTIES"] : '');

		$params["USE_PRICE_COUNT"] = $params["USE_PRICE_COUNT"] == "Y";
		$params["SHOW_DEACTIVATED"] = (isset($params['SHOW_DEACTIVATED']) && 'Y' == $params['SHOW_DEACTIVATED'] ? 'Y' : 'N');


		/**
		 * ‘ормируем массив полей/свойств - не грузим лишнии
		 */
		// $params["PROPERTY_CODE"] = array();
	} // end function __onPrepareComponentParams

	protected static function __component (&$component) {
		$arParams = &$component->arParams;

		if ($arParams['CONVERT_CURRENCY'] == 'Y') {
			if (!Loader::includeModule('currency')) {
				$arParams['CONVERT_CURRENCY'] = 'N';
				$arParams['CURRENCY_ID'] = '';

			} else {
				$arResultModules['currency'] = true;
				$currencyIterator = CurrencyTable::getList(array(
					'select' => array('CURRENCY'),
					'filter' => array('=CURRENCY' => $arParams['CURRENCY_ID'])
				));
				if ($currency = $currencyIterator->fetch()) {
					$arParams['CURRENCY_ID'] = $currency['CURRENCY'];
					$arConvertParams['CURRENCY_ID'] = $currency['CURRENCY'];

				} else {
					$arParams['CONVERT_CURRENCY'] = 'N';
					$arParams['CURRENCY_ID'] = '';
				}

				unset($currency, $currencyIterator);
			}
		}
		if ($arParams["ELEMENT_ID"] <= 0) {
			$findFilter = array(
				"IBLOCK_ID" => $arParams["IBLOCK_ID"],
				"IBLOCK_LID" => SITE_ID,
				"IBLOCK_ACTIVE" => "Y",
				"ACTIVE_DATE" => "Y",
				"CHECK_PERMISSIONS" => "Y",
				"MIN_PERMISSION" => 'R',
			);
			if ($arParams["SHOW_DEACTIVATED"] !== "Y")
				$findFilter["ACTIVE"] = "Y";

			$arParams["ELEMENT_ID"] = \CIBlockFindTools::GetElementID(
				$arParams["ELEMENT_ID"],
				$arParams["ELEMENT_CODE"],
				false,
				false,
				$findFilter
			);
		}

		$arResultPrices = \CIBlockPriceTools::GetCatalogPrices($arParams["IBLOCK_ID"], $arParams["PRICE_CODE"]);

		$arSelect = array(
			"ID",
			"IBLOCK_ID",
			"CODE",
			"XML_ID",
			"NAME",
			"ACTIVE",
			"DATE_ACTIVE_FROM",
			"DATE_ACTIVE_TO",
			"SORT",
			"PREVIEW_TEXT",
			"PREVIEW_TEXT_TYPE",
			"DETAIL_TEXT",
			"DETAIL_TEXT_TYPE",
			"DATE_CREATE",
			"CREATED_BY",
			"TIMESTAMP_X",
			"MODIFIED_BY",
			"TAGS",
			"IBLOCK_SECTION_ID",
			"DETAIL_PAGE_URL",
			"LIST_PAGE_URL",
			"DETAIL_PICTURE",
			"PREVIEW_PICTURE",
			"PROPERTY_*",
			"CATALOG_QUANTITY"
		);

		$arFilter = array(
			"ID" => $arParams["ELEMENT_ID"],
			"IBLOCK_ID" => $arParams["IBLOCK_ID"],
			"IBLOCK_LID" => SITE_ID,
			"IBLOCK_ACTIVE" => "Y",
			"ACTIVE_DATE" => "Y",
			"CHECK_PERMISSIONS" => "Y",
			"MIN_PERMISSION" => 'R',
		);
		if ($arParams["SHOW_DEACTIVATED"] !== "Y")
			$arFilter["ACTIVE"] = "Y";

		if (!$arParams["USE_PRICE_COUNT"])
			foreach ($arResultPrices as $value) {
				if (!$value['CAN_VIEW'] && !$value['CAN_BUY'])
					continue;

				$arSelect[] = $value["SELECT"];
				$arFilter["CATALOG_SHOP_QUANTITY_".$value["ID"]] = $arParams["SHOW_PRICE_COUNT"];
			}

		else {
			foreach ($arResultPrices as &$value) {
				if (!$value['CAN_VIEW'] && !$value['CAN_BUY'])
					continue;

				$arPriceTypeID[] = $value["ID"];
			}

			if (isset($value))
				unset($value);
		}

		$rsElement = \CIBlockElement::GetList(false, $arFilter, false, false, $arSelect);
		$arResult = false;
		if ($obElement = $rsElement->GetNextElement()) {
			$arResult = $obElement->GetFields();

			$arResult["PROPERTIES"] = $obElement->GetProperties();
			$arResult["DISPLAY_PROPERTIES"] = array();

			if (!empty($arParams['PROPERTY_CODE'])) {
				foreach ($arParams['PROPERTY_CODE'] as &$pid) {
					if (!isset($arResult["PROPERTIES"][$pid]))
						continue;

					$prop = &$arResult["PROPERTIES"][$pid];
					$boolArr = is_array($prop["VALUE"]);

					if (($boolArr && !empty($prop["VALUE"])) || (!$boolArr && strlen($prop["VALUE"]) > 0))
						$arResult["DISPLAY_PROPERTIES"][$pid] = \CIBlockFormatProperties::GetDisplayValue($arResult, $prop, "catalog_out");

					unset($prop);
				}
				unset($pid);
			}

			$arResult['CONVERT_CURRENCY'] = $arConvertParams;
			$arResult['MODULES'] = array(
				'iblock' => true,
				'catalog' => false,
				'currency' => false,
				'workflow' => false
			);

			$arResult["CAT_PRICES"] = $arResultPrices;
			$arResult['PRICES_ALLOW'] = $arResultPricesAllow;

			$arResult["PRICE_MATRIX"] = false;
			$arResult["PRICES"] = array();
			$arResult['MIN_PRICE'] = false;

			if ($arParams["USE_PRICE_COUNT"]) {
				if ($bCatalog) {
					$arResult["PRICE_MATRIX"] = CatalogGetPriceTableEx($arResult["ID"], 0, $arPriceTypeID, 'Y', $arConvertParams);

					if (isset($arResult["PRICE_MATRIX"]["COLS"]) && is_array($arResult["PRICE_MATRIX"]["COLS"]))
						foreach($arResult["PRICE_MATRIX"]["COLS"] as $keyColumn=>$arColumn)
							$arResult["PRICE_MATRIX"]["COLS"][$keyColumn]["NAME_LANG"] = htmlspecialcharsbx($arColumn["NAME_LANG"]);
				}

			} else {
				$arResult["PRICES"] = \CIBlockPriceTools::GetItemPrices($arParams["IBLOCK_ID"], $arResult["CAT_PRICES"], $arResult, $arParams['PRICE_VAT_INCLUDE'], $arConvertParams);
				if (!empty($arResult['PRICES']))
					$arResult['MIN_PRICE'] = \CIBlockPriceTools::getMinPriceFromList($arResult['PRICES']);
			}

			$arResult["CAN_BUY"] = \CIBlockPriceTools::CanBuy($arParams["IBLOCK_ID"], $arResult["CAT_PRICES"], $arResult);

			static::__offers($arResult, $arParams);

			if ('Y' == $arParams['CONVERT_CURRENCY']) {
				$currencyList = array();
				if ($arParams["USE_PRICE_COUNT"]) {
					if (!empty($arResult["PRICE_MATRIX"]) && is_array($arResult["PRICE_MATRIX"])) {
						if (isset($arResult["PRICE_MATRIX"]['CURRENCY_LIST']) && is_array($arResult["PRICE_MATRIX"]['CURRENCY_LIST']))
							$currencyList = $arResult["PRICE_MATRIX"]['CURRENCY_LIST'];

						//TODO: remove this code after catalog 15.5.4
						if (!empty($currencyList))
							$currencyList = array_unique($currencyList);
					}

				} else {
					if (!empty($arResult["PRICES"])) {
						foreach ($arResult["PRICES"] as &$arOnePrices)
							if (isset($arOnePrices['ORIG_CURRENCY']))
								$currencyList[$arOnePrices['ORIG_CURRENCY']] = $arOnePrices['ORIG_CURRENCY'];

						unset($arOnePrices);
					}
				}

				if (!empty($arResult["OFFERS"])) {
					foreach ($arResult["OFFERS"] as &$arOneOffer) {
						if (!empty($arOneOffer['PRICES'])) {
							foreach ($arOneOffer['PRICES'] as &$arOnePrices) {
								if (isset($arOnePrices['ORIG_CURRENCY']))
									$currencyList[$arOnePrices['ORIG_CURRENCY']] = $arOnePrices['ORIG_CURRENCY'];
							}
							unset($arOnePrices);
						}
					}

					unset($arOneOffer);
				}
			}
		}

		return $arResult;
	} // end function __component


	private static function __offers (&$arResult, $arParams) {
		$arResult["OFFERS"] = array();

		if ($bCatalog = true && (!empty($arParams["OFFERS_FIELD_CODE"]) || !empty($arParams["OFFERS_PROPERTY_CODE"]))) {
			$offersFilter = array(
				'IBLOCK_ID' => $arParams['IBLOCK_ID'],
				'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE']
			);
			if (!$arParams["USE_PRICE_COUNT"])
				$offersFilter['SHOW_PRICE_COUNT'] = $arParams['SHOW_PRICE_COUNT'];

			$arOffers = \CIBlockPriceTools::GetOffersArray(
				$offersFilter,
				array($arResult["ID"]),
				array(
					$arParams["OFFERS_SORT_FIELD"] => $arParams["OFFERS_SORT_ORDER"],
					$arParams["OFFERS_SORT_FIELD2"] => $arParams["OFFERS_SORT_ORDER2"],
				),
				$arParams["OFFERS_FIELD_CODE"],
				$arParams["OFFERS_PROPERTY_CODE"] = array(),
				$arParams["OFFERS_LIMIT"],
				$arResult["CAT_PRICES"],
				$arParams['PRICE_VAT_INCLUDE'],
				$arResult['CONVERT_CURRENCY']
			);

			foreach ($arOffers as $arOffer)
				$arResult["OFFERS"][] = $arOffer;
		}
	} // end function __offers

} // end class Catalog