<?php

namespace Yandex\Market\Trading\Service\Marketplace;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Status extends TradingService\Common\Status
{
	use Market\Reference\Concerns\HasLang;

	const VIRTUAL_PAID = 'PAID';

	const STATUS_UNPAID = 'UNPAID';
	const STATUS_PENDING = 'PENDING';
	const STATUS_CANCELLED = 'CANCELLED';
	const STATUS_DELIVERED = 'DELIVERED';
	const STATUS_DELIVERY = 'DELIVERY';
	const STATUS_PICKUP = 'PICKUP';
	const STATUS_PROCESSING = 'PROCESSING';

	const STATE_STARTED = 'STARTED';
	const STATE_READY_TO_SHIP = 'READY_TO_SHIP';
	const STATE_SHOP_FAILED = 'SHOP_FAILED';
	const STATE_SHIPPED = 'SHIPPED';

	protected $provider;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(Provider $provider)
	{
		parent::__construct($provider);
	}

	public function getTitle($status, $version = '')
	{
		$statusKey = Market\Data\TextString::toUpper($status);
		$versionSuffix = ($version !== '' ? '_' . $version : '');

		return static::getLang('TRADING_SERVICE_MARKETPLACE_STATUS_' . $statusKey . $versionSuffix, null, $status);
	}

	public function getVariants()
	{
		return [
			static::STATUS_CANCELLED,
			static::STATUS_PENDING,
			static::STATE_STARTED,
			static::STATE_READY_TO_SHIP,
			static::STATE_SHIPPED,
			static::STATUS_DELIVERY,
			static::STATUS_PICKUP,
			static::STATUS_DELIVERED,
		];
	}

	public function getIncomingVariants()
	{
		$isExpert = Market\Config::isExpertMode();

		return array_keys(array_filter([
			static::VIRTUAL_CREATED => true,
			static::STATUS_PENDING => true,
			static::STATUS_CANCELLED => true,
			static::STATUS_PROCESSING => true,
			static::STATUS_PROCESSING . '_' . static::STATE_SHIPPED => $isExpert,
			static::STATUS_DELIVERY => true,
			static::STATUS_PICKUP => true,
			static::VIRTUAL_PAID => true,
			static::STATUS_DELIVERED => true,
		]));
	}

	public function getIncomingRequired()
	{
		return [
			static::STATUS_CANCELLED,
			static::STATUS_DELIVERED,
		];
	}

	public function getIncomingMeaningfulMap()
	{
		return [
			Market\Data\Trading\MeaningfulStatus::CREATED => static::VIRTUAL_CREATED,
			Market\Data\Trading\MeaningfulStatus::PAYED => static::VIRTUAL_PAID,
			Market\Data\Trading\MeaningfulStatus::PROCESSING => static::STATUS_PROCESSING,
			Market\Data\Trading\MeaningfulStatus::CANCELED => static::STATUS_CANCELLED,
			Market\Data\Trading\MeaningfulStatus::FINISHED => static::STATUS_DELIVERED,
		];
	}

	public function getOutgoingVariants()
	{
		return [
			static::STATE_READY_TO_SHIP,
			static::STATE_SHOP_FAILED,
		];
	}

	public function getOutgoingRequired()
	{
		return [
			static::STATE_READY_TO_SHIP,
			static::STATE_SHOP_FAILED,
		];
	}

	public function getOutgoingMultiple()
	{
		return [
			static::STATE_SHOP_FAILED,
		];
	}

	public function getOutgoingMeaningfulMap()
	{
		return [
			Market\Data\Trading\MeaningfulStatus::ALLOW_DELIVERY => static::STATE_READY_TO_SHIP,
			Market\Data\Trading\MeaningfulStatus::CANCELED => static::STATE_SHOP_FAILED,
		];
	}

	public function isCanceled($status, $subStatus = null)
	{
		return (
			$status === static::STATUS_CANCELLED
			|| $subStatus === static::STATE_SHOP_FAILED
		);
	}

	public function isProcessing($status)
	{
		return $status === static::STATUS_PROCESSING;
	}

	public function isConfirmed($status)
	{
		return $this->getStatusOrder($status) >= $this->getStatusOrder(static::STATUS_PROCESSING);
	}

	public function isShipped($status, $subStatus = null)
	{
		return (
			$this->getStatusOrder($status) >= $this->getStatusOrder(static::STATUS_DELIVERY)
			|| ($status === static::STATUS_PROCESSING && $subStatus === static::STATE_SHIPPED)
		);
	}

	public function isOrderDelivered($status)
	{
		return $status === static::STATUS_DELIVERED;
	}

	public function isLeftProcessing($status)
	{
		return $this->getStatusOrder($status) > $this->getStatusOrder(static::STATUS_PROCESSING);
	}

	public function getProcessOrder()
	{
		return [
			static::STATUS_UNPAID => 0,
			static::STATUS_PENDING => 1,
			static::STATUS_PROCESSING => 2,
			static::STATUS_DELIVERY => 3,
			static::STATUS_PICKUP => 4,
			static::STATUS_DELIVERED => 5,
			static::STATUS_CANCELLED => 5,
		];
	}

	public function hasSubstatus($status)
	{
		return $status === static::STATUS_PROCESSING;
	}

	public function getSubStatusProcessOrder()
	{
		return [
			static::STATE_STARTED => 1,
			static::STATE_READY_TO_SHIP => 2,
			static::STATE_SHIPPED => 3,
			static::STATE_SHOP_FAILED => 3,
		];
	}

	public function splitComplex($status)
	{
		$subStatues = $this->getSubStatuses();
		$result = null;

		if (in_array($status, $subStatues, true))
		{
			$result = [
				'status' => static::STATUS_PROCESSING,
				'substatus' => $status,
			];
		}

		return $result;
	}

	public function isChanged($orderId, $status, $substatus = null)
	{
		$storedStatusEncoded = $this->getStored($orderId);
		$result = false;

		if ($storedStatusEncoded === null)
		{
			$result = true;
		}
		else
		{
			list($storedStatus, $storedSubStatus) = explode(':', $storedStatusEncoded);
			$submitStatusOrder = $this->getStatusOrder($status);
			$storedStatusOrder = $this->getStatusOrder($storedStatus);

			if ($submitStatusOrder !== null && $submitStatusOrder < $storedStatusOrder)
			{
				$result = false;
			}
			else if ($storedStatus !== $status)
			{
				$result = true;
			}
			else if (
				$status === static::STATUS_PROCESSING
				&& $substatus !== $storedSubStatus
			)
			{
				$submitSubStatusOrder = $this->getSubStatusOrder($substatus);
				$storedSubStatusOrder = $this->getSubStatusOrder($storedSubStatus);

				$result = (
					$submitSubStatusOrder !== null
					&& $submitSubStatusOrder > $storedSubStatusOrder
				);
			}
		}

		return $result;
	}

	protected function getSubStatuses()
	{
		return [
			static::STATE_STARTED,
			static::STATE_READY_TO_SHIP,
			static::STATE_SHIPPED,
			static::STATE_SHOP_FAILED,
		];
	}
}