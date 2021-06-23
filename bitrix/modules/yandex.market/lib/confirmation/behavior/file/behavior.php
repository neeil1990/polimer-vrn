<?php

namespace Yandex\Market\Confirmation\Behavior\File;

use Yandex\Market;
use Bitrix\Main;

class Behavior extends Market\Confirmation\Behavior\Reference\Behavior
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function validate($contents)
	{
		$this->hasHtmlTags($contents, [ 'html', 'body' ]);
		$this->hasCode($contents);
	}

	protected function hasHtmlTags($contents, $tagNames)
	{
		foreach ($tagNames as $tagName)
		{
			$openTag = '<' . $tagName;
			$closeTag = '</' . $tagName . '>';

			if (Market\Data\TextString::getPosition($contents, $openTag) === false)
			{
				$message = static::getLang('CONFIRMATION_BEHAVIOR_FILE_CONTENTS_MUST_CONTAIN_TAG', [
					'#TAG#' => $tagName,
				]);
				throw new Main\ArgumentException($message, 'contents');
			}

			if (Market\Data\TextString::getPosition($contents, $closeTag) === false)
			{
				$message = static::getLang('CONFIRMATION_BEHAVIOR_FILE_CONTENTS_MUST_CONTAIN_CLOSING_TAG', [
					'#TAG#' => $tagName,
				]);
				throw new Main\ArgumentException($message, 'contents');
			}
		}
	}

	protected function hasCode($contents)
	{
		if ($this->extractCode($contents) === null)
		{
			$message = static::getLang('CONFIRMATION_BEHAVIOR_FILE_CONTENTS_MUST_CONTAIN_CODE');
			throw new Main\ArgumentException($message, 'contents');
		}
	}

	public function formatDisplay($domain, $contents)
	{
		$code = $this->extractCode($contents);
		$fileName = $this->getFileName($code);
		$url = $domain . '/' . $fileName;

		return
			'<a href="http://' . htmlspecialcharsbx($url) . '" target="_blank">'
			. htmlspecialcharsbx($fileName)
			. '</a>';
	}

	public function install($domain, $contents)
	{
		$code = $this->extractCode($contents);

		if ($code !== null)
		{
			$siteId = $this->getSiteId($domain);
			$fileName = $this->getFileName($code);
			$file = $this->createFile($fileName, $siteId);
			$putResult = $file->putContents($contents);

			if ($putResult === false)
			{
				$message = static::getLang('CONFIRMATION_BEHAVIOR_FILE_CANT_WRITE_FILE', [
					'#PATH#' => $file->getPath(),
				]);
				throw new Main\SystemException($message);
			}
		}
	}

	public function uninstall($domain, $contents)
	{
		$code = $this->extractCode($contents);

		if ($code !== null)
		{
			$siteId = $this->getSiteId($domain);
			$fileName = $this->getFileName($code);
			$file = $this->createFile($fileName, $siteId);
			$deleteResult = $file->delete();

			if ($deleteResult === false)
			{
				$message = static::getLang('CONFIRMATION_BEHAVIOR_FILE_CANT_DELETE_FILE', [
					'#PATH#' => $file->getPath(),
				]);
				throw new Main\SystemException($message);
			}
		}
	}

	protected function createFile($fileName, $siteId)
	{
		$path = Main\IO\Path::convertSiteRelativeToAbsolute('/' . $fileName, $siteId);

		return new Main\IO\File($path, $siteId);
	}

	protected function getFileName($code)
	{
		return 'yandex_' . $code . '.html';
	}

	protected function extractCode($contents)
	{
		$result = null;

		if (preg_match('/Verification: ?(\w+)/i', $contents, $matches))
		{
			$result = $matches[1];
		}

		return $result;
	}

	protected function getSiteId($domain)
	{
		$result = Market\Data\SiteDomain::getSite($domain);

		if ($result === null)
		{
			$result = Market\Data\Site::getDefault();
		}

		return $result;
	}
}