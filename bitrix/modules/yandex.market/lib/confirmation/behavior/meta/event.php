<?php

namespace Yandex\Market\Confirmation\Behavior\Meta;

use Yandex\Market;
use Bitrix\Main;

class Event extends Market\Reference\Event\Base
{
	public static function addMeta($domain, $contents)
	{
		$request = Main\Context::getCurrent()->getRequest();
		$host = $request->getHttpHost();
		$host = Market\Data\TextString::toLower($host);
		$page = $request->getRequestedPage();
		$page = static::normalizePage($page);

		if (
			$domain === $host
			&& ($page === '/' || $page === Market\Data\TextString::toLower(LANG_DIR))
		)
		{
			$assets = Main\Page\Asset::getInstance();
			$assets->addString($contents,  false,Main\Page\AssetLocation::BEFORE_CSS);
		}
	}

	protected static function normalizePage($page)
	{
		$page = Market\Data\TextString::toLower($page);
		$searchString = '/index.php';
		$searchLength = Market\Data\TextString::getLength($searchString);

		if (Market\Data\TextString::getSubstring($page, -1 * $searchLength) === $searchString)
		{
			$result = Market\Data\TextString::getSubstring($page, 0, -1 * $searchLength + 1);
		}
		else
		{
			$result = $page;
		}

		return $result;
	}
}