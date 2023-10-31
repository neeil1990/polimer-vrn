<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

class Status extends Market\Trading\Entity\Reference\Status
{
	use Market\Reference\Concerns\HasLang;

	const STATUS_CANCELED = 'CANCELED';
	const STATUS_ALLOW_DELIVERY = 'ALLOW_DELIVERY';
	const STATUS_SUBSIDY = 'SUBSIDY';
	const STATUS_PAYED = 'PAYED';
	const STATUS_DEDUCTED = 'DEDUCTED';
	
	protected $orderEnum;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getTitle($status, $version = '')
	{
		$orderEnum = $this->getOrderEnum();

		if (isset($orderEnum[$status]))
		{
			$result = $orderEnum[$status];
		}
		else
		{
			$result = $this->getSpecialTitle($status, $version);
		}

		return $result;
	}

	public function getEnum($variants)
	{
		$result = [];
		$orderVariants = $this->getOrderVariants();

		foreach ($variants as $variant)
		{
			if (in_array($variant, $orderVariants, true))
			{
				$statusName = '[' . $variant . '] ' . $this->getTitle($variant);
			}
			else
			{
				$statusName = $this->getTitle($variant);
			}

			$result[] = [
				'ID' => $variant,
				'VALUE' => $statusName,
			];
		}

		return $result;
	}

	public function getVariants()
	{
		$result = array_merge(
			$this->getSpecialVariants(),
			$this->getOrderVariants()
		);
		$result = array_diff($result, [
			static::STATUS_SUBSIDY,
		]);

		return $result;
	}

	public function isStandalone($status)
	{
		return in_array($status, $this->getSpecialVariants(), true);
	}

	public function getGroup($status)
	{
		return in_array($status, $this->getSpecialVariants(), true) ? $status : 'STATUS';
	}

	public function getMeaningfulMap()
	{
		$result = [
			Market\Data\Trading\MeaningfulStatus::CREATED => 'N',
			Market\Data\Trading\MeaningfulStatus::PROCESSING => 'P',
			Market\Data\Trading\MeaningfulStatus::ALLOW_DELIVERY => static::STATUS_ALLOW_DELIVERY,
			Market\Data\Trading\MeaningfulStatus::DEDUCTED => static::STATUS_DEDUCTED,
			Market\Data\Trading\MeaningfulStatus::SUBSIDY => static::STATUS_SUBSIDY,
			Market\Data\Trading\MeaningfulStatus::PAYED => static::STATUS_PAYED,
			Market\Data\Trading\MeaningfulStatus::CANCELED => static::STATUS_CANCELED,
			Market\Data\Trading\MeaningfulStatus::FINISHED => 'F',
		];

		if (Main\ModuleManager::isModuleInstalled('intaro.retailcrm'))
		{
			$result = $this->extendMeaningfulMapByRetailCrm($result);
		}

		return $result;
	}

	protected function extendMeaningfulMapByRetailCrm($map)
	{
		$cancelOption = (string)Main\Config\Option::get('intaro.retailcrm', 'cansel_order');

		if ($cancelOption === '') { return $map; }

		$cancelStatuses = unserialize($cancelOption, [ 'allowed_classes' => false ]);

		if (!is_array($cancelStatuses)) { return $map; }

		$cancelStatuses = array_filter($cancelStatuses, static function($status) {
			return is_string($status) && trim($status) !== '';
		});

		if (empty($cancelStatuses)) { return $map; }

		$map[Market\Data\Trading\MeaningfulStatus::CANCELED] = (array)$map[Market\Data\Trading\MeaningfulStatus::CANCELED];

		array_push($map[Market\Data\Trading\MeaningfulStatus::CANCELED], ...$cancelStatuses);

		return $map;
	}

	public function getCancelReasonMeaningfulMap()
	{
		$result = [];

		if (Main\ModuleManager::isModuleInstalled('intaro.retailcrm'))
		{
			$result = $this->extendCancelReasonMeaningfulMapByRetailCrm($result);
		}

		return $result;
	}

	protected function extendCancelReasonMeaningfulMapByRetailCrm($cancelReasonMap)
	{
		$statusMapOption = (string)Main\Config\Option::get('intaro.retailcrm', 'pay_statuses_arr');

		if ($statusMapOption === '') { return $cancelReasonMap; }

		$statusMap = unserialize($statusMapOption, [ 'allowed_classes' => false ]);

		if (!is_array($statusMap)) { return $cancelReasonMap; }

		$retailReasonMap = [
			'no-call' => Market\Data\Trading\CancelReason::USER_CHANGED_MIND,
			'already-buyed' => Market\Data\Trading\CancelReason::USER_CHANGED_MIND,
			'delyvery-did-not-suit' => Market\Data\Trading\CancelReason::SHOP_FAILED,
			'prices-did-not-suit' => Market\Data\Trading\CancelReason::SHOP_FAILED,
			'no-product' => Market\Data\Trading\CancelReason::SHOP_FAILED,
		];

		foreach ($statusMap as $status => $retailReason)
		{
			if (!isset($retailReasonMap[$retailReason])) { continue; }

			$cancelReason = $retailReasonMap[$retailReason];

			if (!isset($cancelReasonMap[$cancelReason]))
			{
				$cancelReasonMap[$cancelReason] = $status;
			}
			else
			{
				if (!is_array($cancelReasonMap[$cancelReason]))
				{
					$cancelReasonMap[$cancelReason] = (array)$cancelReasonMap[$cancelReason];
				}

				$cancelReasonMap[$cancelReason][] = $status;
			}
		}

		return $cancelReasonMap;
	}

	protected function getSpecialVariants()
	{
		return [
			static::STATUS_ALLOW_DELIVERY,
			static::STATUS_DEDUCTED,
			static::STATUS_SUBSIDY,
			static::STATUS_PAYED,
			static::STATUS_CANCELED,
		];
	}

	protected function getSpecialTitle($status, $version = '')
	{
		$statusKey = Market\Data\TextString::toUpper($status);
		$versionSuffix = ($version !== '' ? '_' . $version : '');

		return static::getLang('TRADING_ENTITY_SALE_STATUS_' . $statusKey . $versionSuffix);
	}
	
	protected function getOrderVariants()
	{
		$enum = $this->getOrderEnum();

		return array_keys($enum);
	}
	
	protected function getOrderEnum()
	{
		if ($this->orderEnum === null)
		{
			$this->orderEnum = $this->loadOrderEnum();
		}
		
		return $this->orderEnum;
	}
	
	protected function loadOrderEnum()
	{
		$result = [];
		$query = Sale\Internals\StatusTable::getList([
			'order' => [ 'SORT' => 'asc' ],
			'filter' => [ '=TYPE' => 'O', '=YAMARKET_STATUS_LANG.LID' => LANGUAGE_ID ],
			'select' => [ 'ID', 'YAMARKET_NAME' => 'YAMARKET_STATUS_LANG.NAME' ],
			'runtime' => [
				new Main\Entity\ReferenceField(
					'YAMARKET_STATUS_LANG',
					Sale\Internals\StatusLangTable::class,
					[ '=this.ID' => 'ref.STATUS_ID' ]
				)
			]
		]);

		while ($row = $query->Fetch())
		{
			$result[$row['ID']] = $row['YAMARKET_NAME'];
		}

		return $result;
	}
}