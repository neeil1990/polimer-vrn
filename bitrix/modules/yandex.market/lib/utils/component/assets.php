<?php

namespace Yandex\Market\Utils\Component;

use Yandex\Market;
use Bitrix\Main;

class Assets
{
	public static function preloadCss($componentName, $templateName = false)
	{
		$component = new \CBitrixComponent();

		if (!$component->InitComponent($componentName)) { return; }

		if ($templateName !== false)
		{
			$component->setTemplateName($templateName);
		}

		if (!$component->initComponentTemplate()) { return; }

		$component->__template->__IncludeCSSFile();
	}
}