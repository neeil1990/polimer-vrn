<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;

class Options extends TradingService\Marketplace\Options
	implements
		TradingService\Common\Concerns\Options\UserRegistrationInterface,
		TradingService\Common\Concerns\Options\BuyerProfileInterface
{
	use TradingService\Common\Concerns\Options\HasUserRegistration;
	use TradingService\Common\Concerns\Options\HasBuyerProfile;

	/** @var Provider */
	protected $provider;

	protected static function includeMessages()
	{
		parent::includeMessages();
		Main\Localization\Loc::loadMessages(__FILE__);
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

	/**
	 * @deprecated
	 * @throws Main\NotSupportedException
	 */
	public function getPaySystemId()
	{
		throw new Main\NotSupportedException();
	}

	/**
	 * @deprecated
	 * @throws Main\NotSupportedException
	 */
	public function getDeliveryId()
	{
		throw new Main\NotSupportedException();
	}

	public function isDeliveryStrict()
	{
		return (string)$this->getValue('DELIVERY_STRICT') === Market\Reference\Storage\Table::BOOLEAN_Y;
	}

	/** @return Options\DeliveryOptions */
	public function getDeliveryOptions()
	{
		return $this->getFieldsetCollection('DELIVERY_OPTIONS');
	}

	/** @return string|null */
	public function getOutletStoreField()
	{
		return $this->getValue('OUTLET_STORE_FIELD');
	}

	public function isPaySystemStrict()
	{
		return (string)$this->getValue('PAY_SYSTEM_STRICT') === Market\Reference\Storage\Table::BOOLEAN_Y;
	}

	/** @return Options\PaySystemOptions */
	public function getPaySystemOptions()
	{
		return $this->getFieldsetCollection('PAY_SYSTEM_OPTIONS');
	}

	protected function useTaxSystem()
	{
		return false;
	}

	public function useAddressDetails()
	{
		return (string)$this->getValue('USE_ADDRESS_DETAILS') === Market\Reference\Storage\Table::BOOLEAN_Y;
	}

	public function getStatusOut($bitrixStatus)
	{
		$result = parent::getStatusOut($bitrixStatus);

		if ($result === null && $this->getCancelStatusOptions()->hasStatus($bitrixStatus))
		{
			$result = Status::STATUS_CANCELLED;
		}

		return $result;
	}

	/** @return Options\CancelStatusOptions */
	public function getCancelStatusOptions()
	{
		return $this->getFieldsetCollection('STATUS_OUT_CANCELLED_OPTION');
	}

	protected function applyValues()
	{
		$this->applyCancelOptionValues();
	}

	protected function applyCancelOptionValues()
	{
		$cancelOptions = $this->getCancelStatusOptions();
		$oldCancelStatusOutKey = 'STATUS_OUT_' . Status::STATUS_CANCELLED;
		$oldCancelStatusOut = (string)$this->getValue($oldCancelStatusOutKey);

		if ($oldCancelStatusOut !== '' && count($cancelOptions) === 0)
		{
			$cancelOptions->setValues([
				[ 'STATUS' => $oldCancelStatusOut ],
			]);
		}

		unset($this->values[$oldCancelStatusOutKey]);
	}

	public function getTabs()
	{
		$tabs = parent::getTabs() + [
			'ORDER' => [
				'name' => static::getLang('TRADING_SERVICE_MARKETPLACE_TAB_ORDER'),
				'sort' => 3000,
			],
			'DELIVERY_AND_PAYMENT' => [
				'name' => static::getLang('TRADING_SERVICE_MARKETPLACE_TAB_DELIVERY_AND_PAYMENT'),
				'sort' => 4000,
			],
		];

		$tabs['STATUS']['sort'] = 5000;

		return $tabs;
	}

	public function getFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return
			$this->getCommonFields($environment, $siteId)
			+ $this->getCompanyFields($environment, $siteId)
			+ $this->getIncomingRequestFields($environment, $siteId)
			+ $this->getOauthRequestFields($environment, $siteId)
			+ $this->getProductSkuMapFields($environment, $siteId)
			+ $this->getProductStoreFields($environment, $siteId)
			+ $this->getProductPriceFields($environment, $siteId)
			+ $this->getProductSelfTestFields($environment, $siteId)
			+ $this->getOrderUserRuleFields($environment, $siteId)
			+ $this->getOrderPersonFields($environment, $siteId)
			+ $this->getBuyerProfileRuleFields($environment, $siteId)
			+ $this->getOrderPropertyFields($environment, $siteId)
			+ $this->getDeliveryFields($environment, $siteId)
			+ $this->getOutletFields($environment, $siteId)
			+ $this->getPaySystemFields($environment, $siteId)
			+ $this->getOrderBasketSubsidyFields($environment, $siteId)
			+ $this->getAddressCommonFields($environment, $siteId)
			+ $this->getAddressDetailsFields($environment, $siteId)
			+ $this->getAddressCoordinatesFields($environment, $siteId)
			+ $this->getDeliveryDatesFields($environment, $siteId)
			+ $this->getStatusInFields($environment, $siteId)
			+ $this->getStatusOutFields($environment, $siteId)
			+ $this->getCancelledStatusOutFields($environment, $siteId)
			+ $this->getCancelReasonFields($environment, $siteId);
	}

	protected function getCommonFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getCommonFields($environment, $siteId);

		return $this->applyFieldsOverrides($result, [
			'GROUP' => static::getLang('TRADING_SERVICE_COMMON_GROUP_SERVICE_REQUEST'),
		]);
	}

	protected function getCompanyFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getCompanyFields($environment, $siteId);

		return $this->applyFieldsOverrides($result, [
			'MANDATORY' => 'N',
			'HIDDEN' => 'Y',
		]);
	}

	protected function getPersonTypeDefaultValue(TradingEntity\Reference\PersonType $personType, $siteId)
	{
		return $personType->getIndividualId($siteId);
	}

	protected function getOrderPersonFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getOrderPersonFields($environment, $siteId);
		$result['PROFILE_ID'] = array_merge(
			$result['PROFILE_ID'],
			TradingService\Common\Concerns\Options\BuyerProfile::getProfileIdOverrides()
		);

		return $this->applyFieldsOverrides($result, [
			'TAB' => 'ORDER',
			'GROUP' => null,
			'SORT' => 3100,
		]);
	}

	protected function getBuyerProfileRuleFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = TradingService\Common\Concerns\Options\BuyerProfile::getFields($environment, $siteId);

		return $this->applyFieldsOverrides($result, [
			'TAB' => 'ORDER',
			'GROUP' => null,
			'SORT' => 3110,
		]);
	}

	protected function getOrderUserRuleFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = TradingService\Common\Concerns\Options\UserRegistration::getFields($environment, $siteId);

		return $this->applyFieldsOverrides($result, [
			'TAB' => 'ORDER',
			'GROUP' => null,
			'SORT' => 3000,
		]);
	}

	protected function getOrderPropertyFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return
			$this->getOrderPropertyBuyerFields($environment, $siteId)
			+ $this->getOrderPropertyUtilFields($environment, $siteId);
	}

	protected function getOrderPropertyBuyerFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$fields = Model\Order\Buyer::getMeaningfulFields();
		$options = [];

		foreach ($fields as $fieldName)
		{
			$options[$fieldName] = [
				'TAB' => 'ORDER',
				'NAME' => Model\Order\Buyer::getMeaningfulFieldTitle($fieldName),
				'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_PROPERTY_BUYER'),
			];
		}

		return $this->createPropertyFields($environment, $siteId, $options, 3200);
	}

	protected function getOrderPropertyUtilFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getOrderPropertyUtilFields($environment, $siteId);

		return $this->applyFieldsOverrides($result, [
			'TAB' => 'ORDER',
			'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_PROPERTY_UTIL'),
			'SORT' => 3300,
		]);
	}

	protected function getDeliveryFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$deliveryOptions = $this->getDeliveryOptions();

			$result = [
				'DELIVERY_STRICT' => [
					'TYPE' => 'boolean',
					'TAB' => 'DELIVERY_AND_PAYMENT',
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_DELIVERY_GROUP'),
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_DELIVERY_STRICT'),
					'SORT' => 3000,
				],
				'DELIVERY_OPTIONS' => $deliveryOptions->getFieldDescription($environment, $siteId) + [
					'TYPE' => 'fieldset',
					'TAB' => 'DELIVERY_AND_PAYMENT',
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_DELIVERY_GROUP'),
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_DELIVERY_OPTIONS'),
					'NOTE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_DELIVERY_OPTIONS_NOTE'),
					'SORT' => 3010,
				],
			];
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getOutletFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$store = $environment->getStore();

			$result = [
				'OUTLET_STORE_FIELD' => [
					'TYPE' => 'enumeration',
					'TAB' => 'DELIVERY_AND_PAYMENT',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_OUTLET_FIELD'),
					'SORT' => 3050,
					'VALUES' => $store->getFieldEnum($siteId),
					'SETTINGS' => [
						'DEFAULT_VALUE' => $store->getOutletDefaultField(),
						'STYLE' => 'max-width: 220px;',
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

	protected function getPaySystemFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$paySystemOptions = $this->getPaySystemOptions();

			$result = [
				'PAY_SYSTEM_STRICT' => [
					'TYPE' => 'boolean',
					'TAB' => 'DELIVERY_AND_PAYMENT',
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_PAYMENT_GROUP'),
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_PAY_SYSTEM_STRICT'),
					'SORT' => 3100,
				],
				'PAY_SYSTEM_OPTIONS' => $paySystemOptions->getFieldDescription($environment, $siteId) + [
					'TYPE' => 'fieldset',
					'TAB' => 'DELIVERY_AND_PAYMENT',
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_PAYMENT_GROUP'),
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_PAY_SYSTEM_OPTIONS'),
					'SORT' => 3110,
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
		$result = parent::getOrderBasketSubsidyFields($environment, $siteId);

		return $this->applyFieldsOverrides($result, [
			'TAB' => 'DELIVERY_AND_PAYMENT',
			'SORT' => 3120,
		]);
	}

	protected function getAddressCommonFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$propertyFields = [];
			$keys = [
				'ZIP',
				'CITY',
				'ADDRESS',
			];

			foreach ($keys as $key)
			{
				$propertyFields[$key] = [
					'TAB' => 'DELIVERY_AND_PAYMENT',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_ADDRESS_' . $key, null, $key),
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ADDRESS'),
				];
			}

			$result = $this->createPropertyFields($environment, $siteId, $propertyFields, 3200);
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getAddressDetailsFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			// common

			$result = [
				'USE_ADDRESS_DETAILS' => [
					'TYPE' => 'boolean',
					'TAB' => 'DELIVERY_AND_PAYMENT',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_USE_ADDRESS_DETAILS'),
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ADDRESS'),
					'SORT' => 3250,
				],
			];

			// property map

			$propertyFields = [];

			foreach (Model\Order\Delivery\Address::getAddressFields() as $key)
			{
				$propertyFields[$key] = [
					'TAB' => 'DELIVERY_AND_PAYMENT',
					'NAME' => Model\Order\Delivery\Address::getFieldTitle($key),
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ADDRESS'),
					'DEPEND' => [
						'USE_ADDRESS_DETAILS' => [
							'RULE' => 'EMPTY',
							'VALUE' => false,
						],
					],
				];
			}

			$result += $this->createPropertyFields($environment, $siteId, $propertyFields, 3251);
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getAddressCoordinatesFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$propertyFields = [];

			foreach (Model\Order\Delivery\Address::getCoordinatesFields() as $key)
			{
				$propertyFields[$key] = [
					'TAB' => 'DELIVERY_AND_PAYMENT',
					'NAME' => Model\Order\Delivery\Address::getFieldTitle($key),
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ADDRESS'),
				];
			}

			$result = $this->createPropertyFields($environment, $siteId, $propertyFields, 3271);
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getDeliveryDatesFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$keys = [
				'DELIVERY_DATE_FROM',
				'DELIVERY_DATE_TO',
				'DELIVERY_INTERVAL_FROM',
				'DELIVERY_INTERVAL_TO',
			];

			foreach ($keys as $key)
			{
				$propertyFields[$key] = [
					'TAB' => 'DELIVERY_AND_PAYMENT',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_DELIVERY_DATES_' . $key, null, $key),
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_DELIVERY_DATES'),
				];
			}

			$result = $this->createPropertyFields($environment, $siteId, $propertyFields, 3300);
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getStatusOutFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getStatusOutFields($environment, $siteId);
		$canceledKey = 'STATUS_OUT_' . Status::STATUS_CANCELLED;

		return array_diff_key($result, [ $canceledKey => true ]);
	}

	protected function getCancelledStatusOutFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$cancelStatusOptions = $this->getCancelStatusOptions();
			$serviceStatus = $this->provider->getStatus();
			$serviceOutgoingRequired = $serviceStatus->getOutgoingRequired();
			$statusDefaults = $this->makeStatusDefaults($environment->getStatus()->getMeaningfulMap(), $serviceStatus->getOutgoingMeaningfulMap());
			$cancelReasonDefaults = $environment->getStatus()->getCancelReasonMeaningfulMap();
			$cancelReasonDefaultsMap = $this->makeCancelReasonDefaultsMap($cancelReasonDefaults);
			$defaultValues = isset($statusDefaults[Status::STATUS_CANCELLED])
				? array_map(static function($status) use ($cancelReasonDefaultsMap) {
					return [
						'STATUS' => $status,
						'CANCEL_REASON' => isset($cancelReasonDefaultsMap[$status]) ? $cancelReasonDefaultsMap[$status] : null,
					];
				}, (array)$statusDefaults[Status::STATUS_CANCELLED])
				: [];

			$result = [
				'STATUS_OUT_CANCELLED_OPTION' => $cancelStatusOptions->getFieldDescription($environment, $siteId) + [
					'TAB' => 'STATUS',
					'NAME' => sprintf('%s (%s)', $serviceStatus->getTitle(Status::STATUS_CANCELLED), Status::STATUS_CANCELLED),
					'TYPE' => 'fieldset',
					'MANDATORY' => in_array(Status::STATUS_CANCELLED, $serviceOutgoingRequired, true) ? 'Y' : 'N',
					'SETTINGS' => [
						'LAYOUT' => 'table',
						'DEFAULT_VALUE' => $defaultValues,
					],
					'SORT' => 2100,
				],
			];
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function makeCancelReasonDefaultsMap($cancelReasonDefaults)
	{
		$result = [];

		foreach ($cancelReasonDefaults as $cancelReason => $statuses)
		{
			if (is_array($statuses))
			{
				$result += array_fill_keys($statuses, $cancelReason);
			}
			else
			{
				$result[$statuses] = $cancelReason;
 			}
		}

		return $result;
	}

	protected function getCancelReasonFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$fields = [
				'REASON_CANCELED' => [
					'TAB' => 'STATUS',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_REASON_CANCELED'),
					'SETTINGS' => [
						'CAPTION_NO_VALUE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_REASON_CANCELED_CAPTION_NO_VALUE'),
						'DEFAULT_GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_REASON_CANCELED_DEFAULT_GROUP'),
						'SERVICE_CODE' => $this->provider->getCode(),
						'ADD_URL' => sprintf(
							'%s/tools/%s/orderproperty/cancelreasoncreate.php',
							BX_ROOT,
							Market\Config::getModuleName()
						),
					],
					'SORT' => 2110,
				],
			];

			$result = $this->createPropertyFields($environment, $siteId, $fields, 2100);
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getFieldsetCollectionMap()
	{
		return [
			'DELIVERY_OPTIONS' => Options\DeliveryOptions::class,
			'PAY_SYSTEM_OPTIONS' => Options\PaySystemOptions::class,
			'STATUS_OUT_CANCELLED_OPTION' => Options\CancelStatusOptions::class,
		];
	}
}