<?php

namespace Yandex\Market\Template\Node;

use Bitrix\Main;
use Bitrix\Iblock;

if (!Main\Loader::includeModule('iblock'))
{
	throw new Main\SystemException('require module iblock');
	return;
}

class Root extends Iblock\Template\NodeRoot
{
	public function processValue(Iblock\Template\Entity\Base $entity)
	{
		if (empty($this->modifiers) && count($this->children) === 1)
		{
			/** @var \Bitrix\Iblock\Template\NodeBase $child */
			$child = reset($this->children);
			$result = $child->process($entity);
		}
		else
		{
			$result = $this->process($entity);
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getChildren()
	{
		return $this->children;
	}

	public function getSourceSelect()
	{
		$result = [];

		$this->extendSourceSelect($result, $this->children);

		return $result;
	}

	protected function extendSourceSelect(&$result, $children)
	{
		foreach ($children as $child)
		{
			if ($child instanceof Field)
			{
				$sourceType = $child->getSourceType();
				$sourceField = $child->getSourceField();

				if ($sourceType !== '' && $sourceField !== '')
				{
					if (!isset($result[$sourceType]))
					{
						$result[$sourceType] = [ $sourceField ];
					}
					else if (!in_array($sourceField, $result[$sourceType]))
					{
						$result[$sourceType][] = $sourceField;
					}
				}
			}
			else if ($child instanceof Operation)
			{
				$this->extendSourceSelect($result, $child->getParameters());
			}
		}
	}
}