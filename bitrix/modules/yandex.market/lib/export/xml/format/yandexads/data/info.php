<?php

namespace Yandex\Market\Export\Xml\Format\YandexAds\Data;

use Yandex\Market\Reference\Concerns;

class Info
{
	use Concerns\HasMessage;

	public static function getDocumentationLink()
	{
		return 'https://yandex.ru/support/o-desktop/price-list-requirements-yml.html';
	}

	public static function getPublishNote()
	{
		return static::getMessage('PUBLISH');
	}
}