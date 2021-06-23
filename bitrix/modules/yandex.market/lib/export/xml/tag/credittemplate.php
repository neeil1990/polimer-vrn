<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class CreditTemplate extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'credit-template',
			'value_type' => Market\Type\Manager::TYPE_NUMBER
		];
	}

	public function exportNode($value, array $context, \SimpleXMLElement $parent, Market\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$result = $parent->addChild($this->name);
		$result->addAttribute('id', $value);

		return $result;
	}
}