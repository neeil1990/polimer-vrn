<?php

namespace Yandex\Market\Trading\Service\Reference\Action;

use Bitrix\Main;

class PushRequest extends DataRequest
{
	/** @var string */
	public function getAction()
	{
		return $this->getField('action');
	}

	/** @return bool */
	public function isForce()
	{
		return (bool)$this->getField('force');
	}

	/** @return Main\Type\DateTime|null */
	public function getTimestamp()
	{
		return $this->getField('timestamp');
	}

	/** @return mixed|null */
	public function getOffset()
	{
		return $this->getField('offset');
	}

	/** @return mixed|null */
	public function getLimit()
	{
		return $this->getField('limit');
	}
}