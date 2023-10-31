<?php

namespace Yandex\Market\Confirmation\Behavior\Meta;

use Yandex\Market;
use Bitrix\Main;

class Behavior extends Market\Confirmation\Behavior\Reference\Behavior
{
	use Market\Reference\Concerns\HasLang;

	const NAME = 'yandex-verification';

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function validate($contents)
	{
		$contents = trim($contents);
		$attributes = $this->extractAttributes($contents);

		if (!isset($attributes['name']))
		{
			$message = static::getLang('CONFIRMATION_BEHAVIOR_META_ATTRIBUTE_NAME_NOT_EXISTS');
			throw new Main\ArgumentException($message, 'contents');
		}

		if (Market\Data\TextString::toLower($attributes['name']) !== Market\Data\TextString::toLower(static::NAME))
		{
			$message = static::getLang('CONFIRMATION_BEHAVIOR_META_ATTRIBUTE_NAME_INVALID', [
				'#REQUIRED#' => static::NAME,
				'#VALUE#' => $attributes['name'],
			]);
			throw new Main\ArgumentException($message, 'contents');
		}

		if (empty($attributes['content']))
		{
			$message = static::getLang('CONFIRMATION_BEHAVIOR_META_ATTRIBUTE_CONTENT_EMPTY');
			throw new Main\ArgumentException($message, 'contents');
		}
	}

	public function formatDisplay($domain, $contents)
	{
		return
			'<a href="http://' . htmlspecialcharsbx($domain) . '" target="_blank">'
			. htmlspecialcharsbx($domain)
			. '</a>';
	}

	protected function extractAttributes($contents)
	{
		$result = [];

		if (preg_match('/^<meta (.*?)\/?>$/i', $contents, $tagMatches))
		{
			$attributes = explode(' ', trim($tagMatches[1]));

			foreach ($attributes as $attributeString)
			{
				if (preg_match('/(.*?)=["\'](.*)["\']/', $attributeString, $attributeMatches))
				{
					list(, $name, $value) = $attributeMatches;
					$name = Market\Data\TextString::toLower($name);
				}
				else
				{
					$name = Market\Data\TextString::toLower($attributeString);
					$value = '';
				}

				$result[$name] = $value;
			}
		}
		else
		{
			$message = static::getLang('CONFIRMATION_BEHAVIOR_META_TAG_NOT_EXITS');
			throw new Main\ArgumentException($message, 'contents');
		}

		return $result;
	}

	public function install($domain, $contents)
	{
		$this->registerEvent($domain, $contents, true);
		$this->clearCompositeRoot($domain);
	}

	public function uninstall($domain, $contents)
	{
		$this->registerEvent($domain, $contents, false);
	}

	protected function registerEvent($domain, $contents, $direction)
	{
		$parameters = $this->getHandlerParameters($domain, $contents);

		if ($direction)
		{
			Event::register($parameters);
		}
		else
		{
			Event::unregister($parameters);
		}
	}

	protected function getHandlerParameters($domain, $contents)
	{
		$encodedDomain = Market\Data\Domain::encode($domain);
		$encodedDomain = Market\Data\TextString::toLower($encodedDomain);

		return [
			'module' => 'main',
			'event' => 'OnEpilog',
			'method' => 'addMeta',
			'arguments' => [$encodedDomain, $contents],
		];
	}

	protected function clearCompositeRoot($domain)
	{
		if (\CHTMLPagesCache::IsCompositeEnabled())
		{
			$path = '/';
			$bytes = 0.0;
			$encodedDomain = Market\Data\Domain::encode($domain);
			$encodedDomain = Market\Data\TextString::toLower($encodedDomain);

			foreach ($this->getCompositeDomains() as $compositeDomain)
			{
				$compositeDomainEncoded = Market\Data\Domain::encode($compositeDomain);
				$compositeDomainEncoded = Market\Data\TextString::toLower($compositeDomainEncoded);

				if ($encodedDomain === $compositeDomainEncoded)
				{
					$bytes += $this->deleteCompositePage($path, $compositeDomainEncoded);
					break;
				}
			}

			\CHTMLPagesCache::updateQuota(-1 * $bytes);
		}
	}

	protected function deleteCompositePage($path, $domain)
	{
		if (class_exists(Main\Composite\Page::class))
		{
			$compositePage = new Main\Composite\Page($path, $domain);
			$changedBytes = $compositePage->delete();
		}
		else
		{
			$cachedFile = \CHTMLPagesCache::convertUriToPath($path, $domain);
			$cacheStorage = Main\Data\StaticHtmlCache::getStaticHtmlStorage($cachedFile);

			if ($cacheStorage !== null)
			{
				$changedBytes = $cacheStorage->delete();
			}
			else
			{
				$changedBytes = 0.0;
			}
		}

		return $changedBytes;
	}

	protected function getCompositeDomains()
	{
		return (array)\CHTMLPagesCache::getDomains();
	}
}