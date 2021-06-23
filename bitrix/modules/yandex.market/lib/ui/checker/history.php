<?php

namespace Yandex\Market\Ui\Checker;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Ui\Checker;

class History
{
	protected $resolved = [];
	protected $errors = [];

	public function register(Checker\Reference\AbstractTest $test, Market\Result\Base $testResult)
	{
		if ($testResult->isSuccess())
		{
			$this->registerResolved($test);
		}
		else
		{
			$this->registerErrors($test, $testResult);
		}
	}

	protected function registerResolved(Checker\Reference\AbstractTest $test)
	{
		$this->resolved[] = $this->getTestIdentifier($test);
	}

	protected function registerErrors(Checker\Reference\AbstractTest $test, Market\Result\Base $testResult)
	{
		$existsRecords = $this->getExistRecords($test);
		$existsMessages = array_column($existsRecords, 'MESSAGE');
		$existsCodes = array_column($existsRecords, 'CODE');

		foreach ($testResult->getErrors() as $error)
		{
			$message = $error->getMessage();
			$code = (string)($error->getCode() ?: 0);
			$message = $this->sanitizeMessage($message);

			if ($error instanceof Checker\Reference\Error)
			{
				$group = (string)$error->getGroup();

				if ($group !== '')
				{
					$message = $group . ': ' . $message;
				}
			}

			if (in_array($message, $existsMessages, true)) { continue; }
			if ($code !== '0' && in_array($code, $existsCodes, true)) { continue; }

			$existsMessages[] = $message;
			$existsCodes[] = $code;

			$this->errors[] = [
				'TEST' => $this->getTestIdentifier($test),
				'MESSAGE' => $message,
				'CODE' => $code,
				'TIMESTAMP_X' => new Main\Type\DateTime(),
				'RESOLVED' => Internals\HistoryTable::BOOLEAN_N,
			];
		}
	}

	protected function sanitizeMessage($message)
	{
		$message = strip_tags($message, '<br>');
		$message = preg_replace('#<br.*?>#', PHP_EOL, $message);

		return $message;
	}

	public function hasErrors()
	{
		return !empty($this->errors);
	}

	public function flush()
	{
		$this->flushResolved();
		$this->flushErrors();
	}

	protected function flushResolved()
	{
		if (empty($this->resolved)) { return; }

		Internals\HistoryTable::updateBatch([
			'filter' => [ '=TEST' => $this->resolved ],
		], [
			'RESOLVED' => Internals\HistoryTable::BOOLEAN_Y,
		]);
	}

	protected function flushErrors()
	{
		if (empty($this->errors)) { return; }

		Internals\HistoryTable::addBatch($this->errors);
	}

	protected function getExistRecords(Checker\Reference\AbstractTest $test)
	{
		$result = [];

		$query = Internals\HistoryTable::getList([
			'filter' => [
				'=TEST' => $this->getTestIdentifier($test),
				'=RESOLVED' => Internals\HistoryTable::BOOLEAN_N,
			],
			'select' => [ 'MESSAGE', 'CODE' ],
		]);

		while ($row = $query->fetch())
		{
			$result[] = $row;
		}

		return $result;
	}

	protected function getTestIdentifier(Checker\Reference\AbstractTest $test)
	{
		return get_class($test);
	}
}