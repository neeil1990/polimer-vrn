<?php

namespace Corsik\YaDelivery;

use Bitrix\Main\
{
	Application,
	ArgumentException,
	Config\Option,
	Context,
	Error,
	Event,
	EventResult,
	IO\File,
	IO\FileNotFoundException,
	Loader,
	LoaderException,
	Localization\Loc,
	ObjectPropertyException,
	Page\Asset,
	SiteTable,
	SystemException,
	Web\Json};
use Bitrix\Sale\Delivery\
{
	DeliveryLocationTable,
	Restrictions\ByLocation,
	Services\Manager};
use Bitrix\Sale\ResultError;
use CFile;
use CJSCore;
use Corsik\YaDelivery\Admin\Scripts;

class Handler
{
	const OUT = 'out';
	const IN = 'in';

	public static string $siteId = 's1';
	protected static ?Handler $instance = null;
	private static string $moduleID = 'corsik.yadelivery';
	private static string $registerMainExtJsName = 'corsik_yadelivery';
	/**
	 * other
	 */
	private static string $delivery_js = "/bitrix/js/corsik.yadelivery/delivery.bundle.js";

	private static string $delivery_css = "/bitrix/css/corsik.yadelivery/corsik.yadelivery.css";
	private static string $class_delivery = "YaDelivery\Delivery\YandexDeliveryProfile";
	public $helper = false;
	public bool $module_status = false;

	/**
	 * Creates new instance.
	 *
	 * @throws LoaderException
	 */
	protected function __construct()
	{
		Loader::includeModule('sale');
		$this->helper = Helper::getInstance();
		$this->module_status = self::moduleStatus();
		self::registerMainExtJs();
	}

