<?php

namespace Yandex\Market\Export\Entity\Formula;

use Bitrix\Main;
use Bitrix\Iblock;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Market\Export\Entity\Template\Source
	implements
		Market\Export\Entity\Reference\HasFunctions,
		Market\Export\Entity\Reference\HasFieldCompilation
{
	public function isTemplate()
	{
		return true;
	}

	public function getControl()
	{
		return Market\Export\Entity\Manager::CONTROL_FORMULA;
	}

	public function getFunctions(array $context = [])
	{
		$result = [];

		foreach (Market\Template\Functions\Registry::getTypes() as $type)
		{
			$function = Market\Template\Functions\Registry::createInstance($type);
			$title = $type;
			$isMultiple = true;

			if ($function instanceof Market\Template\Functions\HasConfiguration)
			{
				$title = $function->getTitle();
				$isMultiple = $function->isMultiple();
			}

			$result[] = [
				'ID' => $type,
				'VALUE' => $title,
				'MULTIPLE' => $isMultiple,
			];
		}

		return $result;
	}

	public function compileField($field)
	{
		if (!empty($field['FUNCTION']) && !empty($field['PARTS']))
		{
			$result = sprintf(
				'{=%s %s}',
				$field['FUNCTION'],
				implode(' ', (array)$field['PARTS'])
			);
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	public function parseField($compiledField)
	{
		$result = null;

		if (preg_match('/{=(\w+) (.*?)}/', $compiledField, $matches))
		{
			list(, $function, $partsImploded) = $matches;
			$parts = explode(' ', $partsImploded);

			$result = [
				'FUNCTION' => $function,
				'PARTS' => $parts,
			];
		}

		return $result;
	}

	protected function getLangPrefix()
	{
		return 'FORMULA_';
	}
}