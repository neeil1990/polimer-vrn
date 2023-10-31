<?php

namespace Yandex\Market\Trading\UseCase;

use Yandex\Market;
use Bitrix\Main;

class AnonymousEmailBlock extends Market\Reference\Event\Regular
{
	public static function getHandlers()
	{
		return [
			[
				'module' => 'main',
				'event' => 'OnBeforeMailSend',
			],
		];
	}

	public static function onBeforeMailSend(Main\Event $event)
	{
		$eventParameters = $event->getParameters();
		$mailParameters = reset($eventParameters);
		$allRecipients = static::splitRecipients($mailParameters);
		$validRecipients = static::filterFake($allRecipients);

		if (count($allRecipients) === count($validRecipients))
		{
			$result = new Main\EventResult(Main\EventResult::SUCCESS, []);
		}
		else if (!empty($validRecipients))
		{
			$result = new Main\EventResult(Main\EventResult::SUCCESS, [
				'TO' => implode(', ', $validRecipients),
			]);
		}
		else if (!empty($mailParameters['HEADER']['BCC']) && !static::isFake($mailParameters['HEADER']['BCC']))
		{
			$result = new Main\EventResult(Main\EventResult::SUCCESS, [
				'TO' => $mailParameters['HEADER']['BCC'],
				'HEADER' => array_diff_key($mailParameters['HEADER'], [
					'BCC' => true,
				]),
			]);
		}
		else
		{
			$result = new Main\EventResult(Main\EventResult::ERROR);
		}

		return $result;
	}

	protected static function splitRecipients(array $parameters)
	{
		if (!isset($parameters['TO']) || !is_string($parameters['TO'])) { return []; }

		$result = explode(',', $parameters['TO']);
		$result = array_map('trim', $result);

		return $result;
	}

	protected static function filterFake(array $recipients)
	{
		$result = [];

		foreach ($recipients as $recipient)
		{
			if (!static::isFake($recipient))
			{
				$result[] = $recipient;
			}
		}

		return $result;
	}

	protected static function isFake($recipient)
	{
		if (!is_string($recipient)) { return false; }

		return (
			Market\Data\TextString::getPosition($recipient, 'anonymous@market.yandex.ru') !== false
			|| Market\Data\TextString::getPosition($recipient, 'noemail') === 0
		);
	}
}