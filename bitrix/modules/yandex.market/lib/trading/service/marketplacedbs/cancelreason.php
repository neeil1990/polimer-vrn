<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs;

use Yandex\Market;
use Bitrix\Main;

class CancelReason
{
	use Market\Reference\Concerns\HasLang;

	const SHOP_FAILED = 'SHOP_FAILED';
	const REPLACING_ORDER = 'REPLACING_ORDER';
	const USER_CHANGED_MIND = 'USER_CHANGED_MIND';

	protected $provider;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
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
			static::REPLACING_ORDER,
			static::USER_CHANGED_MIND,
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