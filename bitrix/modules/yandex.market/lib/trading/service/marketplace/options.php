<?php

namespace Yandex\Market\Trading\Service\Marketplace;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;

class Options extends TradingService\Common\Options
{
	const STOCKS_PLAIN = 'plain';
	const STOCKS_ONLY_AVAILABLE = 'onlyAvailable';
	const STOCKS_WITH_RESERVE = 'withReserve';

	const PRICES_MODE_CAMPAIGN = 'campaign';
	const PRICES_MODE_BUSINESS = 'business';

	/** @var Provider */
	protected $provider;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
		parent::includeMessages();
	}

	public function __construct(Provider $provider)
	{
		parent::__construct($provider);
	}

	public function getTitle($version = '')
	{
		$suffix = $version !== '' ? '_' . $version : '';

		return static::getLang('TRADING_SERVICE_MARKETPLACE_TITLE' . $suffix);
	}

	public function getBusinessId()
	{
		$option = Market\Data\Number::castInteger($this->getValue('BUSINESS_ID'));

		if ($option !== null)
		{
			return $option;
		}

		return Market\Api\Campaigns\Facade::businessId($this);
	}

	public function getPaySystemId($paymentType)
	{
		$paySystemTypeUpper = Market\Data\TextString::toUpper($paymentType);

		return (string)$this->getValue('PAY_SYSTEM_' . $paySystemTypeUpper);
	}

	public function getDeliveryId()
	{
		return (string)$this->getValue('DELIVERY_ID');
	}

	public function includeBasketSubsidy()
	{
		return (string)$this->getValue('BASKET_SUBSIDY_INCLUDE') === Market\Reference\Storage\Table::BOOLEAN_Y;
	}

	public function getSubsidyPaySystemId()
	{
		return (string)$this->getValue('SUBSIDY_PAY_SYSTEM_ID');
	}

	public function getCashboxCheck()
	{
		return $this->getValue('CASHBOX_CHECK', PaySystem::CASHBOX_CHECK_DISABLED);
	}

	public function useWarehouses()
	{
		return (string)$this->getValue('USE_WAREHOUSES') === Market\Reference\Storage\Table::BOOLEAN_Y;
	}

	public function getWarehouseStoreField()
	{
		return $this->getRequiredValue('WAREHOUSE_STORE_FIELD');
	}

	public function getProductStores()
	{
		$result = array_unique(array_merge(
			$this->getProductSelfStores(),
			$this->getStoreGroupCommand()->stores()
		));

		sort($result);

		return $result;
	}

	public function getProductSelfStores()
	{
		return parent::getProductStores();
	}

	public function useStoreGroup()
	{
		$storeGroup = $this->getStoreGroup();

		return !empty($storeGroup);
	}

	public function getStoreGroupPrimarySetup()
	{
		return $this->getStoreGroupCommand()->primarySetup();
	}

	public function getStoreGroup()
	{
		return $this->getStoreGroupCommand()->linked();
	}

	protected function getStoreGroupCommand()
	{
		return $this->provider->getContainer()->single(Command\GroupStores::class);
	}

	public function usePushStocks()
	{
		return (string)$this->getValue('USE_PUSH_STOCKS') === Market\Reference\Storage\Table::BOOLEAN_Y;
	}

	public function getWarehousePrimary()
	{
		list($primaryWarehouse) = Market\Api\Business\Warehouses\Facade::primaryWarehouse($this);

		return $primaryWarehouse;
	}

	public function getWarehousePrimaryField()
	{
		return $this->getRequiredValue('WAREHOUSE_PRIMARY_FIELD');
	}

	public function usePushPrices()
	{
		return (string)$this->getValue('USE_PUSH_PRICES') === Market\Reference\Storage\Table::BOOLEAN_Y;
	}

	public function getPricesMode()
	{
		return $this->getValue('PRICES_MODE');
	}

	public function getProductFeeds()
	{
		$ids = (array)$this->getValue('PRODUCT_FEED');

		Main\Type\Collection::normalizeArrayValuesByInt($ids, false);

		return $ids;
	}

	public function productUpdatedAt()
	{
		$dateFormatted = (string)$this->getValue('PRODUCT_UPDATED_AT');

		return (
			$dateFormatted !== ''
				? new Main\Type\DateTime($dateFormatted, \DateTime::ATOM)
				: null
		);
	}

	public function getStocksBehavior()
	{
		return $this->getValue('STOCKS_BEHAVIOR');
	}

	public function useOrderReserve()
	{
		return (
			$this->selfOrderReserve()
			|| $this->getStoreGroupCommand()->useOrderReserve()
		);
	}

	public function selfOrderReserve()
	{
		return in_array($this->getStocksBehavior(), [
			static::STOCKS_ONLY_AVAILABLE,
			static::STOCKS_WITH_RESERVE,
		], true);
	}

	public function getReserveGroupSetupIds()
	{
		return $this->getStoreGroupCommand()->linkedWithReserve();
	}

	public function isAllowModifyPrice()
	{
		return true;
	}

	public function isAllowModifyBasket()
	{
		return (string)$this->getValue('ORDER_ACCEPT_WITH_ERRORS') === Market\Reference\Storage\Table::BOOLEAN_Y;
	}

	public function isAllowProductSkuPrefix()
	{
		return Market\Config::isExpertMode();
	}

	/** @return Options\SelfTestOption */
	public function getSelfTestOption()
	{
		return $this->getFieldset('SELF_TEST');
	}

	public function getShipmentStatus($action)
	{
		return $this->getValue('STATUS_SHIPMENT_' . $action);
	}

	public function getEnvironmentFieldActions(TradingEntity\Reference\Environment $environment)
	{
		return array_filter([
			$this->getEnvironmentCisActions($environment),
			$this->getEnvironmentItemsActions(),
			$this->getEnvironmentCashboxActions(),
		]);
	}

	protected function getEnvironmentCisActions(TradingEntity\Reference\Environment $environment)
	{
		return [
			'FIELD' => 'SHIPMENT.ITEM.STORE.MARKING_CODE',
			'PATH' => 'send/identifiers',
			'PAYLOAD' => static function(array $action) use ($environment) {
				$itemsMap = [];
				$newIndex = 0;
				$result = [
					'items' => [],
				];

				foreach ($action['VALUE'] as $storeItem)
				{
					$markingCode = trim($storeItem['VALUE']);

					if ($markingCode === '') { continue; }

					$markingType = $environment->getProduct()->getMarkingGroupType($storeItem['MARKING_GROUP']);
					$itemKey = $storeItem['XML_ID'] . ':' . $storeItem['PRODUCT_ID'];

					if ($markingType === Market\Data\Trading\MarkingRegistry::UIN)
					{
						$identifier = Market\Data\Trading\Uin::formatMarkingCode($markingCode);
						$key = 'uin';
					}
					else
					{
						$identifier = Market\Data\Trading\Cis::formatMarkingCode($markingCode);
						$key = 'cis';
					}

					if (isset($itemsMap[$itemKey]))
					{
						$itemIndex = $itemsMap[$itemKey];
						$result['items'][$itemIndex]['instances'][] = [ $key => $identifier ];
					}
					else
					{
						$itemsMap[$itemKey] = $newIndex;
						$result['items'][$newIndex] = [
							'productId' => $storeItem['PRODUCT_ID'],
							'xmlId' => $storeItem['XML_ID'],
							'instances' => [
								[ $key => $identifier ],
							],
						];

						++$newIndex;
					}
				}

				return !empty($result['items']) ? $result : null;
			}
		];
	}

	protected function getEnvironmentItemsActions()
	{
		if (Market\Config::getOption('trading_silent_basket', 'N') === 'Y') { return null; }

		return [
			'FIELD' => 'BASKET.QUANTITY',
			'PATH' => 'send/items',
			'PAYLOAD' => static function(array $action) {
				$result = [
					'items' => [],
				];

				foreach ($action['VALUE'] as $basketItem)
				{
					$quantity = (float)$basketItem['VALUE'];

					if ($quantity <= 0) { continue; }

					$result['items'][] = [
						'productId' => $basketItem['PRODUCT_ID'],
						'xmlId' => $basketItem['XML_ID'],
						'count' => $quantity,
					];
				}

				return $result;
			}
		];
	}

	protected function getEnvironmentCashboxActions()
	{
		if ($this->getCashboxCheck() !== PaySystem::CASHBOX_CHECK_DISABLED) { return null; }

		return [
			'FIELD' => 'CASHBOX.CHECK',
			'PATH' => 'system/cashbox/reset',
			'PAYLOAD' => [],
			'DELAY' => false,
		];
	}

	protected function applyValues()
	{
		parent::applyValues();
		$this->applyOrderCourierProperties();
		$this->applyElectronicAcceptanceCertificateProperties();
		$this->applyPaySystemId();
		$this->applyStocksBehavior();
		$this->applyPricesMode();
	}

	protected function applyProductStoresReserve()
	{
		$stored = (array)$this->getValue('PRODUCT_STORE');
		$required = array_diff($stored, [
			TradingEntity\Common\Store::PRODUCT_FIELD_QUANTITY_RESERVED,
		]);

		if (count($stored) !== count($required))
		{
			$this->values['PRODUCT_STORE'] = array_values($required);
			$this->values['USE_ORDER_RESERVE'] = Market\Ui\UserField\BooleanType::VALUE_Y;
		}
		else if (!empty($stored) && !isset($this->values['USE_ORDER_RESERVE']))
		{
			$this->values['USE_ORDER_RESERVE'] = Market\Ui\UserField\BooleanType::VALUE_N;
		}
	}

	protected function applyOrderCourierProperties()
	{
		if (
			empty($this->values['PROPERTY_VEHICLE_NUMBER'])
			|| !empty($this->values['PROPERTY_COURIER_VEHICLE_NUMBER'])
		)
		{
			return;
		}

		$this->values['PROPERTY_COURIER_VEHICLE_NUMBER'] = $this->values['PROPERTY_VEHICLE_NUMBER'];
		unset($this->values['PROPERTY_VEHICLE_NUMBER']);
	}

	protected function applyElectronicAcceptanceCertificateProperties()
	{
		if (
			empty($this->values['PROPERTY_ELECTRONIC_ACCEPTANCE_CERTIFICATE'])
			|| !empty($this->values['PROPERTY_EAC_CODE'])
		)
		{
			return;
		}

		$this->values['PROPERTY_EAC_CODE'] = $this->values['PROPERTY_ELECTRONIC_ACCEPTANCE_CERTIFICATE'];
		unset($this->values['PROPERTY_ELECTRONIC_ACCEPTANCE_CERTIFICATE']);
	}

	protected function applyPaySystemId()
	{
		if (empty($this->values['PAY_SYSTEM_ID'])) { return; }

		foreach ($this->provider->getPaySystem()->getTypes() as $paymentType)
		{
			$optionName = 'PAY_SYSTEM_' . $paymentType;

			if (isset($this->values[$optionName])) { continue; }

			$this->values[$optionName] = $this->values['PAY_SYSTEM_ID'];
		}

		unset($this->values['PAY_SYSTEM_ID']);
	}

	protected function applyStocksBehavior()
	{
		if (!isset($this->values['USE_ORDER_RESERVE'])) { return; }

		if (empty($this->values['STOCKS_BEHAVIOR']))
		{
			$useReserve = ((string)$this->values['USE_ORDER_RESERVE'] === Market\Reference\Storage\Table::BOOLEAN_Y);
			$this->values['STOCKS_BEHAVIOR'] = $useReserve ? static::STOCKS_ONLY_AVAILABLE : static::STOCKS_PLAIN;
		}

		unset($this->values['USE_ORDER_RESERVE']);
	}

	protected function applyPricesMode()
	{
		if (!empty($this->values['PRICES_MODE']) || !$this->usePushPrices()) { return; }

		$this->values['PRICES_MODE'] = static::PRICES_MODE_CAMPAIGN;
	}

	public function takeChanges(TradingService\Reference\Options\Skeleton $previous)
	{
		/** @var Options $previous */
		Market\Reference\Assert::typeOf($previous, static::class, 'previous');

		$this->takeProductChanges($previous);
	}

	protected function takeProductChanges(Options $previous)
	{
		if ($this->compareStoreChanges($previous) || $this->compareSkuChanges($previous) || $this->compareReserveChanges($previous))
		{
			$timestamp = new Main\Type\DateTime();

			$this->values['PRODUCT_UPDATED_AT'] = $timestamp->format(\DateTime::ATOM);
		}
	}

	protected function compareStoreChanges(Options $previous)
	{
		if ($previous->useWarehouses() !== $this->useWarehouses())
		{
			$changed = true;
		}
		else if ($this->useWarehouses())
		{
			$changed = $previous->getWarehouseStoreField() !== $this->getWarehouseStoreField();
		}
		else
		{
			$currentStores = (array)$this->getValue('PRODUCT_STORE');
			$previousStores = (array)$previous->getValue('PRODUCT_STORE');
			$newStores = array_diff($currentStores, $previousStores);
			$deletedStores = array_diff($previousStores, $currentStores);

			$changed = !empty($newStores) || !empty($deletedStores);
		}

		return $changed;
	}

	protected function compareSkuChanges(Options $previous)
	{
		$currentMap = $this->getProductSkuMap();
		$previousMap = $previous->getProductSkuMap();

		if (empty($currentMap) !== empty($previousMap))
		{
			$changed = true;
		}
		else if (!empty($previousMap))
		{
			$changed = false;

			foreach ($previousMap as $key => $previousLink)
			{
				$currentLink = isset($currentMap[$key])
					? $currentMap[$key]
					: null;

				if (
					$currentLink === null
					|| $currentLink['IBLOCK'] !== $previousLink['IBLOCK']
					|| $currentLink['FIELD'] !== $previousLink['FIELD']
				)
				{
					$changed = true;
					break;
				}
			}
		}
		else
		{
			$changed = false;
		}

		return $changed;
	}

	protected function compareReserveChanges(Options $previous)
	{
		return (string)$this->getValue('USE_ORDER_RESERVE') !== (string)$previous->getValue('USE_ORDER_RESERVE');
	}

	public function getTabs()
	{
		return [
			'COMMON' => [
				'name' => static::getLang('TRADING_SERVICE_MARKETPLACE_TAB_COMMON'),
				'sort' => 1000,
			],
			'STORE' => [
				'name' => static::getLang('TRADING_SERVICE_MARKETPLACE_TAB_STORE'),
				'sort' => 2000,
			],
			'STATUS' => [
				'name' => static::getLang('TRADING_SERVICE_MARKETPLACE_TAB_STATUS'),
				'sort' => 3000,
				'data' => [
					'WARNING' => static::getLang('TRADING_SERVICE_MARKETPLACE_TAB_STATUS_NOTE'),
				]
			],
		];
	}

	public function getFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return
			$this->getCommonFields($environment, $siteId)
			+ $this->getCompanyFields($environment, $siteId)
			+ $this->getIncomingRequestFields($environment, $siteId)
			+ $this->getOauthRequestFields($environment, $siteId)
			+ $this->getOrderDeliveryFields($environment, $siteId)
			+ $this->getOrderPaySystemFields($environment, $siteId)
			+ $this->getOrderBasketSubsidyFields($environment, $siteId)
			+ $this->getOrderCashboxFields($environment, $siteId)
			+ $this->getOrderPersonFields($environment, $siteId)
			+ $this->getOrderPropertyBuyerFields($environment, $siteId)
			+ $this->getOrderPropertyUtilFields($environment, $siteId)
			+ $this->getOrderPropertyCourierFields($environment, $siteId)
			+ $this->getProductSkuMapFields($environment, $siteId)
			+ $this->getProductStoreFields($environment, $siteId)
			+ $this->getPushStocksFields($environment, $siteId)
			+ $this->getPushPricesFields($environment, $siteId)
			+ $this->getProductPriceFields($environment, $siteId)
			+ $this->getProductFeedFields($environment, $siteId)
			+ $this->getStatusInFields($environment, $siteId)
			+ $this->getStatusOutFields($environment, $siteId)
			+ $this->getStatusOutSyncFields($environment, $siteId)
			+ $this->getStatusShipmentFields($environment, $siteId)
			+ $this->getOrderAcceptFields($environment, $siteId);
	}

	protected function getCommonFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getCommonFields($environment, $siteId);
		$result['BUSINESS_ID'] = [
			'TYPE' => 'string',
			'HIDDEN' => 'Y',
			'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_BUSINESS_ID'),
		];

		return $this->applyFieldsOverrides($result, [
			'GROUP' => static::getLang('TRADING_SERVICE_COMMON_GROUP_SERVICE_REQUEST'),
		]);
	}

	protected function getCompanyFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getCompanyFields($environment, $siteId);

		return $this->applyFieldsOverrides($result, [
			'GROUP' => static::getLang('TRADING_SERVICE_COMMON_GROUP_SERVICE_REQUEST'),
			'DEPRECATED' => 'Y',
		]);
	}

	protected function getPersonTypeDefaultValue(TradingEntity\Reference\PersonType $personType, $siteId)
	{
		return $personType->getLegalId($siteId);
	}

	protected function getOrderPersonFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getOrderPersonFields($environment, $siteId);

		return $this->applyFieldsOverrides($result, [
			'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ORDER_PROPERTY'),
			'SORT' => 3480,
		]);
	}

	protected function getOrderPaySystemFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$paySystem = $environment->getPaySystem();
			$paySystemEnum = $paySystem->getEnum($siteId);
			$firstPaySystem = reset($paySystemEnum);
			$servicePaySystem = $this->provider->getPaySystem();
			$result = [];
			$sort = 3400;

			foreach ($servicePaySystem->getTypes() as $paymentType)
			{
				$result['PAY_SYSTEM_' . $paymentType] = [
					'TYPE' => 'enumeration',
					'MANDATORY' => $paySystem->isRequired() ? 'Y' : 'N',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_PAY_SYSTEM', [
						'#TYPE#' => $servicePaySystem->getTypeTitle($paymentType, 'SHORT'),
					]),
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ORDER'),
					'GROUP_DESCRIPTION' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ORDER_DESCRIPTION'),
					'VALUES' => $paySystemEnum,
					'SETTINGS' => [
						'DEFAULT_VALUE' => $firstPaySystem !== false ? $firstPaySystem['ID'] : null,
						'STYLE' => 'max-width: 220px;',
					],
					'SORT' => ++$sort,
				];
			}
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getOrderDeliveryFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$delivery = $environment->getDelivery();
			$deliveryEnum = $delivery->getEnum($siteId);
			$defaultDelivery = null;
			$emptyDelivery = array_filter($deliveryEnum, function($option) {
				return $option['TYPE'] === Market\Data\Trading\Delivery::EMPTY_DELIVERY;
			});

			if (empty($emptyDelivery))
			{
				$firstEmptyDelivery = reset($emptyDelivery);
				$defaultDelivery = $firstEmptyDelivery['ID'];
			}
			else if (!empty($deliveryEnum))
			{
				$firstDelivery = reset($deliveryEnum);
				$defaultDelivery = $firstDelivery['ID'];
			}

			$result = [
				'DELIVERY_ID' => [
					'TYPE' => 'enumeration',
					'MANDATORY' => $delivery->isRequired() ? 'Y' : 'N',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_DELIVERY_ID'),
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ORDER'),
					'GROUP_DESCRIPTION' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ORDER_DESCRIPTION'),
					'VALUES' => $deliveryEnum,
					'SETTINGS' => [
						'DEFAULT_VALUE' => $defaultDelivery,
						'STYLE' => 'max-width: 220px;',
					],
					'SORT' => 3300,
				],
			];
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getOrderBasketSubsidyFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$paySystem = $environment->getPaySystem();
			$paySystemEnum = $paySystem->getEnum($siteId);

			$result = [
				'BASKET_SUBSIDY_INCLUDE' => [
					'TYPE' => 'boolean',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_BASKET_SUBSIDY_INCLUDE'),
					'SORT' => 3450,
					'SETTINGS' => [
						'DEFAULT_VALUE' => Market\Ui\UserField\BooleanType::VALUE_Y,
					],
				],
				'SUBSIDY_PAY_SYSTEM_ID' => [
					'TYPE' => 'enumeration',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_SUBSIDY_PAY_SYSTEM_ID'),
					'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_SUBSIDY_PAY_SYSTEM_ID_HELP'),
					'VALUES' => $paySystemEnum,
					'SETTINGS' => [
						'DEFAULT_VALUE' => $paySystem->getInnerPaySystemId(),
						'CAPTION_NO_VALUE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_SUBSIDY_PAY_SYSTEM_ID_NO_VALUE'),
						'STYLE' => 'max-width: 220px;'
					],
					'SORT' => 3451,
					'DEPEND' => [
						'BASKET_SUBSIDY_INCLUDE' => [
							'RULE' => 'ANY',
							'VALUE' => Market\Ui\UserField\BooleanType::VALUE_Y,
						],
					],
				],
			];
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getOrderCashboxFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$paySystem = $this->provider->getPaySystem();
		$default = $paySystem::CASHBOX_CHECK_DISABLED;
		$values = $paySystem->getCashboxCheckEnum();

		uasort($values, function($optionA, $optionB) use ($default) {
			$sortA = $optionA['ID'] === $default ? 0 : 1;
			$sortB = $optionB['ID'] === $default ? 0 : 1;

			if ($sortA === $sortB) { return 0; }

			return ($sortA < $sortB ? -1 : 1);
		});

		return [
			'CASHBOX_CHECK' => [
				'TYPE' => 'enumeration',
				'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_CASHBOX_CHECK'),
				'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_CASHBOX_CHECK_HELP'),
				'VALUES' => $values,
				'HIDDEN' => !Main\Loader::includeModule('sale') || !class_exists(Sale\Cashbox\Cashbox::class) ? 'Y' : 'N',
				'SETTINGS' => [
					'DEFAULT_VALUE' => $default,
					'ALLOW_NO_VALUE' => 'N',
					'STYLE' => 'max-width: 220px;',
				],
				'SORT' => 3470,
			],
		];
	}

	protected function getOrderPropertyBuyerFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$buyerClass = $this->provider->getModelFactory()->getBuyerClassName();
		$fields = $buyerClass::getMeaningfulFields();
		$options = [];

		foreach ($fields as $fieldName)
		{
			$options[$fieldName] = [
				'NAME' => $buyerClass::getMeaningfulFieldTitle($fieldName),
			];
		}

		return $this->createPropertyFields($environment, $siteId, $options, 3550);
	}

	protected function getOrderPropertyUtilFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getOrderPropertyUtilFields($environment, $siteId);

		return $this->applyFieldsOverrides($result, [
			'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ORDER_PROPERTY'),
			'SORT' => 3500,
		]);
	}

	protected function getOrderPropertyCourierFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$options = [];

		foreach (Model\Order\Delivery\Courier::getMeaningfulFields() as $field)
		{
			$options['COURIER_' . $field] = [
				'NAME' => Model\Order\Delivery\Courier::getMeaningfulFieldTitle($field),
				'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_COURIER_PROPERTY'),
			];
		}

		return $this->createPropertyFields($environment, $siteId, $options, 3600);
	}

	protected function getProductSkuMapFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getProductSkuMapFields($environment, $siteId);
		$overridable = array_diff_key($result, [
			'PRODUCT_SKU_ADV_PREFIX' => true,
		]);

		return
			$this->applyFieldsOverrides($overridable, [ 'HIDDEN' => 'N' ])
			+ $result;
	}

	protected function getProductStoreFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		global $APPLICATION;

		try
		{
			$store = $environment->getStore();
			$supportsWarehouses = $this->provider->getFeature()->supportsWarehouses();

			$warehouseFields = [
				'USE_WAREHOUSES' => [
					'TYPE' => 'boolean',
					'TAB' => 'STORE',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_USE_WAREHOUSES'),
					'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_USE_WAREHOUSES_HELP'),
					'SORT' => 1100,
					'HIDDEN' => $supportsWarehouses ? 'N' : 'Y',
					'DEPRECATED' => 'Y',
				],
				'WAREHOUSE_STORE_FIELD' => [
					'TYPE' => 'enumeration',
					'TAB' => 'STORE',
					'MANDATORY' => 'Y',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_WAREHOUSE_STORE_FIELD'),
					'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_WAREHOUSE_STORE_FIELD_HELP', [
						'#LANG#' => LANGUAGE_ID,
						'#BACKURL#' => rawurlencode($APPLICATION->GetCurPageParam('')),
					]),
					'SORT' => 1105,
					'VALUES' => $store->getFieldEnum($siteId),
					'HIDDEN' => $supportsWarehouses ? 'N' : 'Y',
					'SETTINGS' => [
						'DEFAULT_VALUE' => $store->getWarehouseDefaultField(),
						'STYLE' => 'max-width: 220px;',
					],
					'DEPEND' => [
						'USE_WAREHOUSES' => [
							'RULE' => 'EMPTY',
							'VALUE' => false,
						],
					],
				],
			];
			$commonFields = parent::getProductStoreFields($environment, $siteId);
			$commonFields += [
				'STOCKS_BEHAVIOR' =>  [
					'TYPE' => 'enumeration',
					'TAB' => 'STORE',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_STOCKS_BEHAVIOR'),
					'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_STOCKS_BEHAVIOR_HELP'),
					'SORT' => 1105,
					'VALUES' => [
						[
							'ID' => static::STOCKS_ONLY_AVAILABLE,
							'VALUE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_STOCKS_BEHAVIOR_ONLY_AVAILABLE'),
						],
						[
							'ID' => static::STOCKS_WITH_RESERVE,
							'VALUE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_STOCKS_BEHAVIOR_WITH_RESERVE'),
						],
						[
							'ID' => static::STOCKS_PLAIN,
							'VALUE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_STOCKS_BEHAVIOR_PLAIN'),
						],
					],
					'SETTINGS' => [
						'DEFAULT_VALUE' => static::STOCKS_ONLY_AVAILABLE,
						'ALLOW_NO_VALUE' => 'N',
					],
				],
			];

			if ($supportsWarehouses)
			{
				$excludeDepend = [
					'PRODUCT_RATIO_SOURCE' => true,
				];

				foreach ($commonFields as $commonFieldKey => &$commonField)
				{
					if (isset($commonField['INTRO']))
					{
						$warehouseFields['USE_WAREHOUSES']['INTRO'] = $commonField['INTRO'];
						unset($commonField['INTRO']);
					}

					$commonField['SORT'] += 5;

					if (!isset($excludeDepend[$commonFieldKey]))
					{
						$commonField['DEPEND'] = [
							'USE_WAREHOUSES' => [
								'RULE' => 'EMPTY',
								'VALUE' => true,
							],
						];
					}
				}
				unset($commonField);
			}

			$result = $warehouseFields + $commonFields;
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getPushStocksFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		global $APPLICATION;

		try
		{
			$store = $environment->getStore();

			$result = [
				'USE_PUSH_STOCKS' => [
					'TYPE' => 'boolean',
					'TAB' => 'STORE',
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_GROUP_PUSH_DATA'),
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_USE_PUSH_STOCKS'),
					'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_USE_PUSH_STOCKS_HELP'),
					'SORT' => 2200,
					'SETTINGS' => [
						'DEFAULT_VALUE' => Market\Ui\UserField\BooleanType::VALUE_Y,
					],
				],
				'WAREHOUSE_PRIMARY_FIELD' => [
					'TYPE' => 'enumeration',
					'TAB' => 'STORE',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_WAREHOUSE_PRIMARY_FIELD'),
					'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_WAREHOUSE_PRIMARY_FIELD_HELP', [
						'#LANG#' => LANGUAGE_ID,
						'#BACKURL#' => rawurlencode($APPLICATION->GetCurPageParam('')),
					]),
					'MANDATORY' => 'Y',
					'VALUES' => $store->getFieldEnum($siteId),
					'SORT' => 2205,
					'DEPEND' => [
						'USE_WAREHOUSES' => [
							'RULE' => 'EMPTY',
							'VALUE' => false,
						],
						'USE_PUSH_STOCKS' => [
							'RULE' => 'EMPTY',
							'VALUE' => false,
						],
					],
				],
			];
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getPushPricesFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return [
			'USE_PUSH_PRICES' => [
				'TYPE' => 'boolean',
				'TAB' => 'STORE',
				'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_GROUP_PUSH_DATA'),
				'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_USE_PUSH_PRICES'),
				'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_USE_PUSH_PRICES_HELP'),
				'SORT' => 2225,
			],
			'PRICES_MODE' => [
				'TYPE' => 'enumeration',
				'TAB' => 'STORE',
				'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_PRICES_MODE'),
				'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_PRICES_MODE_HELP'),
				'SORT' => 2226,
				'VALUES' => [
					[
						'ID' => static::PRICES_MODE_BUSINESS,
						'VALUE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_PRICES_MODE_BUSINESS'),
					],
					[
						'ID' => static::PRICES_MODE_CAMPAIGN,
						'VALUE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_PRICES_MODE_CAMPAIGN'),
					],
				],
				'SETTINGS' => [
					'ALLOW_NO_VALUE' => 'N',
					'DEFAULT_VALUE' => static::PRICES_MODE_BUSINESS,
				],
				'DEPEND' => [
					'USE_PUSH_PRICES' => [
						'RULE' => Market\Utils\UserField\DependField::RULE_EMPTY,
						'VALUE' => false,
					],
				],
			],
		];
	}

	protected function getProductFeedFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return [
			'PRODUCT_FEED' => [
				'TYPE' => 'enumeration',
				'TAB' => 'STORE',
				'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_PRODUCT_FEED'),
				'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_PRODUCT_FEED_HELP'),
				'MULTIPLE' => 'Y',
				'VALUES' => $this->getFeedEnum(),
				'SORT' => 2250,
				'SETTINGS' => [
					'STYLE' => 'max-width: 220px;',
					'VALIGN_PUSH' => true,
				],
				'DEPEND' => [
					'LOGIC' => 'OR',
					'USE_PUSH_STOCKS' => [
						'RULE' => 'EMPTY',
						'VALUE' => false,
					],
					'USE_PUSH_PRICES' => [
						'RULE' => 'EMPTY',
						'VALUE' => false,
					],
				],
			]
		];
	}

	protected function getFeedEnum()
	{
		$result = [];

		$query = Market\Export\Setup\Table::getList([
			'select' => [ 'ID', 'NAME', 'GROUP_NAME' => 'GROUP.NAME' ],
			'order' => [ 'GROUP.ID' => 'ASC', 'ID' => 'ASC' ],
		]);

		while ($row = $query->fetch())
		{
			$result[] = [
				'ID' => $row['ID'],
				'VALUE' => sprintf('[%s] %s', $row['ID'], $row['NAME']),
				'GROUP' => $row['GROUP_NAME'],
			];
		}

		return $result;
	}

	protected function getProductPriceFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getProductPriceFields($environment, $siteId);
		$overrides = [
			'SORT' => 2230,
		];

		if (!Market\Config::isExpertMode())
		{
			$overrides['DEPEND'] = [
				'USE_PUSH_PRICES' => [
					'RULE' => Market\Utils\UserField\DependField::RULE_EMPTY,
					'VALUE' => false,
				],
			];
		}

		return $this->applyFieldsOverrides($result, $overrides);
	}

	protected function getProductSelfTestFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = [];
		$defaults = [
			'TAB' => 'STORE',
			'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_SELF_TEST'),
			'SORT' => 2300,
		];

		foreach ($this->getSelfTestOption()->getFields($environment, $siteId) as $name => $field)
		{
			$key = sprintf('SELF_TEST[%s]', $name);

			$result[$key] = $field + $defaults;
		}

		return $result;
	}

	protected function getStatusInFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getStatusInFields($environment, $siteId);

		if (isset($result['STATUS_IN_PROCESSING_SHIPPED']))
		{
			$result['STATUS_IN_PROCESSING_SHIPPED']['DEPRECATED'] = 'Y';
		}

		return $result;
	}

	protected function getStatusShipmentFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$environmentStatus = $environment->getStatus();
		$variants = $environmentStatus->getVariants();
		$enum = $environmentStatus->getEnum($variants);
		$meaningfulMap = $environmentStatus->getMeaningfulMap();

		return [
			'STATUS_SHIPMENT_CONFIRM' => [
				'TYPE' => 'enumeration',
				'TAB' => 'STATUS',
				'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_STATUS_SHIPMENT'),
				'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_STATUS_SHIPMENT_CONFIRM'),
				'VALUES' => $enum,
				'SETTINGS' => [
					'DEFAULT_VALUE' =>
							isset($meaningfulMap[Market\Data\Trading\MeaningfulStatus::DEDUCTED])
								? $meaningfulMap[Market\Data\Trading\MeaningfulStatus::DEDUCTED]
								: null,
					'STYLE' => 'max-width: 300px;',
				],
				'SORT' => 3000,
			],
		];
	}

	public function getOrderAcceptFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return [
			'ORDER_ACCEPT_WITH_ERRORS' => [
				'TAB' => 'STORE',
				'TYPE' => 'boolean',
				'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_TROUBLESHOOTING'),
				'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_ORDER_ACCEPT_WITH_ERRORS'),
				'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_ORDER_ACCEPT_WITH_ERRORS_HELP'),
				'SORT' => 4000,
				'HIDDEN' => !Market\Config::isExpertMode() ? 'Y' : 'N',
			],
		];
	}

	protected function getFieldsetMap()
	{
		return [
			'SELF_TEST' => Options\SelfTestOption::class,
		];
	}
}