<?php

namespace Yandex\Market\Trading\Entity\Common;

use Yandex\Market;
use Bitrix\Main;

class Route extends Market\Trading\Entity\Reference\Route
{
	public function getScriptPath()
	{
		return $this->getPublicBasePath() . '/index.php';
	}

	public function getPublicPath($serviceCode, $urlId)
	{
		$serviceCodeSanitized = str_replace(':', '-', $serviceCode);

		return $this->getPublicBasePath() . '/' . $serviceCodeSanitized . '/' . $urlId;
	}

	public function installPublic($siteId)
	{
		$rule = $this->getUrlRewriteRule();

		Main\UrlRewriter::add($siteId, $rule);
	}

	public function uninstallPublic($siteId)
	{
		$rule = $this->getUrlRewriteRule();
		unset($rule['RULE']);

		Main\UrlRewriter::delete($siteId, $rule);
	}

	protected function getUrlRewriteRule()
	{
		$path = $this->getPublicBasePath();
		$scriptPath = $this->getScriptPath();

		return [
			'CONDITION' => '#^' . $path . '/#',
			'RULE' => '',
			'ID' => '',
			'PATH' => $scriptPath,
		];
	}

	protected function getPublicBasePath()
	{
		$moduleName = Market\Config::getModuleName();

		return BX_ROOT . '/services/' . $moduleName . '/trading';
	}
}