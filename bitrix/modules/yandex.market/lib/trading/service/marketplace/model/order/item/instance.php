<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model\Order\Item;

use Yandex\Market;

class Instance extends Market\Api\Reference\Model
{
	/** @return string|null */
	public function getCis()
	{
		return $this->getField('cis');
	}

	/** @return string|null */
	public function getCisFull()
	{
		return $this->getField('cisFull');
	}

	/** @return string|null */
	public function getUin()
	{
		return $this->getField('uin');
	}

	/** @return string|null */
	public function getRnpt()
	{
		return $this->getField('rnpt');
	}

	/** @return string|null */
	public function getGtd()
	{
		return $this->getField('gtd');
	}
}