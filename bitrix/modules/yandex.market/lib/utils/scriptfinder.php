<?php

namespace Yandex\Market\Utils;

use Yandex\Market\Data;
use Bitrix\Main;

class ScriptFinder
{
	protected $siteId;

	public function __construct($siteId)
	{
		$this->siteId = $siteId;
	}

	public function resolveUrl($url)
	{
		$path = $this->searchRewrite($url);

		if ($path !== null) { return $this->toAbsolute($path); }

		$path = $this->searchFile($url);

		if ($path !== null) { return $this->toAbsolute($path); }

		return null;
	}

	protected function searchRewrite($url)
	{
		$result = null;

		foreach (Main\UrlRewriter::getList($this->siteId) as $rule)
		{
			if (preg_match($rule['CONDITION'], $url))
			{
				$result = $rule['PATH'];
				break;
			}
		}

		return $result;
	}

	protected function searchFile($url)
	{
		$path = parse_url($url, PHP_URL_PATH);

		if (!is_string($path)) { return null; }

		if (!preg_match('/\.php$/', $path))
		{
			$path = rtrim($path, '/') . '/index.php';
		}

		return $path;
	}

	protected function toAbsolute($path)
	{
		$root = Data\Site::getDocumentRoot($this->siteId);

		return Main\IO\Path::combine($root, $path);
	}
}