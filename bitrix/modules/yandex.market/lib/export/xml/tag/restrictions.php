<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class Restrictions extends Base
{
	use Market\Reference\Concerns\HasMessage;

	public function getDefaultParameters()
	{
		return [
			'name' => 'restrictions',
		];
	}

	protected function exportTagValue($tagValue, $tagValuesList, $context, \SimpleXMLElement $parent, \SimpleXMLElement $unionTag = null)
	{
		if ($tagValue === null && !$this->isRequired) { return new Market\Result\XmlNode(); } // missing in editor, then skip

		$result = parent::exportTagValue($tagValue, $tagValuesList, $context, $parent);
		$node = $result->getXmlElement();

		if ($node === null || !$result->isSuccess()) { return $result; }

		if (
			!$this->checkTagChildren($node, $result)
			|| !$this->checkTagWholesale($parent, $node, $result)
		)
		{
			$this->detachNode($parent, $node);
		}

		return $result;
	}

	protected function checkTagChildren(\SimpleXMLElement $node, Market\Result\XmlNode $tagResult)
	{
		foreach ($node->children() as $first)
		{
			$hasTrue = false;

			foreach ($first->children() as $second)
			{
				if ((string)$second === 'true')
				{
					$hasTrue = true;
					break;
				}
			}

			if (!$hasTrue)
			{
				$error = new Market\Error\XmlNode(self::getMessage('ONE_CHILD_MUST_BE_POSITIVE', [
					'#TAG#' => $first->getName(),
				]));

				$error->markCritical();
				$error->setTagName($this->name);

				$tagResult->addError($error);
			}
		}

		return $tagResult->isSuccess();
	}

	protected function checkTagWholesale(\SimpleXMLElement $parent, \SimpleXMLElement $node, Market\Result\XmlNode $tagResult)
	{
		$wholesalePath = 'trading/wholesale';
		$wholesaleNodes = $node->xpath($wholesalePath);

		if (empty($wholesaleNodes)) { return true; }

		$wholesale = (string)reset($wholesaleNodes);

		if ($wholesale !== 'true') { return true; }

		$priceTagName = $this->getParameter('wholesalePrice');
		$priceTags = $parent->xpath($priceTagName);

		if (empty($priceTags))
		{
			$error = new Market\Error\XmlNode(self::getMessage('WHOLESALE_PRICES_REQUIRED', [
				'#PRICE_TAG#' => $priceTagName,
				'#WHOLESAGE_TAG#' => $this->id . '.' . str_replace('/', '.', $wholesalePath),
			]));

			$error->markCritical();
			$error->setTagName($this->name);

			$tagResult->addError($error);
		}

		return $tagResult->isSuccess();
	}
}