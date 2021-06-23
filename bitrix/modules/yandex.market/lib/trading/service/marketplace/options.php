<?php

namespace Yandex\Market\Trading\Service\Marketplace;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;

class Options extends TradingService\Common\Options
{
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

	public function getPaySystemId()
	{
		return (string)$this->getValue('PAY_SYSTEM_ID');
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

	public function getProductStores()
	{
		return (array)$this->getRequiredValue('PRODUCT_STORE');
	}

	public function isAllowModifyPrice()
	{
		return true;
	}

	/** @return Options\SelfTestOption */
	public function getSelfTestOption()
	{
		return $this->getFieldset('SELF_TEST');
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
			+ $this->getOrderPersonFields($environment, $siteId)
			+ $this->getOrderPaySystemFields($environment, $siteId)
			+ $this->getOrderDeliveryFields($environment, $siteId)
			+ $this->getOrderBasketSubsidyFields($environment, $siteId)
			+ $this->getOrderPropertyUtilFields($environment, $siteId)
			+ $this->getProductSkuMapFields($environment, $siteId)
			+ $this->getProductStoreFields($environment, $siteId)
			+ $this->getProductPriceFields($environment, $siteId)
			+ $this->getProductSelfTestFields($environment, $siteId)
			+ $this->getStatusInFields($environment, $siteId)
			+ $this->getStatusOutFields($environment, $siteId);
	}

	protected function getPersonTypeDefaultValue(TradingEntity\Reference\PersonType $personType, $siteId)
	{
		return $personType->getLegalId($siteId);
	}

	protected function getOrderPersonFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getOrderPersonFields($environment, $siteId);

		return $this->applyFieldsOverrides($result, [
			'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ORDER'),
			'GROUP_DESCRIPTION' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ORDER_DESCRIPTION'),
			'SORT' => 3200,
		]);
	}

	protected function getOrderPaySystemFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$paySystem = $environment->getPaySystem();
			$paySystemEnum = $paySystem->getEnum($siteId);
			$firstPaySystem = reset($paySystemEnum);

			$result = [
				'PAY_SYSTEM_ID' => [
					'TYPE' => 'enumeration',
					'MANDATORY' => $paySystem->isRequired() ? 'Y' : 'N',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_PAY_SYSTEM_ID'),
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ORDER'),
					'GROUP_DESCRIPTION' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ORDER_DESCRIPTION'),
					'VALUES' => $paySystemEnum,
					'SETTINGS' => [
						'DEFAULT_VALUE' => $firstPaySystem !== false ? $firstPaySystem['ID'] : null,
						'STYLE' => 'max-width: 220px;',
					],
					'SORT' => 3400,
				]
			];
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
				],
				'SUBSIDY_PAY_SYSTEM_ID' => [
					'TYPE' => 'enumeration',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_SUBSIDY_PAY_SYSTEM_ID'),
					'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTION_SUBSIDY_PAY_SYSTEM_ID_HELP'),
					'VALUES' => $paySystemEnum,
					'SETTINGS' => [
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

	protected function getOrderPropertyUtilFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getOrderPropertyUtilFields($environment, $siteId);

		return $this->applyFieldsOverrides($result, [
			'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_GROUP_ORDER_PROPERTY'),
			'SORT' => 3500,
		]);
	}

	protected function getProductSkuMapFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getProductSkuMapFields($environment, $siteId);

		return $this->applyFieldsOverrides($result, [
			'HIDDEN' => 'N',
		]);
	}

	protected function getProductPriceFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getProductPriceFields($environment, $siteId);

		if (!Market\Config::isExpertMode())
		{
			$result = $this->applyFieldsOverrides($result, [
				'HIDDEN' => 'Y',
			]);
		}

		return $result;
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

	protected function getFieldsetMap()
	{
		return [
			'SELF_TEST' => Options\SelfTestOption::class,
		];
	}
}