<?php
namespace Yandex\Market\Watcher\Agent;

use Bitrix\Main;
use Yandex\Market\Glossary;
use Yandex\Market\Export;
use Yandex\Market\SalesBoost;

class Factory
{
	public static function processor($method, $serviceType, $setupId)
	{
		if ($serviceType === Glossary::SERVICE_EXPORT)
		{
			$result = new Export\Agent\Processor($method, $setupId);
		}
		else if ($serviceType === Glossary::SERVICE_SALES_BOOST)
		{
			$result = new SalesBoost\Agent\Processor($method, $setupId);
		}
		else
		{
			throw new Main\ArgumentException($serviceType);
		}

		return $result;
	}

	public static function setup($serviceType, $setupId)
	{
		if ($serviceType === Glossary::SERVICE_EXPORT)
		{
			$result = Export\Setup\Model::loadById($setupId);
		}
		else if ($serviceType === Glossary::SERVICE_SALES_BOOST)
		{
			$result = SalesBoost\Setup\Model::loadById($setupId);
		}
		else
		{
			throw new Main\ArgumentException($serviceType);
		}

		return $result;
	}
}