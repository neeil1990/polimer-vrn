<?php

namespace Yandex\Market\Trading\Service\Reference\Action;

use Yandex\Market;
use Bitrix\Main;

class Response
{
	protected $fields = [];
	protected $raw;

	public function getField($key)
	{
		return Market\Utils\Field::getChainValue($this->fields, $key);
	}

	public function getFields()
	{       
		return $this->fields;
	}

	public function setRaw($raw)
	{
		$this->raw = $raw;
	}

	public function getRaw()
	{
		return $this->raw !== null ? $this->raw : $this->fields;
	}

	public function setField($key, $value)
	{
		Market\Utils\Field::setChainValue($this->fields, $key, $value);
	}

	public function pushField($key, $value)
	{
		Market\Utils\Field::pushChainValue($this->fields, $key, $value);
	}
}