	/**
	 * @return Handler|null
	 */
	public static function getInstance(): ?Handler
	{
		if (!isset(static::$instance))
		{
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Module status
	 *
	 * @return bool
	 */
	public static function moduleStatus(): bool
	{
		$status = Loader::includeSharewareModule(self::$moduleID);

		return $status == 1 || $status == 2;
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function checkLocation($code, $d): bool
	{
		Loader::includeModule('sale');
		$delivery2location = DeliveryLocationTable::getList(['filter' => ['DELIVERY_ID' => $d]])->fetch();
		if ($delivery2location)
		{
			return ByLocation::check($code, [], $d);
		}
		else
		{
			return true;
		}
	}

	/**
	 * Событие записи скриптов JS
	 *
	 * @throws ArgumentException
	 */
	public static function sale_ComponentOrderOneStepProcess(): void
	{
		$mainOptions = [
			'api_key_yandex_maps' => Option::get('fileman', "yandex_map_api_key"),
			'enable_delivery' => Options::getBoolOptionByName("enable_delivery"),
			'enable_dadata' => Options::getBoolOptionByName("enable_dadata"),
			'console_logs' => Options::getBoolOptionByName("console_logs"),
			'path_core' => "/bitrix/tools/corsik.yadelivery/ajax.php",
		];

		if ($mainOptions['enable_dadata'] || $mainOptions['enable_delivery'])
		{
			self::setJsOption($mainOptions);
		}
	}

	/**
	 * Пишем настройки модуля в Json
	 *
	 * @param $mainOptions
	 * @throws ArgumentException
	 */
	public static function setJsOption($mainOptions): void
	{
		//        $module_version = ModuleManager::getVersion(self::$module_id);
		//        $version = $module_version ? "?v=$module_version" : '?v=777';
		self::registerMainExtJs();
		CJSCore::Init([self::$registerMainExtJsName]);
		$mainOptions['init_delivery'] = CJSCore::IsExtRegistered(self::$registerMainExtJsName) ? "Y" : "N";
		$jsonMainOptions = Json::encode($mainOptions);
		Asset::getInstance()->addString("<script type='text/javascript'> window.json_options = $jsonMainOptions;</script>");
	}

	public static function getSiteId(): ?string
	{
		if (self::$siteId)
		{
			return self::$siteId;
		}
		self::$siteId = Context::getCurrent()->getSite();

		return self::$siteId;
	}

	/**
	 * Получение координат из geoJson и изменение их Lat - Long
	 *
	 * @param $geoJson
	 * @param bool $path
	 * @param array $featuresType
	 * @return mixed
	 * @throws ArgumentException
	 * @throws FileNotFoundException
	 */
	public static function changeLatLong($geoJson, array $featuresType = [], bool $path = false)
	{
		if ($path)
		{
			$file = new File(Application::getDocumentRoot() . $geoJson);
			$geoJson = Helper::JsonDecode($file->getContents());
		}
		foreach ($geoJson['features'] as $key => &$features)
		{
			if (in_array($features['geometry']['type'], $featuresType))
			{
				if ($features['geometry']['type'] == 'Polygon')
				{
					$features['geometry']['coordinates'] = self::reduceSize($features['geometry']['coordinates'], true);
				}
				elseif ($features['geometry']['type'] == 'Point')
				{
					$features['geometry']['coordinates'] = [
						$features['geometry']['coordinates'][1],
						$features['geometry']['coordinates'][0],
					];
				}
			}
			else
			{
				unset($geoJson['features'][$key]);
			}
		}

		return $path ? Json::encode($geoJson) : $geoJson;
	}

	/**
	 * Обрезаем координаты до нужной длины
	 *
	 * @param $geometry
	 * @param bool $reverse
	 * @return mixed
	 */
	public static function reduceSize($geometry, bool $reverse = false)
	{
		foreach ($geometry as &$coordinates)
		{
			$coordinates = array_map(function ($coords) use ($reverse) {
				$lengthCoordinates = 7;

				return $reverse
					? [substr($coords[1], 0, $lengthCoordinates), substr($coords[0], 0, $lengthCoordinates)]
					: [substr($coords[0], 0, $lengthCoordinates), substr($coords[1], 0, $lengthCoordinates)];
			}, $coordinates);
		}

		return $geometry;
	}

	/**
	 * Получаем массив сайтов
	 *
	 * @param bool $ref
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function getSites(bool $ref = false): array
	{
		$sites = [];
		$queryObject = SiteTable::getList(["select" => ["LID", "NAME"], "filter" => ["ACTIVE" => "Y"]]);
		while ($site = $queryObject->fetch())
		{
			$sites[$site['LID']] = $site['NAME'];
		}
		if ($ref)
		{
			$sites = ["reference_id" => array_keys($sites), "reference" => array_values($sites)];
		}

		return $sites;
	}

	public static function sale_OrderBeforeSaved(Event $event): ?EventResult
	{
		$errorCodes = ['DELIVERY_CALCULATED', 'WRONG_DELIVERY_PRICE'];

		if (Options::getBoolOptionByName("disabled_save_order"))
		{
			$isYaDelivery = false;
			$order = $event->getParameter("ENTITY");

			foreach ($order->getShipmentCollection()->getNotSystemItems() as $shipment)
			{
				$deliveryHandlerCode = $shipment->getDelivery()->getHandlerCode();
				$isYaDelivery = $deliveryHandlerCode === 'CORSIK_YADELIVERY';
			}

			if (!$isYaDelivery)
			{
				return null;
			}

			$calculationDelivery = $order->getShipmentCollection()->calculateDelivery();
			if (!$calculationDelivery->isSuccess() && $order->isNew())
			{
				foreach ($calculationDelivery->getErrors() as $error)
				{
					if (in_array($error->getCode(), $errorCodes))
					{
						return new EventResult(EventResult::ERROR, ResultError::create(
							new Error(Loc::getMessage("CORSIK_DELIVERY_SERVICE_BEFORE_SAVED_DELIVERY_ERROR"),
								"DELIVERY"))
						);
					}
				}
			}
		}

		return null;
	}

	public static function sale_DeliveryRestrictions(): void
	{
		// 	return new EventResult(
		// 		EventResult::SUCCESS,
		// 		['\Corsik\YaDelivery\Restriction' => '/bitrix/modules/corsik.yadelivery/lib/restriction.php']
		// 	);
	}

	public static function sale_ComponentOrderResultPrepared($order): void
	{
	}

	public static function sale_DeliveryHandlers(): EventResult
	{
		return new EventResult(
			EventResult::SUCCESS,
			[
				'\Corsik\YaDelivery\Delivery\YandexDeliveryHandler' => '/bitrix/modules/corsik.yadelivery/lib/delivery/handler.php',
			]
		);
	}

	public static function handlerOnEpilog(): void
	{
		if (Handler::moduleStatus())
		{
			Scripts::registerEpilogExtJs();
		}
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public static function getDeliveries(): array
	{
		$arrDeliveries = Manager::getList([
			'select' => ['ID', 'NAME', 'CONFIG'],
			'order' => ['SORT' => 'ASC', 'NAME' => 'ASC'],
			'filter' => ['=ACTIVE' => 'Y', '%CLASS_NAME' => self::$class_delivery],
		])->fetchAll();

		return [
			'DELIVERY_IDS' => array_map(function ($d) {
				return $d['ID'];
			}, $arrDeliveries),
			'DELIVERY_SETTINGS' => array_map(function ($d) {
				return [
					'ID' => $d['ID'],
					'NAME' => $d['NAME'],
					'CONFIG' => $d['CONFIG']['MAIN'],
				];
			}, $arrDeliveries),
		];
	}

	public static function getDadataProperties(): array
	{
		$properties = [];
		$locationID = false;
		foreach (Options::getTypePayers() as $typePayer)
		{
			foreach (Options::getPropertiesOrder($typePayer['ID']) as $prop)
			{
				$option = Options::getOptionByName($prop['ID']);
				$params = explode('-', $option);
				if ($params[0] === 'PARAMS')
				{
					$properties[$typePayer['ID']]['ORDER_PROPS'][$prop['ID']] = [
						'TYPE' => $params[1],
						'PARAMS' => $params[2],
					];
				}
				elseif ($params[0] === 'AFTER')
				{
					$properties[$typePayer['ID']]['ORDER_PROPS_AFTER'][$params[1]][$prop['ID']] = $params[2];
				}
				elseif ($option && $option !== 'N')
				{
					$properties[$typePayer['ID']]['ORDER_PROPS'][$prop['ID']] = $option;
				}
				elseif ($prop['CODE'] == 'LOCATION')
				{
					$locationID = $prop['ID'];
				}
			}

			if ($suggestionsOnly = Options::getOptionByName("suggestions_only_" . $typePayer['ID']))
			{
				$properties[$typePayer['ID']]['SUGGESTIONS_ONLY'] = explode(',', $suggestionsOnly);
			}

			$properties[$typePayer['ID']]['LOCATION'] = [
				'PROPS' => [
					'ID' => Options::getOptionByName('enable_location_address_' . $typePayer['ID']),
					'CODE' => $properties[$typePayer['ID']]['ORDER_PROPS'][Options::getOptionByName('enable_location_address_'
						. $typePayer['ID'])],
				],
				'ID' => $locationID,
				'BX_LOCATION_AUTO' => Options::getBoolOptionByName('enable_location_options_' . $typePayer['ID']),
			];
		}

		return $properties;
	}

	public static function getOrderProperties(): array
	{
		$orderProperties = [];
		foreach (Options::getTypePayers() as $typePayer)
		{
			foreach (Options::getPropertiesOrder($typePayer['ID']) as $prop)
			{
				if ($prop['IS_ADDRESS'] === 'Y' || $prop['IS_LOCATION'] == 'Y')
				{
					$orderProperties[$typePayer['ID']][] = [
						'ID' => $prop['ID'],
						'CODE' => $prop['CODE'],
						'IS_ADDRESS' => $prop['IS_ADDRESS'] === 'Y',
						'IS_LOCATION' => $prop['IS_LOCATION'] === 'Y',
					];
				}
			}
		}

		return $orderProperties;
	}

	public static function getDeliveryLogotip(): int
	{
		$arFile = CFile::MakeFileArray('/bitrix/themes/.default/images/' . self::$moduleID . '/ya_delivery_logo.png');

		return CFile::SaveFile($arFile, "sale/delivery/logotip");
	}

	private static function registerMainExtJs(): void
	{
		if (self::moduleStatus() && !CJSCore::IsExtRegistered(self::$registerMainExtJsName))
		{
			CJSCore::RegisterExt(
				self::$registerMainExtJsName,
				[
					"js" => self::$delivery_js,
					"css" => self::$delivery_css,
					"lang" => "/bitrix/modules/" . self::$moduleID . "/lang/ru/langJS.php",
				]
			);
		}
	}

	private static function getYandexSuggestionsOptions(): array
	{
		$suggestionsProperties = [];
		foreach (Handler::getSites() as $siteID => $siteName)
		{
			foreach (Options::getTypePayers($siteID) as $typePayer)
			{
				$yandexAddressProp = Options::getOptionByName("yandex-address", $siteID, $typePayer['ID']);
				$suggestionsProperties[$typePayer['ID']]['order_props'][$yandexAddressProp] = 'ADDRESS';
			}
		}

		return Helper::array_change_key_case_recursive(['properties' => $suggestionsProperties]);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getModuleParameters(): array
	{
		$arOptions = [];
		if ($this->module_status)
		{
			$arOptions = [
				'main' => [
					'console_logs' => Options::getBoolOptionByName("console_logs"),
				],
			];

			['DELIVERY_SETTINGS' => $deliveriesSettings] = self::getDeliveries();

			$deliveriesConfigs = [];
			foreach ($deliveriesSettings as $deliveryConfig)
			{
				$config['suggestionsRestriction'] = $deliveryConfig['CONFIG']['SUGGESTIONS_RESTRICTION'];
				$config['disabledDeliveryOut'] = $deliveryConfig['CONFIG']['DISABLED_DELIVERY_OUT'] === 'Y';
				$config['startPrice'] = (int)$deliveryConfig['CONFIG']['START_PRICE'];
				$deliveriesConfigs[$deliveryConfig['ID']] = $config;
			}

			$isDeliveryEnable = Options::getBoolOptionByName("enable_delivery");
			if ($isDeliveryEnable)
			{
				$arOptions['delivery'] = [
					'disabled_out' => Options::getBoolOptionByName("disabled_delivery_out"),
					'delivery_calculation_type' => Options::getOptionByName("delivery_calculation_type"),
					'delivery_route_building_method' => Options::getOptionByName("delivery_route_building_method"),
					'delivery_calculation_total' => Options::getOptionByName("delivery_calculation_total"),
					'deliveries_configs' => $deliveriesConfigs,
					'reset_delivery_by_location' => Options::getBoolOptionByName("reset_delivery_by_location"),
					'auto_calculate_delivery' => Options::getBoolOptionByName("auto_calculate_delivery"),
					"currency_format_string" => Options::getOptionByName("currency"),
					'point_properties' => [
						'point_draggable' => Options::getBoolOptionByName("point_draggable"),
						'point_selection' => Options::getBoolOptionByName("point_selection"),
						'event_selection' => Options::getOptionByName("event_selection"),
					],
					'modal' => [
						'display_mode_modal' => Options::getOptionByName("display_mode_modal"),
						'warehouses_preset' => Options::getOptionByName("warehouses_preset"),
						'point_start_preset' => Options::getOptionByName("point_start_preset"),
						'point_stop_preset' => Options::getOptionByName("point_stop_preset"),
						'show_warehouses' => Options::getBoolOptionByName("show_warehouses"),
						'show_warehouses_title' => Options::getBoolOptionByName("show_warehouses_title"),
						'show_alert_calculate' => Options::getBoolOptionByName("show_alert_calculate"),
						'show_full_modal_header' => Options::getBoolOptionByName("show_full_modal_header"),
						'show_full_modal_footer' => Options::getBoolOptionByName("show_full_modal_footer"),
						'show_content_always' => false,
						'show_calculate_price' => Options::getBoolOptionByName("show_calculate_price"),
						'show_route_calculate' => Options::getBoolOptionByName("show_route_calculate"),
					],
				];
			}
			$arOptions['delivery']['enable'] = $isDeliveryEnable;

			$arOptions['order']['properties'] = Helper::array_change_key_case_recursive(self::getOrderProperties());
			$arOptions['additional_fields'] = Options::getOptionsArrayBySite('additional_delivery_fields', true);
			$arOptions['suggestions'] = [
				'type_prompts' => Options::getOptionByName("type_prompts"),
				'restriction' => Options::getOptionByName("address_restriction_common"),
				'count_row' => Options::getOptionByName("count_row") ?? Options::getOptionByName("count_row_dadata"),
				'dadata' => self::getDadataSuggestionsOptions(),
				'yandex' => self::getYandexSuggestionsOptions(),
			];
		}

		foreach (GetModuleEvents(self::$moduleID, 'getModuleParameters', true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, [&$arOptions]);
		}

		return $arOptions;
	}

	/**
	 * @throws SystemException
	 */
	public function getMapCoords($post): array
	{
		[
			'deliveryID' => $deliveryID,
			'points' => ['warehouse' => $warehouseID],
		] = $post;

		['DELIVERY_IDS' => $deliveryIDs] = self::getDeliveries();
		$zoneID = $this->getWarehouseAttachedZone($warehouseID);

		if (
			isset($deliveryID)
			&& $this->module_status
			&& Options::getBoolOptionByName("enable_delivery")
			&& in_array($deliveryID, $deliveryIDs)
		)
		{
			$config = Manager::getById($deliveryID);
			$warehouseID = $config['CONFIG']['MAIN']['WAREHOUSES'];
			$zoneID = $this->getWarehouseAttachedZone($warehouseID);
		}

		$w = $this->helper->getDataById($warehouseID, 'warehouses');
		$z = $this->helper->getDataById($zoneID, 'zones');
		if (!empty($w) && !empty($z))
		{
			return [
				'w' => [
					'id' => $w['ID'],
					'coordinates' => $w['COORDINATES'],
				],
				'z' => [
					'id' => $z['ID'],
					'coordinates' => $z['COORDINATES'],
				],
			];
		}

		return [];
	}

	public function getWarehouseAttachedZone($warehouseID, $byName = null): ?int
	{
		$params = $this->helper->getDataById($warehouseID, 'warehouses', ['ZONE_ID'], $byName ?? 'ID');

		return $params['ZONE_ID'];
	}

	/**
	 * Получение массива с данными для SelectArray
	 */
	public function getDataToSelect($type, $ref = false, $id = 0, $start = []): array
	{
		$result = $start;

		if ($id)
		{
			$query = ["select" => ["COORDINATES", "NAME", "ID"], "filter" => ["ID" => $id, "ACTIVE" => "Y"]];
			$row = $this->helper->getQueryObject($type, $query, 'getRow');
			if ($row)
			{
				$arrJson = Helper::JsonDecode($row["COORDINATES"]);
				foreach ($arrJson['features'] as $feature)
				{
					$result[$feature['properties']['code']] = $feature['properties']['hint-content'];
				}
			}
		}
		else
		{
			$query = ["select" => ["ID", "NAME"], "filter" => ["ACTIVE" => "Y"]];
			$queryObject = $this->helper->getQueryObject($type, $query, 'getList');
			while ($res = $queryObject->fetch())
			{
				$result[$res['ID']] = $res['NAME'];
			}
		}

		if ($ref)
		{
			$result = ["reference_id" => array_keys($result), "reference" => array_values($result)];
		}

		return $result;
	}

	public function getOrderPropertiesToSelect(int $typePayers): array
	{
		$orderProperties = [];

		foreach (Options::getPropertiesOrder($typePayers) as $orderProperty)
		{
			$orderProperties[$orderProperty['ID']] = $orderProperty['NAME'];
		}

		return $orderProperties;
	}

	/**
	 * @throws SystemException
	 */
	public function calculatePrice($post): array
	{
		$zones = 'zones';
		$warehouses = 'warehouses';
		$params = [];
		$rules = [];

		foreach (GetModuleEvents(self::$moduleID, 'OnYandexBeforeCalculatePrice', true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, [&$post]);
		}

		[
			'delivery' => $delivery,
			'order' => $order,
			'customParams' => $customParams,
		] = $post;

		$deliveryPrice = 0;
		$deliveryConfig = $this->prepareDeliveryConfig($delivery, $customParams);
		$isDeliveryOut = trim($delivery['type']) === Handler::OUT;
		$warehouseID = $deliveryConfig['WAREHOUSES'];
		$zoneID = $this->getWarehouseAttachedZone($warehouseID);

		if ($isDeliveryOut && $this->isDisabledOutRoute($delivery['deliveryID']))
		{
			$deliveryPrice = -1;
		}
		else
		{
			if ($warehouseID > 0 && $zoneID > 0)
			{
				$warehouseData = $this->helper->getDataById($warehouseID, $warehouses);
				$warehousesList = $this->getDeliveryInProperties($warehouseData['COORDINATES'], $warehouses);
				$filteredWarehouses = $this->helper->filtrationWarehouses($delivery, $warehousesList);
				$filteredWarehousesRules = $this->helper->filtrationRules($order, $filteredWarehouses);
				$priceInZones = $this->getPriceInZones($filteredWarehousesRules);
				$rules['WAREHOUSES']['ALL'] = $filteredWarehouses;
				$rules['WAREHOUSES']['FILTERED'] = $filteredWarehousesRules;
				$deliveryPrice = $priceInZones;

				if ($isDeliveryOut)
				{
					$polygonsData = $this->helper->getDataById($zoneID, $zones);
					$polygonsList = $this->getDeliveryInProperties($polygonsData['COORDINATES'], $zones);
					$filteredPolygons = $this->helper->filtrationPolygons($delivery, $polygonsList);
					$filteredPolygonsRules = $this->helper->filtrationRules($order, $filteredPolygons);
					$filteredForMaxPriceRules = $this->helper->getMaxPrice($filteredPolygonsRules);
					$rules['POLYGONS']['ALL'] = $filteredPolygonsRules;
					$rules['POLYGONS']['FILTERED'] = $filteredForMaxPriceRules;
					$priceOutZones = $this->calculateDistancePrice($filteredForMaxPriceRules, $delivery['distance']);

					$isAddZonePrice = $deliveryConfig && $deliveryConfig['ADD_ZONE_PRICE'] === 'Y';
					$deliveryPrice = $isAddZonePrice ? $priceInZones + $priceOutZones : $priceOutZones;
				}

				// $minPrice = $minPrice ?? $deliveryOptions['START_PRICE'];
				// $maxPriceWarehouseRules = $maxPriceWarehouseRules >= $minPrice ? $maxPriceWarehouseRules : $minPrice;
			}
		}

		foreach (GetModuleEvents(self::$moduleID, 'OnYandexAfterCalculatePrice', true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, [&$deliveryPrice, $post, &$params]);
		}

		return [
			'price' => $deliveryPrice,
			'rules' => $rules,
			'params' => $params,
		];
	}

	/**
	 * @throws SystemException
	 */
	public function prepareDeliveryConfig(array $delivery, ?array $customParams = []): array
	{
		$defaultConfig = [
			'DISABLED_DELIVERY_OUT' => 'N',
			'START_PRICE' => 0,
			'ADD_ZONE_PRICE' => 'Y',
		];

		$deliveryConfig = [];

		if (is_numeric($delivery['deliveryID']))
		{
			$deliveryConfig = $this->getDeliveryConfigByID($delivery['deliveryID']);
		}
		elseif ($customParams)
		{
			$deliveryConfig = [
				'WAREHOUSES' => $customParams['points']['warehouse'],
				'ADD_ZONE_PRICE' => $customParams['addZonePrice'],
			];
		}

		return array_merge($defaultConfig, $deliveryConfig);
	}

	/**
	 * @throws SystemException
	 */
	public function getDeliveryConfigByID(int $deliveryID): array
	{
		if ($deliveryID > 0)
		{
			$deliveryOptions = Manager::getById($deliveryID);

			return $deliveryOptions['CONFIG']['MAIN'];
		}

		return [];
	}

	/**
	 * @throws SystemException
	 */
	public function isDisabledOutRoute(?int $deliveryID): bool
	{
		$disabledDeliveryOut = false;
		if ($deliveryID > 0)
		{
			$deliveryConfig = $this->getDeliveryConfigByID($deliveryID);
			$disabledDeliveryOut = $deliveryConfig['DISABLED_DELIVERY_OUT'] === 'Y';
		}

		$disabledDeliveryOutAll = Options::getBoolOptionByName("disabled_delivery_out");

		return $disabledDeliveryOut || $disabledDeliveryOutAll;
	}

	/**
	 * Получение данных о доставках из свойств Json
	 */
	public function getDeliveryInProperties(string $json, string $type): array
	{
		if (!Helper::isJson($json))
		{
			return [];
		}

		$arrJson = Helper::JsonDecode($json);
		if (!isset($arrJson['features']))
		{
			return [];
		}

		$mergeDeliveries = [];
		foreach ($arrJson['features'] as $feature)
		{
			$deliveries = $feature['properties']['delivery'][$type] ?? [];
			$mergeDeliveries = array_merge($mergeDeliveries, $deliveries);
		}

		$allDeliveries = $arrJson['properties'][$type]['all'] ?? [];
		$mergeDeliveries = Helper::array_change_key_case_recursive(array_merge($allDeliveries, $mergeDeliveries),
			CASE_UPPER);

		if (empty($mergeDeliveries))
		{
			if ($type === 'zone')
			{
				$keys = ['POLYGON', 'KM', 'PRICE', 'RULE'];
			}
			else
			{
				$keys = ['FROM', 'TO', 'PRICE'];
			}

			return [array_fill_keys($keys, '')];
		}

		return $mergeDeliveries;
	}

	function calculateDistancePrice($rules, $distance): int
	{
		if (!$rules['KM'])
		{
			return 0;
		}
		$price = ceil($distance / ($rules['KM'] * 1000)) * $rules['PRICE'];

		return intval(max($price, 0));
	}

	public function hasFeatures(string $json): bool
	{
		$arrJson = [];
		if (Helper::isJson($json))
		{
			$arrJson = Helper::JsonDecode($json);
		}

		return !empty($arrJson['features']);
	}

	/**
	 * Запись сданных о доставки в Json
	 *
	 * @throws ArgumentException
	 */
	public function setPropertiesToJson($postData, $type): string
	{
		$arrJson = Helper::JsonDecode($postData['COORDINATES']);
		foreach ($arrJson['features'] as &$features)
		{
			if ($features['geometry']['type'] == 'Polygon')
			{
				$features['geometry']['coordinates'] = self::reduceSize($features['geometry']['coordinates']);
			}

			if (!empty($postData['PROPERTIES']['DELIVERY']))
			{
				$arrJson['properties'][$type]['all'] = Helper::array_change_key_case_recursive(
					array_filter($postData['PROPERTIES']['DELIVERY'], function ($delivery) {
						return $delivery['POLYGON'] == 'all' || $delivery['FROM'] == 'all';
					})
				);

				foreach ($postData['PROPERTIES']['DELIVERY'] as $delivery)
				{
					if (
						$delivery['FROM'] == $features['properties']['code']
						|| $delivery['POLYGON']
						== $features['properties']['code']
					)
					{
						$features['properties']['delivery'][$type][] = Helper::array_change_key_case_recursive($delivery);
					}
				}
			}
		}

		return Json::encode($arrJson);
	}

	private function getDadataSuggestionsOptions(): array
	{
		$isDadataEnable = Options::getBoolOptionByName("enable_dadata");
		$arDadataOptions = [
			'enable' => $isDadataEnable,
			'properties' => Helper::array_change_key_case_recursive(self::getDadataProperties()),
		];

		return $isDadataEnable
			? array_merge($arDadataOptions, [
				'api_key' => Options::getOptionByName("api_key_dadata"),
				'partner' => 'BITRIX.CORSIK39',
				'count_row' => Options::getOptionByName("count_row_dadata"),
				'required_house' => Options::getBoolOptionByName("required_house"),
				'restriction' => Options::getOptionByName("address_restriction_common"),
				'division' => Options::getOptionByName("division_dadata"),
			]) : $arDadataOptions;
	}

	private function getPriceInZones($filteredWarehousesRules): int
	{
		return intval(max(array_column($filteredWarehousesRules, 'PRICE')));
	}
}

?>
