<?php

namespace Yandex\Market\Export\Run\Data;

use Bitrix\Main;
use Yandex\Market;

class Agent
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getNamespace()
	{
		return Market\Config::getNamespace() . '\\Export';
	}

	public static function getTitle($functionCall)
	{
		$parsed = Market\Utils\Agent::parseName($functionCall);
		$result = '';
		
		if ($parsed !== null)
		{
			$classKey = static::getAgentClassLangKey($parsed['class']);
			$methodKey = static::getAgentMethodLangKey($parsed['method']);
			$argumentReplaces = static::getAgentArgumentLangReplaces($parsed['arguments']);
			$langKey = 'EXPORT_RUN_DATA_AGENT_NAME_' . $classKey . '_' . $methodKey;

			$result = static::getLang($langKey, $argumentReplaces, '');
		}

		return $result;
	}

	protected static function getAgentClassLangKey($className)
	{
		$namespace = static::getNamespace();
		$namespaceLength = Market\Data\TextString::getLength($namespace);
		$relativeClassName = Market\Data\TextString::getSubstring($className, $namespaceLength + 1);
		$relativeClassName = preg_replace('/[^A-Z_]+/i', '_', $relativeClassName);

		return Market\Data\TextString::toUpper($relativeClassName);
	}

	protected static function getAgentMethodLangKey($method)
	{
		return Market\Data\TextString::toUpper($method);
	}

	protected static function getAgentArgumentLangReplaces(array $arguments)
	{
		$result = [];

		foreach ($arguments as $index => $value)
		{
			$result['#ARGUMENT_' . $index . '#'] = $value;
		}

		return $result;
	}
}