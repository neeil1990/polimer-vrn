<?php

namespace Yandex\Market\Result;

use Yandex\Market;
use Bitrix\Main;

class Facade
{
	/**
	 * @param Main\Result|Market\Result\Base $result
	 * @param string $exceptionClassName
	 */
	public static function handleException($result, $exceptionClassName = Main\SystemException::class)
	{
		if (!$result->isSuccess())
		{
			$addErrorMessages = $result->getErrorMessages();
			$exceptionMessage = implode(PHP_EOL, $addErrorMessages);

			throw new $exceptionClassName($exceptionMessage);
		}
	}

	/**
	 * @param Main\Result[]|Market\Result\Base[] $results
	 *
	 * @return Main\Result|Market\Result\Base
	 */
	public static function merge(array $results)
	{
		/** @var Main\Result|Market\Result\Base $target */
		$target = array_shift($results);
		$targetSupportWarnings = method_exists($target, 'getWarnings');

		if ($target === null)
		{
			throw new Main\ArgumentException('cant merge empty results');
		}

		foreach ($results as $result)
		{
			// errors

			$errors = $result->getErrors();

			if (!empty($errors))
			{
				$target->addErrors($errors);
			}

			// warnings

			if ($targetSupportWarnings && method_exists($result, 'getWarnings'))
			{
				$target->addWarnings($result->getWarnings());
			}

			// data

			$data = (array)$result->getData();

			if (!empty($data))
			{
				$targetData = (array)$target->getData();
				$target->setData($targetData + $data);
			}
		}

		return $target;
	}
}