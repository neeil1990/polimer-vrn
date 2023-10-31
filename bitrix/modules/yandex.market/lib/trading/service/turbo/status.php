<?php

namespace Yandex\Market\Trading\Service\Turbo;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Status extends TradingService\Common\Status
{
	use Market\Reference\Concerns\HasLang;

	const STATUS_UNPAID = 'UNPAID';
	const STATUS_CANCELLED = 'CANCELLED';
	const STATUS_PROCESSING = 'PROCESSING';

	const COMPLEX_PROCESSING_PREPAID = 'PROCESSING_PREPAID';

	protected $provider;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(Provider $provider)
	{
		parent::__construct($provider);
	}

	public function getVariants()
	{
		return [
			static::STATUS_UNPAID,
			static::STATUS_CANCELLED,
			static::STATUS_PROCESSING,
		];
	}

	public function getTitle($status, $version = '')
	{
		$statusKey = Market\Data\TextString::toUpper($status);
		$versionSuffix = ($version !== '' ? '_' . $version : '');

		return static::getLang('TRADING_SERVICE_TURBO_STATUS_' . $statusKey . $versionSuffix, null, $status);
	}

	public function getIncomingVariants()
	{
		return [
			static::VIRTUAL_CREATED,
			static::STATUS_CANCELLED,
			static::COMPLEX_PROCESSING_PREPAID,
		];
	}

	public function getIncomingRequired()
	{
		return [];
	}

	public function getIncomingMeaningfulMap()
	{
		return [
			Market\Data\Trading\MeaningfulStatus::CREATED => static::VIRTUAL_CREATED,
			Market\Data\Trading\MeaningfulStatus::CANCELED => static::STATUS_CANCELLED,
			Market\Data\Trading\MeaningfulStatus::PAYED => static::COMPLEX_PROCESSING_PREPAID,
		];
	}

	public function getOutgoingVariants()
	{
		return [];
	}

	public function getOutgoingRequired()
	{
		return [];
	}

	public function getOutgoingMeaningfulMap()
	{
		return [];
	}

	public function isCanceled($status, $subStatus = null)
	{
		return $status === static::STATUS_CANCELLED;
	}

	public function isProcessing($status)
	{
		return $status === static::STATUS_PROCESSING;
	}

	public function isConfirmed($status)
	{
		return $status !== static::STATUS_UNPAID && $this->isCanceled($status);
	}

	public function isLeftProcessing($status)
	{
		return !$this->isProcessing($status) && $this->isConfirmed($status);
	}
}