<?php

namespace Yandex\Market\Trading\Service\Turbo;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Options extends TradingService\Common\Options
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
		Main\Localization\Loc::loadMessages(__FILE__);
		parent::includeMessages();
	}

	public function __construct(Provider $provider)
	{
		parent::__construct($provider);
	}

	public function getDeliveryId()
	{
		return (string)$this->getValue('DELIVERY_ID');
	}

	public function getPaySystemId($paySystemType)
	{
		$paySystemTypeUpper = Market\Data\TextString::toUpper($paySystemType);

		return (string)$this->getValue('PAY_SYSTEM_' . $paySystemTypeUpper);
	}

	public function isAllowModifyBasket()
	{
		return true;
	}

	public function isAllowModifyPrice()
	{
		return true;
	}

	public function getTitle($version = '')
	{
		return static::getLang('TRADING_SERVICE_TURBO_TITLE');
	}

	public function getTabs()
	{
		return [
			'COMMON' => [
				'name' => static::getLang('TRADING_SERVICE_TURBO_TAB_COMMON'),
				'sort' => 1000,
			],
			'STORE' => [
				'name' => static::getLang('TRADING_SERVICE_TURBO_TAB_STORE'),
				'sort' => 2000,
			],
			'STATUS' => [
				'name' => static::getLang('TRADING_SERVICE_TURBO_TAB_STATUS'),
				'sort' => 3000,
			],
		];
	}

	public function getFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return
			$this->getIncomingRequestFields($environment, $siteId)
			+ $this->getOrderPaySystemFields($environment, $siteId)
			+ $this->getOrderDeliveryFields($environment, $siteId)
			+ $this->getOrderUserRuleFields($environment, $siteId)
			+ $this->getOrderPersonFields($environment, $siteId)
			+ $this->getBuyerProfileRuleFields($environment, $siteId)
			+ $this->getOrderPropertyFields($environment, $siteId)
			+ $this->getProductPriceFields($environment, $siteId)
			+ $this->getStatusInFields($environment, $siteId);
	}

	protected function getOrderUserRuleFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = TradingService\Common\Concerns\Options\UserRegistration::getFields($environment, $siteId);

		return $this->applyFieldsOverrides($result, [
			'GROUP' => static::getLang('TRADING_SERVICE_TURBO_GROUP_PROPERTY'),
			'SORT' => 3400,
		]);
	}

	protected function getOrderPersonFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = parent::getOrderPersonFields($environment, $siteId);
		$result['PROFILE_ID'] = array_merge(
			$result['PROFILE_ID'],
			TradingService\Common\Concerns\Options\BuyerProfile::getProfileIdOverrides()
		);

		return $result;
	}

	protected function getBuyerProfileRuleFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$result = TradingService\Common\Concerns\Options\BuyerProfile::getFields($environment, $siteId);

		return $this->applyFieldsOverrides($result, [
			'SORT' => 3520,
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
			$sort = 3200;

			foreach ($servicePaySystem->getTypeVariants() as $servicePaySystemType)
			{
				$typeTitle = $servicePaySystem->getTypeTitle($servicePaySystemType);

				$result['PAY_SYSTEM_' . Market\Data\TextString::toUpper($servicePaySystemType)] = [
					'TYPE' => 'enumeration',
					'MANDATORY' => $paySystem->isRequired() ? 'Y' : 'N',
					'NAME' => static::getLang('TRADING_SERVICE_TURBO_OPTION_PAY_SYSTEM', [
						'#TYPE#' => $typeTitle,
					]),
					'GROUP' => static::getLang('TRADING_SERVICE_TURBO_GROUP_ORDER'),
					'GROUP_DESCRIPTION' => static::getLang('TRADING_SERVICE_TURBO_GROUP_ORDER_DESCRIPTION'),
					'VALUES' => $paySystemEnum,
					'SETTINGS' => [
						'DEFAULT_VALUE' => $firstPaySystem !== false ? $firstPaySystem['ID'] : null,
						'STYLE' => 'max-width: 220px;',
					],
					'SORT' => $sort,
				];

				++$sort;
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
			$firstDelivery = reset($deliveryEnum);

			$result = [
				'DELIVERY_ID' => [
					'TYPE' => 'enumeration',
					'MANDATORY' => $delivery->isRequired() ? 'Y' : 'N',
					'NAME' => static::getLang('TRADING_SERVICE_TURBO_OPTION_DELIVERY_ID'),
					'GROUP' => static::getLang('TRADING_SERVICE_TURBO_GROUP_ORDER'),
					'GROUP_DESCRIPTION' => static::getLang('TRADING_SERVICE_TURBO_GROUP_ORDER_DESCRIPTION'),
					'VALUES' => $deliveryEnum,
					'SETTINGS' => [
						'DEFAULT_VALUE' => $firstDelivery !== false ? $firstDelivery['ID'] : null,
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

	protected function getOrderPropertyFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return
			$this->getOrderPropertyUserFields($environment, $siteId)
			+ $this->getOrderPropertyUtilFields($environment, $siteId);
	}

	protected function getOrderPropertyUserFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$fields = Model\Order\User::getMeaningfulFields();
		$options = [];

		foreach ($fields as $fieldName)
		{
			$options[$fieldName] = [
				'NAME' => Model\Order\User::getMeaningfulFieldTitle($fieldName),
				'GROUP' => static::getLang('TRADING_SERVICE_TURBO_GROUP_PROPERTY'),
			];
		}

		return $this->createPropertyFields($environment, $siteId, $options, 3600);
	}
}