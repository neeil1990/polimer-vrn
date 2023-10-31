<?php

namespace Yandex\Market\Trading\Service\Marketplace;

use Yandex\Market;
use Bitrix\Main;

class Delivery
{
	use Market\Reference\Concerns\HasLang;

	const PARTNER_TYPE_YANDEX_MARKET = 'YANDEX_MARKET';
	const PARTNER_TYPE_SHOP = 'SHOP';

	const TYPE_DELIVERY = 'DELIVERY';
	const TYPE_PICKUP = 'PICKUP';
	const TYPE_POST = 'POST';
	const TYPE_DIGITAL = 'DIGITAL';

	protected $provider;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	public function isShopDelivery($partnerType)
	{
		return $partnerType === static::PARTNER_TYPE_SHOP;
	}

	public function getTypes()
	{
		return [
			static::TYPE_DELIVERY,
			static::TYPE_PICKUP,
			static::TYPE_POST,
			static::TYPE_DIGITAL,
		];
	}

	public function getDefaultType()
	{
		return static::TYPE_DELIVERY;
	}

	public function getTypeTitle($type)
	{
		return static::getLang('TRADING_SERVICE_MARKETPLACE_DELIVERY_TYPE_' . strtoupper($type));
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

	public function restrictedPaymentMethods()
	{
		return [
			static::TYPE_DIGITAL => [
				PaySystem::METHOD_YANDEX,
				PaySystem::METHOD_SBP,
				PaySystem::METHOD_APPLE_PAY,
				PaySystem::METHOD_GOOGLE_PAY,
			],
		];
	}
}