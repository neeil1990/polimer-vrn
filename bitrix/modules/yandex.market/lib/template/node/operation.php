<?php

namespace Yandex\Market\Template\Node;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Iblock;

if (!Main\Loader::includeModule('iblock'))
{
	throw new Main\SystemException('require module iblock');
	return;
}

class Operation extends Iblock\Template\NodeFunction
{
	/**
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	public function process(Iblock\Template\Entity\Base $entity)
	{
		$functionObject = Market\Template\Functions\Fabric::createInstance($this->functionName);

		if ($functionObject instanceof Iblock\Template\Functions\FunctionBase)
		{
			$arguments = $functionObject->onPrepareParameters($entity, $this->parameters);
			return $functionObject->calculate($arguments);
		}

		return '';
	}
}