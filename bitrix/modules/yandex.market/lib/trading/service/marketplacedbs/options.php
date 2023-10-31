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
	public function getPaySystemId($paymentType)
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

	/** @return Options\ShipmentSchedule */
	public function getShipmentSchedule()
	{
		return $this->getFieldset('SHIPMENT_SCHEDULE');
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

	public function includeLiftPrice()
	{
		return (string)$this->getValue('INCLUDE_LIFT_PRICE') !== Market\Reference\Storage\Table::BOOLEAN_N;
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

	public function getEnvironmentFieldActions(TradingEntity\Reference\Environment $environment)
	{
		return array_filter([
			$this->getEnvironmentCancellationAcceptActions(),
			$this->getEnvironmentDeliveryDateActions(),
			$this->getEnvironmentOutletStorageLimitActions(),
			$this->getEnvironmentItemsActions(),
			$this->getEnvironmentCisActions($environment),
			$this->getEnvironmentCashboxActions(),
		]);
	}

	protected function getEnvironmentCancellationAcceptActions()
	{
		$propertyId = (string)$this->getProperty('CANCELLATION_ACCEPT');

		if ($propertyId === '') { return null; }

		$cancellationAccept = $this->provider->getCancellationAccept();
		$map = [
			Market\Data\Trading\CancellationAccept::CONFIRM => [ 'accepted' => true ],
		];

		foreach ($cancellationAccept->getReasonVariants() as $variant)
		{
			$map[Market\Data\Trading\CancellationAccept::REJECT . ':' . $variant] = [
				'accepted' => false,
				'reason' => $variant,
			];
		}

		return [
			'FIELD' => sprintf('PROPERTY_%s.VALUE', $propertyId),
			'PATH' => 'send/cancellation/accept',
			'PAYLOAD_MAP' => $map,
		];
	}

	protected function getEnvironmentDeliveryDateActions()
	{
		$propertyId = (string)$this->getProperty('DELIVERY_DATE_FROM');

		if ($propertyId === '') { return null; }

		return [
			'FIELD' => sprintf('PROPERTY_%s.VALUE', $propertyId),
			'PATH' => 'send/delivery/date',
			'PAYLOAD' => static function(array $action) {
				$value = is_array($action['VALUE']) ? reset($action['VALUE']) : $action['VALUE'];

				if (Market\Utils\Value::isEmpty($value)) { return null; }

				return [
					'date' => $value,
					'reason' => Action\SendDeliveryDate\Activity::REASON_USER_MOVED_DELIVERY_DATES,
				];
			},
		];
	}

	protected function getEnvironmentOutletStorageLimitActions()
	{
		$propertyId = (string)$this->getProperty('OUTLET_STORAGE_LIMIT_DATE');

		if ($propertyId === '') { return null; }

		return [
			'FIELD' => sprintf('PROPERTY_%s.VALUE', $propertyId),
			'PATH' => 'send/delivery/storageLimit',
			'PAYLOAD' => static function(array $action) {
				$value = is_array($action['VALUE']) ? reset($action['VALUE']) : $action['VALUE'];

				if (Market\Utils\Value::isEmpty($value)) { return null; }

				return [
					'newDate' => $value,
				];
			},
		];
	}

	protected function getEnvironmentItemsActions()
	{
		$result = parent::getEnvironmentItemsActions();

		if ($result === null) { return null; }

		/** @var callable $handler */
		$reason = $this->provider->getItemsChangeReason()->getDefault();
		$handler = $result['PAYLOAD'];

		return array_merge($result, [
			'PAYLOAD' => static function(array $action) use ($handler, $reason) {
				return $handler($action) + [
					'reason' => $reason,
				];
			},
		]);
	}

	protected function getEnvironmentCashboxActions()
	{
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
		$this->applyOrderUserRuleValues();
		$this->applyCancelOptionValues();
	}

	protected function applyOrderUserRuleValues()
	{
		$rule = $this->getUserRule();
		$disabled = $this->getUserRuleDisabled();

		if (in_array($rule, $disabled, true))
		{
			$this->values['ORDER_USER_RULE'] = $this->getUserRuleDefault();
		}
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
			+ $this->getPushStocksFields($environment, $siteId)
			+ $this->getPushPricesFields($environment, $siteId)
			+ $this->getProductPriceFields($environment, $siteId)
			+ $this->getProductFeedFields($environment, $siteId)
			+ $this->getProductSelfTestFields($environment, $siteId)
			+ $this->getOrderUserRuleFields($environment, $siteId)
			+ $this->getOrderPersonFields($environment, $siteId)
			+ $this->getBuyerProfileRuleFields($environment, $siteId)
			+ $this->getOrderPropertyFields($environment, $siteId)
			+ $this->getOrderAcceptFields($environment, $siteId)
			+ $this->getDeliveryFields($environment, $siteId)
			+ $this->getOutletFields($environment, $siteId)
			+ $this->getPaySystemFields($environment, $siteId)
			+ $this->getOrderBasketSubsidyFields($environment, $siteId)
			+ $this->getAddressCommonFields($environment, $siteId)
			+ $this->getAddressDetailsFields($environment, $siteId)
			+ $this->getDeliveryLiftFields($environment, $siteId)
			+ $this->getAddressCoordinatesFields($environment, $siteId)
			+ $this->getDeliveryDispatchTypeFields($environment, $siteId)
			+ $this->getDeliveryDatesFields($environment, $siteId)
			+ $this->getStatusInFields($environment, $siteId)
			+ $this->getCancellationAcceptFields($environment, $siteId)
			+ $this->getStatusOutFields($environment, $siteId)
			+ $this->getCancelledStatusOutFields($environment, $siteId)
			+ $this->getCancelReasonFields($environment, $siteId)
			+ $this->getStatusOutSyncFields($environment, $siteId);
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

	protected function getUserRuleDefault()
	{
		return TradingService\Common\Concerns\Options\UserRegistrationInterface::USER_RULE_ANONYMOUS;
	}

	protected function getUserRuleDisabled()
	{
		return [
			TradingService\Common\Concerns\Options\UserRegistrationInterface::USER_RULE_MATCH_EMAIL,
			TradingService\Common\Concerns\Options\UserRegistrationInterface::USER_RULE_MATCH_PHONE,
		];
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
			'HIDDEN' => Market\Config::isExpertMode() ? 'N' : 'Y',
		]);
	}

	protected function getOrderUserRuleFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$fields = TradingService\Common\Concerns\Options\UserRegistration::getFields($environment, $siteId);
		$fields = $this->extendOrderUserRuleFields($fields);

		return $this->applyFieldsOverrides($fields, [
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
		$result = parent::getOrderPropertyBuyerFields($environment, $siteId);

		return $this->applyFieldsOverrides($result, [
			'TAB' => 'ORDER',
			'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_PROPERTY_BUYER'),
			'SORT' => 3200,
		]);
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
			$shipmentSchedule = $this->getShipmentSchedule();

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
				'SHIPMENT_SCHEDULE' => $shipmentSchedule->getFieldDescription($environment, $siteId) + [
					'TYPE' => 'fieldset',
					'TAB' => 'DELIVERY_AND_PAYMENT',
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_DELIVERY_GROUP'),
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_SHIPMENT_SCHEDULE'),
					'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_SHIPMENT_SCHEDULE_HELP'),
					'SORT' => 3020,
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

			$propertyFields['LIFT_TYPE'] = [
				'TAB' => 'DELIVERY_AND_PAYMENT',
				'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_LIFT_TYPE'),
				'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ADDRESS'),
				'DEPEND' => [
					'USE_ADDRESS_DETAILS' => [
						'RULE' => 'EMPTY',
						'VALUE' => false,
					],
				],
				'SETTINGS' => [
					'SERVICE_CODE' => $this->provider->getCode(),
					'ADD_URL' => Market\Ui\Admin\Path::getToolsUrl('OrderProperty/LiftTypeCreate'),
				],
			];

			$result += $this->createPropertyFields($environment, $siteId, $propertyFields, 3251);
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getDeliveryLiftFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$propertyFields = [
				'LIFT_TYPE' => [
					'TAB' => 'DELIVERY_AND_PAYMENT',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_LIFT_TYPE'),
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ADDRESS'),
					'DEPEND' => [
						'USE_ADDRESS_DETAILS' => [
							'RULE' => 'EMPTY',
							'VALUE' => false,
						],
					],
					'SETTINGS' => [
						'SERVICE_CODE' => $this->provider->getCode(),
						'ADD_URL' => Market\Ui\Admin\Path::getToolsUrl('OrderProperty/LiftTypeCreate'),
					],
				],
				'LIFT_PRICE' => [
					'TAB' => 'DELIVERY_AND_PAYMENT',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_LIFT_PRICE'),
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ADDRESS'),
					'DEPEND' => [
						'USE_ADDRESS_DETAILS' => [
							'RULE' => 'EMPTY',
							'VALUE' => false,
						],
					],
				],
			];

			$result = $this->createPropertyFields($environment, $siteId, $propertyFields, 3265);
			$result += [
				'INCLUDE_LIFT_PRICE' => [
					'TAB' => 'DELIVERY_AND_PAYMENT',
					'TYPE' => 'boolean',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_INCLUDE_LIFT_PRICE'),
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ADDRESS'),
					'SORT' => max(...array_column($result, 'SORT')) + 1,
					'DEPEND' => [
						'USE_ADDRESS_DETAILS' => [
							'RULE' => 'EMPTY',
							'VALUE' => false,
						],
					],
					'SETTINGS' => [
						'DEFAULT_VALUE' => Market\Ui\UserField\BooleanType::VALUE_Y,
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

	protected function getDeliveryDispatchTypeFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$propertyFields = [
				'DISPATCH_TYPE' => [
					'TAB' => 'DELIVERY_AND_PAYMENT',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_DISPATCH_TYPE'),
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ADDRESS'),
				],
			];

			$result = $this->createPropertyFields($environment, $siteId, $propertyFields, 3281);
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
				'DELIVERY_REAL_DATE',
				'OUTLET_STORAGE_LIMIT_DATE',
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

	protected function getCancellationAcceptFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$fields = [
				'CANCELLATION_ACCEPT' => [
					'TAB' => 'STATUS',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_CANCELLATION_ACCEPT'),
					'DESCRIPTION' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_CANCELLATION_ACCEPT_DESCRIPTION', [
						'#NOTIFICATION_TEMPLATE#' => $this->compileNotificationMakeDescription('order/cancellation/notify', $siteId),
					]),
					'SETTINGS' => [
						'SERVICE_CODE' => $this->provider->getCode(),
						'ADD_URL' => Market\Ui\Admin\Path::getToolsUrl('OrderProperty/CancellationAcceptCreate'),
					],
				],
			];

			$result = $this->createPropertyFields($environment, $siteId, $fields, 1100);
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function compileNotificationMakeDescription($path, $siteId)
	{
		$behaviors = array_filter([
			'EMAIL' => true,
			'SMS' => (new Market\Ui\Trading\Notification\SmsRepository())->isSupported(),
		]);
		$parts = [];
		$queryData = [
			'lang' => LANGUAGE_ID,
			'service' => $this->provider->getCode(),
			'path' => $path,
			'site' => $siteId,
			'sessid' => bitrix_sessid(),
		];

		foreach ($behaviors as $behavior => $supported)
		{
			$replaces = [
				'#URL#' => Market\Ui\Admin\Path::getModuleUrl(
					'trading_notification_template',
					$queryData + [ 'type' => $behavior ]
				),
			];

			$parts[] = static::getLang(
				'TRADING_SERVICE_MARKETPLACE_NOTIFICATION_BEHAVIOR_' . $behavior,
				$replaces,
				sprintf('<a href="%s" target="_blank">%s</a>', $replaces['#URL#'], $behavior)
			);
		}

		return implode(
			static::getLang('TRADING_SERVICE_MARKETPLACE_NOTIFICATION_BEHAVIOR_GLUE'),
			$parts
		);
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
						'VALIGN_PUSH' => true,
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
						'ADD_URL' => Market\Ui\Admin\Path::getToolsUrl('OrderProperty/CancelReasonCreate'),
					],
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
		return array_merge(parent::getFieldsetCollectionMap(), [
			'DELIVERY_OPTIONS' => Options\DeliveryOptions::class,
			'PAY_SYSTEM_OPTIONS' => Options\PaySystemOptions::class,
			'STATUS_OUT_CANCELLED_OPTION' => Options\CancelStatusOptions::class,
		]);
	}

	protected function getFieldsetMap()
	{
		return array_merge(parent::getFieldsetMap(), [
			'SHIPMENT_SCHEDULE' => Options\ShipmentSchedule::class,
		]);
	}
}