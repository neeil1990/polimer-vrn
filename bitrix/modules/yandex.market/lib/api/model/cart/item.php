<?php

namespace Yandex\Market\Api\Model\Cart;

use Yandex\Market;
use Bitrix\Main;

class Item extends Market\Api\Reference\Model
{
	public function getOfferId()
	{
		return (string)$this->getRequiredField('offerId');
	}

	public function getOfferName()
	{
		return (string)$this->getField('offerName');
	}

	public function getCount()
	{
		return (float)$this->getRequiredField('count');
	}

	public function getMeaningfulValues()
	{
		return array_filter([
			'NAME' => $this->getOfferName(),
		]);
	}
}