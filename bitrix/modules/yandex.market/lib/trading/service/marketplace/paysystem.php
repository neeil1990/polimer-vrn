<?php

namespace Yandex\Market\Trading\Service\Marketplace;

use Yandex\Market;
use Bitrix\Main;

class PaySystem
{
	use Market\Reference\Concerns\HasLang;

	const TYPE_PREPAID = 'PREPAID';
	const TYPE_POSTPAID = 'POSTPAID';

	const METHOD_YANDEX = 'YANDEX';
	const METHOD_SBP = 'SBP';
	const METHOD_APPLE_PAY = 'APPLE_PAY';
	const METHOD_GOOGLE_PAY = 'GOOGLE_PAY';
	const METHOD_TINKOFF_CREDIT = 'TINKOFF_CREDIT';
	const METHOD_TINKOFF_INSTALLMENTS = 'TINKOFF_INSTALLMENTS';
	const METHOD_CARD_ON_DELIVERY = 'CARD_ON_DELIVERY';
	const METHOD_CASH_ON_DELIVERY = 'CASH_ON_DELIVERY';
	const METHOD_B2B_ACCOUNT_PREPAYMENT = 'B2B_ACCOUNT_PREPAYMENT';

	const CASHBOX_CHECK_MANUAL = 'MANUAL';
	const CASHBOX_CHECK_ENABLED = 'ENABLED';
	const CASHBOX_CHECK_DISABLED = 'DISABLED';

	protected $provider;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	public function getTypes()
	{
		return [
			static::TYPE_PREPAID,
			static::TYPE_POSTPAID,
		];
	}

	public function getTypeMeaningfulMap()
	{
		return [
			static::TYPE_PREPAID => Market\Data\Trading\PaySystem::TYPE_PREPAID,
			static::TYPE_POSTPAID => Market\Data\Trading\PaySystem::TYPE_POSTPAID,
		];
	}

	public function getTypeTitle($type, $version = '')
	{
		$typeKey = Market\Data\TextString::toUpper($type);
		$versionSuffix = ($version !== '' ? '_' . $version : '');

		return static::getLang('TRADING_SERVICE_MARKETPLACE_PAY_SYSTEM_TYPE_' . $typeKey . $versionSuffix, null, $type);
	}

	public function getTypeEnum()
	{
		$result = [];

		foreach ($this->getTypes() as $type)
		{
			$result[] = [
				'ID' => $type,
				'VALUE' => $this->getTypeTitle($type),
			];
		}

		return $result;
	}

	public function isPrepaid($type)
	{
		return $type === static::TYPE_PREPAID;
	}

	public function getMethods()
	{
		return [
			static::METHOD_YANDEX,
			static::METHOD_SBP,
			static::METHOD_APPLE_PAY,
			static::METHOD_GOOGLE_PAY,
			static::METHOD_TINKOFF_CREDIT,
			static::METHOD_TINKOFF_INSTALLMENTS,
			static::METHOD_B2B_ACCOUNT_PREPAYMENT,
			static::METHOD_CARD_ON_DELIVERY,
			static::METHOD_CASH_ON_DELIVERY,
		];
	}

	public function getMethodMeaningfulMap()
	{
		return [
			static::METHOD_YANDEX => Market\Data\Trading\PaySystem::METHOD_YANDEX,
			static::METHOD_SBP => Market\Data\Trading\PaySystem::METHOD_YANDEX,
			static::METHOD_APPLE_PAY => Market\Data\Trading\PaySystem::METHOD_APPLE_PAY,
			static::METHOD_GOOGLE_PAY => Market\Data\Trading\PaySystem::METHOD_GOOGLE_PAY,
			static::METHOD_TINKOFF_CREDIT => Market\Data\Trading\PaySystem::METHOD_CREDIT,
			static::METHOD_TINKOFF_INSTALLMENTS => Market\Data\Trading\PaySystem::METHOD_CREDIT,
			static::METHOD_CARD_ON_DELIVERY => Market\Data\Trading\PaySystem::METHOD_CARD_ON_DELIVERY,
			static::METHOD_CASH_ON_DELIVERY => Market\Data\Trading\PaySystem::METHOD_CASH_ON_DELIVERY,
		];
	}

	public function getMethodTitle($method, $version = '')
	{
		$methodKey = Market\Data\TextString::toUpper($method);
		$versionSuffix = ($version !== '' ? '_' . $version : '');

		return static::getLang('TRADING_SERVICE_MARKETPLACE_PAY_SYSTEM_METHOD_' . $methodKey . $versionSuffix, null, $method);
	}

	public function getMethodEnum()
	{
		$result = [];

		foreach ($this->getUsageMap() as $type => $methods)
		{
			$typeTitle = $this->getTypeTitle($type);

			foreach ($methods as $method)
			{
				$result[] = [
					'ID' => $method,
					'VALUE' => $this->getMethodTitle($method),
					'GROUP' => $typeTitle,
				];
			}
		}

		return $result;
	}

	public function getUsageMap()
	{
		return [
			static::TYPE_PREPAID => [
				static::METHOD_YANDEX,
				static::METHOD_SBP,
				static::METHOD_APPLE_PAY,
				static::METHOD_GOOGLE_PAY,
				static::METHOD_TINKOFF_CREDIT,
				static::METHOD_TINKOFF_INSTALLMENTS,
				static::METHOD_B2B_ACCOUNT_PREPAYMENT,
			],
			static::TYPE_POSTPAID => [
				static::METHOD_CARD_ON_DELIVERY,
				static::METHOD_CASH_ON_DELIVERY,
			],
		];
	}

	public function getCashboxChecks()
	{
		return [
			static::CASHBOX_CHECK_ENABLED,
			static::CASHBOX_CHECK_DISABLED,
			static::CASHBOX_CHECK_MANUAL,
		];
	}

	public function getCashboxCheckTitle($type)
	{
		$typeKey = Market\Data\TextString::toUpper($type);

		return static::getLang('TRADING_SERVICE_MARKETPLACE_PAY_SYSTEM_CASHBOX_CHECK_' . $typeKey, null, $type);
	}

	public function getCashboxCheckEnum()
	{
		$result = [];

		foreach ($this->getCashboxChecks() as $type)
		{
			$result[] = [
				'ID' => $type,
				'VALUE' => $this->getCashboxCheckTitle($type),
			];
		}

		return $result;
	}
}