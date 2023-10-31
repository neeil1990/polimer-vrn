<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;
use Bitrix\Main;

class CancelReason extends TradingService\Reference\CancelReason
{
	use Market\Reference\Concerns\HasLang;

	const SHOP_FAILED = 'SHOP_FAILED';
	const PICKUP_EXPIRED = 'PICKUP_EXPIRED';
	const USER_CHANGED_MIND = 'USER_CHANGED_MIND';
	const USER_UNREACHABLE  = 'USER_UNREACHABLE';
	/** @deprecated */
	const REPLACING_ORDER = 'REPLACING_ORDER';

	protected $provider;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getDefault()
	{
		return static::USER_CHANGED_MIND;
	}

	public function getTitle($type)
	{
		return static::getLang('TRADING_SERVICE_MARKETPLACE_CANCEL_REASON_' . $type, null, $type);
	}

	public function getVariants()
	{
		return [
			static::SHOP_FAILED,
			static::PICKUP_EXPIRED,
			static::USER_CHANGED_MIND,
			static::USER_UNREACHABLE,
		];
	}

	public function resolveVariant($reason)
	{
		$reason = trim($reason);
		$result = null;

		if ($reason === '') { return $result; }

		$reasonUpper = Market\Data\TextString::toUpper($reason);
		$variants = $this->getVariants();

		if (in_array($reasonUpper, $variants, true))
		{
			$result = $reasonUpper;
		}
		else
		{
			foreach ($variants as $variant)
			{
				$title = $this->getTitle($variant);
				$titleUpper = Market\Data\TextString::toUpper($title);

				if ($reasonUpper === $titleUpper)
				{
					$result = $variant;
					break;
				}
			}
		}

		return $result;
	}
}