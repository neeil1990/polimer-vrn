<?php

namespace Corsik\YaDelivery\Delivery;

use Bitrix\Currency;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Delivery\Services\Base;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\Shipment;
use Corsik\YaDelivery\Handler;
use Corsik\YaDelivery\Helper;
use Corsik\YaDelivery\Options;

Loc::loadMessages(__FILE__);

class YandexDeliveryProfile extends Base
{
	protected static $isProfile = true;
	protected static ?Base $parent = null;
	protected static $whetherAdminExtraServicesShow = true;
	protected $handlerCode = 'CORSIK_YADELIVERY';
	protected $handler;
	protected $module_id = "corsik.yadelivery";
	protected $module_status;
	protected $personType = false;

	public function __construct(array $initParams)
	{
		parent::__construct($initParams);
		Loader::includeModule('currency');
		Loader::includeModule('sale');
		$this->module_status = Loader::includeSharewareModule($this->module_id);
		$this->parent = Manager::getObjectById($this->parentId);
		$this->serviceType = $initParams['ID'];
		$this->handler = Handler::getInstance();
	}

	public static function getClassTitle()
	{
		return Loc::getMessage("CORSIK_DELIVERY_PROFILE_ZONE_TITLE");
	}

	public static function getClassDescription()
	{
		return Loc::getMessage("CORSIK_DELIVERY_PROFILE_ZONE_DESCRIPTION");
	}

	public static function isProfile()
	{
		return self::$isProfile;
	}

	public static function whetherAdminExtraServicesShow()
	{
		return self::$whetherAdminExtraServicesShow;
	}

	public function isCalculatePriceImmediately()
	{
		return $this->getParentService()->isCalculatePriceImmediately();
	}

	public function getParentService()
	{
		return $this->parent;
	}

	public function getValue($json, $deliveryID)
	{
		if (Helper::isJson($json))
		{
			$data = Helper::JsonDecode($json);
			$deliveryData = $data[$deliveryID];
			if (isset($data[$deliveryID]))
			{
				return $deliveryData['price'];
			}
		}

		return null;
	}

	public function getAddressPropertyData($propertyCollection): array
	{
		$arrAddress = ['ADDRESS_VALUE' => '', 'ADDRESS_ID' => ''];
		$suggestionsAddressID = Options::getOptionByName('enable_location_address', false, $this->personType);
		$addressProperty = $suggestionsAddressID > 0
			? $propertyCollection->getItemByOrderPropertyId($suggestionsAddressID)
			: $propertyCollection->getAddress();
		if ($addressProperty)
		{
			$arrProperty = $addressProperty->getProperty();
			$arrAddress['ADDRESS_VALUE'] = $addressProperty->getValue();
			$arrAddress['ADDRESS_ID'] = $arrProperty['ID'];
		}

		return $arrAddress;
	}

	public function getAdditionalFields($propertyCollection, $addressPropertyID): string
	{
		$additionalFieldsIDs = $this->getAdditionalFieldsIDs();
		if (!empty($additionalFieldsIDs) && $propertyCollection)
		{
			$additionalFieldsSorting = [];
			foreach ($additionalFieldsIDs as $propertyID)
			{
				$isAddressProperty = $addressPropertyID === $propertyID;
				if ($property = $propertyCollection->getItemByOrderPropertyId($propertyID))
				{
					$arrProperty = $property->getProperty();
					$additionalFieldsSorting[$propertyID] = [
						'ID' => $propertyID,
						'NAME' => $property->getName(),
						'VALUE' => $property->getValue(),
						'SORT' => $arrProperty['SORT'],
						'IS_ADDRESS' => $isAddressProperty,
					];
				}
				usort($additionalFieldsSorting, function ($a, $b) {
					return $a['SORT'] <=> $b['SORT'];
				});
			}

			$additionalRender = array_reduce($additionalFieldsSorting, function ($acc, $property) {
				$acc .= "<div class='form-group bx-custom-customer-field'>
                            <label for='custom-property-{$property['ID']}' class='bx-custom-custom-label'>{$property['NAME']}</label>
                            <input name='ORDER_PROP_{$property['ID']}' type='text' id='custom-property-{$property['ID']}'
                                  class='form-control bx-custom-customer-input bx-ios-fix' value='{$property['VALUE']}' data-id='{$property['ID']}'>
                          </div>";

				return $acc;
			}, '');

			$hiddenOption = Options::getOptionByName('hidden_additional_delivery_fields', true, $this->personType);
			if ($hiddenOption === 'Y')
			{
				$hiddenOriginFields = array_reduce($additionalFieldsIDs, function ($acc, $propertyID) {
					$acc .= "[data-property-id-row='$propertyID'] {
                          display: none;
                     }";

					return $acc;
				});
				$additionalRender .= "<style>$hiddenOriginFields</style>";
			}

			return $additionalRender;
		}

