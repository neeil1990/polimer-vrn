<?php

namespace Yandex\Market\Ui\Service;

class Facade
{
	public static function codeByTradingService($serviceCode)
	{
		$result = null;

		foreach (Manager::getTypes() as $uiType)
		{
			$uiService = Manager::getInstance($uiType);

			if (in_array($serviceCode, $uiService->getTradingServices(), true))
			{
				$result = $uiType;
				break;
			}
		}

		return $result;
	}
}