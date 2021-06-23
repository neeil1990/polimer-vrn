<?php

namespace Yandex\Market\Utils;

use Bitrix\Main;
use Yandex\Market;

class Trace
{
	public static function getTraceUntil($functionName)
	{
		$functionNameLower = Market\Data\TextString::toLower($functionName);
		$trace = Main\Diag\Helper::getBackTrace(20, DEBUG_BACKTRACE_IGNORE_ARGS);
		$isFoundCall = false;
		$result = [];

		foreach ($trace as $traceLevel)
		{
			if ($isFoundCall)
			{
				$result[] = $traceLevel;
			}
			else if (Market\Data\TextString::toLower($traceLevel['function']) === $functionNameLower)
			{
				$isFoundCall = true;
				$result[] = $traceLevel;
			}
		}

		return $result;
	}

	public static function formatTrace($trace)
	{
		$result = '';

		foreach ($trace as $traceNum => $traceInfo)
		{
			$traceLine = '#' . $traceNum . ': ';

			if (array_key_exists('class', $traceInfo))
			{
				$traceLine .= $traceInfo['class'] . $traceInfo['type'];
			}

			if (array_key_exists('function', $traceInfo))
			{
				$traceLine .= $traceInfo['function'];
			}

			$traceLine .= "\n\t" . $traceInfo['file'] . ':' . $traceInfo['line'];

			$result .= $traceLine . "\n";
		}

		return $result;
	}

	public static function getLevelData($traceLevel)
	{
		$filePath = Main\IO\Path::normalize($traceLevel['file']);
		$docRoot = Main\IO\Path::normalize($_SERVER['DOCUMENT_ROOT']);
		$moduleName = null;

		if (Market\Data\TextString::getPosition($filePath, $docRoot) === 0)
		{
			$docRoot = rtrim($docRoot, '/');
			$filePath = Market\Data\TextString::getSubstring(
				$filePath,
				Market\Data\TextString::getLength($docRoot)
			);
		}

		if (preg_match('#(?:' . BX_ROOT . '|local)/modules/(.*?)/#i', $filePath, $matches) && Main\ModuleManager::isModuleInstalled($matches[1]))
		{
			$moduleName = $matches[1];
		}

		return array_filter([
			'file' => $filePath,
			'line' => $traceLevel['line'],
			'function' => $traceLevel['function'],
			'module' => $moduleName,
		]);
	}
}