		return '';
	}

	protected function getConfigStructure()
	{
		$currency = $this->currency;
		$currencyList = Currency\CurrencyManager::getCurrencyList();
		if (isset($currencyList[$this->currency]))
		{
			$currency = $currencyList[$this->currency];
		}
		unset($currencyList);

		$config = [
			"MAIN" => [
				"TITLE" => Loc::getMessage("CORSIK_DELIVERY_PROFILE_SETTINGS"),
				"DESCRIPTION" => Loc::getMessage("CORSIK_DELIVERY_PROFILE_SETTINGS"),

				"ITEMS" => [
					"CURRENCY" => [
						"TYPE" => "DELIVERY_READ_ONLY",
						"NAME" => Loc::getMessage("SALE_DLVR_HANDL_CONF_CURRENCY"),
						"VALUE" => $this->currency,
						"VALUE_VIEW" => $currency,
					],
					"TYPE_PROMPTS" => [
						"TYPE" => "DELIVERY_READ_ONLY",
						"NAME" => Loc::getMessage("CORSIK_DELIVERY_TYPE_PROMPTS"),
						"VALUE" => Options::getOptionByName('type_prompts'),
					],
					"ADD_ZONE_PRICE" => [
						"TYPE" => 'Y/N',
						"NAME" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_ADD_ZONE_PRICE"),
						"DEFAULT" => "Y",
					],
					"START_PRICE" => [
						"TYPE" => "NUMBER",
						"MIN" => 0,
						"DEFAULT" => 0,
						"NAME" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_START_PRICE"),
					],
					"PERIOD" => [
						"TYPE" => "DELIVERY_PERIOD",
						"NAME" => Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_DLV"),
						"ITEMS" => [
							"FROM" => [
								"TYPE" => "NUMBER",
								"MIN" => 0,
								"DEFAULT" => 0,
								"NAME" => "", //Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_FROM"),
							],
							"TO" => [
								"TYPE" => "NUMBER",
								"MIN" => 0,
								"DEFAULT" => 0,
								"NAME" => "&nbsp;-&nbsp;", //Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_TO"),
							],
							"TYPE" => [
								"TYPE" => "ENUM",
								"OPTIONS" => [
									"MIN" => Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_MIN"),
									"H" => Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_HOUR"),
									"D" => Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_DAY"),
									"M" => Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_MONTH"),
								],
							],
						],
					],
					"DISABLED_DELIVERY_OUT" => [
						"TYPE" => 'Y/N',
						"NAME" => Loc::getMessage("CORSIK_DELIVERY_DISABLED_DELIVERY_OUT"),
						"DEFAULT" => "N",
					],
					"POLYGONS_HEADER" => [
						"TYPE" => "DELIVERY_SECTION",
						"NAME" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_REQUIRED"),
					],
					"WAREHOUSES" => [
						"TYPE" => "ENUM",
						"NAME" => Loc::getMessage("CORSIK_DELIVERY_WAREHOUSE"),
						"OPTIONS" => $this->handler->getDataToSelect('warehouses'),
						"REQUIRED" => true,
					],
					"RESTRICTIONS_HEADER" => [
						"TYPE" => "DELIVERY_SECTION",
						"NAME" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_RESTRICTION"),
					],
					"SUGGESTIONS_RESTRICTION" => [
						"TYPE" => "STRING",
						"NAME" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_ADDRESS_RESTRICTION"),
						"DEFAULT" => "",
					],
				],
			],
		];

		return $config;
	}

	/**
	 * @param Shipment $shipment
	 * @return CalculationResult
	 * @throws ArgumentException
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 */
	protected function calculateConcrete(Shipment $shipment)
	{
		if (!Loader::includeModule('corsik.yadelivery'))
		{
			return null;
		}

		$result = new CalculationResult;
		$result->setPeriodDescription($this->getPeriodText());
		$result->setPeriodFrom($this->config["MAIN"]["PERIOD"]["FROM"]);
		$result->setPeriodTo($this->config["MAIN"]["PERIOD"]["TO"]);
		$result->setPeriodType($this->config["MAIN"]["PERIOD"]["TYPE"]);

		if ($this->module_status == 0)
		{
			$result->setDescription(Loc::getMessage("CORSIK_DELIVERY_ERROR_INSTALL"));
		}
		elseif ($this->module_status == 3)
		{
			$result->setDescription(Loc::getMessage("CORSIK_DELIVERY_ERROR_DEMO_EXPIRED"));
		}
		elseif (Options::getOptionByName('enable_delivery') !== 'Y')
		{
			$result->setDescription(Loc::getMessage("CORSIK_DELIVERY_ERROR_DELIVERY_ENABLE"));
		}
		elseif (
			$this->module_status < 3 && $this->module_status > 0
			&& Options::getOptionByName("enable_delivery")
			== 'Y'
		)
		{
			/**
			 * order data
			 */
			$order = $shipment->getCollection()->getOrder();
			$this->personType = $order->getPersonTypeId();
			$deliveryID = $shipment->getDeliveryId();
			$propertyCollection = $order->getPropertyCollection();
			$routePrice = $this->getValue($_COOKIE['yaPrice'], $deliveryID);
			[
				'ADDRESS_VALUE' => $addressPropertyValue,
				'ADDRESS_ID' => $addressPropertyID,
			] = $this->getAddressPropertyData($propertyCollection);

			$deliveryData = Json::encode([
				'personType' => $this->personType,
				'deliveryID' => $deliveryID,
				'address' => $addressPropertyValue,
			]);
			$additionalFields = $this->getAdditionalFields($propertyCollection, $addressPropertyID);
			$notCalculatePrice = !is_numeric($routePrice) || 0 > $routePrice;

			$isShowCalculateButton = $addressPropertyValue
				&& in_array($addressPropertyID, $this->getAdditionalFieldsIDs())
				&& $notCalculatePrice;
			/**
			 * START description
			 */
			$description = "<input id='corsik_yaDelivery__data__$deliveryID' type='hidden' value='$deliveryData' />";
			$description .= "<div class='corsik_yaDelivery__additionalFields'>"
				. $additionalFields
				. "</div><div class='corsik_yaDelivery__actionsButtons'>";

			if ($isShowCalculateButton)
			{
				$description .= Loc::getMessage("CORSIK_DELIVERY_SERVICE_CALCULATE_COAST",
					['#ADDRESS_PROP_ID#' => $addressPropertyID]);
			}

			$description .= Loc::getMessage("CORSIK_DELIVERY_SERVICE_SHOW_MAP", [
				"#DELIVERY_ID#" => $deliveryID,
				"#PERSON_TYPE#" => $this->personType,
			]);
			$description .= "</div>";

			$result->setDescription($description);

			/**
			 * END Description
			 */

			$isStartPrice = $this->config["MAIN"]["START_PRICE"] > 0;

			if ($isStartPrice && !isset($routePrice))
			{
				$result->setDeliveryPrice($this->config["MAIN"]["START_PRICE"]);

				return $result;
			}

			if ($notCalculatePrice)
			{
				$msgError = Loc::getMessage("CORSIK_DELIVERY_EXTRA_ADDRESS_ERROR", [
					"#DELIVERY_ID#" => $deliveryID,
					"#PERSON_TYPE#" => $this->personType,
				]);
				$result->addError(new Error($msgError, 'DELIVERY_CALCULATED'));
			}
			else
			{
				$result->setDeliveryPrice($routePrice);
			}
		}

		return $result;
	}

	/**
	 * @return string Period text.
	 */
	protected function getPeriodText()
	{
		$result = "";

		if (IntVal($this->config["MAIN"]["PERIOD"]["FROM"]) > 0 || IntVal($this->config["MAIN"]["PERIOD"]["TO"]) > 0)
		{
			$result = "";

			if (IntVal($this->config["MAIN"]["PERIOD"]["FROM"]) > 0)
			{
				$result .= " "
					. Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_FROM")
					. " "
					. IntVal($this->config["MAIN"]["PERIOD"]["FROM"]);
			}

			if (IntVal($this->config["MAIN"]["PERIOD"]["TO"]) > 0)
			{
				$result .= " "
					. Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_TO")
					. " "
					. IntVal($this->config["MAIN"]["PERIOD"]["TO"]);
			}

			if ($this->config["MAIN"]["PERIOD"]["TYPE"] == "MIN")
			{
				$result .= " " . Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_MIN") . " ";
			}
			elseif ($this->config["MAIN"]["PERIOD"]["TYPE"] == "H")
			{
				$result .= " " . Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_HOUR") . " ";
			}
			elseif ($this->config["MAIN"]["PERIOD"]["TYPE"] == "M")
			{
				$result .= " " . Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_MONTH") . " ";
			}
			else
			{
				$result .= " " . Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_DAY") . " ";
			}
		}

		return $result;
	}

	private function getAdditionalFieldsIDs(): array
	{
		$additionalFields = trim(Options::getOptionByName('additional_delivery_fields', true, $this->personType));
		if ($additionalFields)
		{
			return explode(',', $additionalFields);
		}

		return [];
	}
}
