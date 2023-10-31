<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class Root extends Base
{
	public function validate($value, array $context, $siblingsValues = null, Market\Result\XmlNode $nodeResult = null, $settings = null)
	{
		return true;
	}

	public function exportNode($value, array $context, \SimpleXMLElement $parent, Market\Result\XmlNode $nodeResult = null, $settings = null)
	{
		if ($nodeResult !== null)
		{
			$valueExport = $nodeResult->addReplace('');
		}
		else
		{
			$valueExport = ' ';
		}

		return $parent->addChild($this->name, $valueExport);
	}
}