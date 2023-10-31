<?php

namespace Yandex\Market\Utils;

class BitrixTemplate
{
	public static function isBitrix24()
	{
		return (defined('SITE_TEMPLATE_ID') && SITE_TEMPLATE_ID === 'bitrix24');
	}
}