<?php

namespace Yandex\Market\Api\Partner\Outlets;

use Bitrix\Main;
use Yandex\Market;

class Response extends Market\Api\Partner\Reference\Response
{
	use Market\Reference\Concerns\HasOnce;

	protected static function includeMessages()
	{
		parent::includeMessages();
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function validate()
	{
		$result = parent::validate();

		if (!$result->isSuccess())
		{
			// nothing
		}
		else if ($orderError = $this->validateOutletsData())
		{
			$result->addError($orderError);
		}

		return $result;
	}

	/** @return Market\Api\Model\OutletCollection */
	public function getOutletCollection()
	{
		return $this->once('loadOutletCollection');
	}

	protected function loadOutletCollection()
	{
		$dataList = (array)$this->getField('outlets');
		$pager = $this->getPaging();

		$collection = Market\Api\Model\OutletCollection::initialize($dataList);
		$collection->setPaging($pager);

		return $collection;
	}

	protected function validateOutletsData()
	{
		$result = null;
		$dataList = $this->getField('outlets');

		if (!is_array($dataList))
		{
			$message = static::getLang('API_OUTLETS_RESPONSE_HASNT_OUTLETS');
			$result = new Main\Error($message);
		}

		return $result;
	}

	public function getPaging()
	{
		$data = (array)$this->getField('paging');

		return new Market\Api\Model\Paging($data);
	}
}
