<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Options;

use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class AssemblyDelayOption extends TradingService\Reference\Options\Fieldset
{
	use Market\Reference\Concerns\HasMessage;

	const TODAY = 'today';

	protected $disabledUseDefaults = false;

	public function isEmpty()
	{
		if ($this->getDays() !== null) { return false; }

		list($hour, $minutes) = Market\Data\Time::parse($this->getTimeBefore());

		return $hour === null && $minutes === null;
	}

	/** @return int|null */
	public function getDays()
	{
		$option = $this->getValue('DAY');

		if ($option === static::TODAY)
		{
			$result = 0;
		}
		else
		{
			$day = (int)$option;
			$result = $day > 0 ? $day : null;
		}

		return $result;
	}

	/** @return string|null */
	public function getTimeBefore()
	{
		return $this->getValue('TIME_BEFORE');
	}

	protected function applyValues()
	{
		$this->applyDayNoValue();
	}

	protected function applyDayNoValue()
	{
		if ((string)$this->values['DAY'] !== '0') { return; }

		$time = isset($this->values['TIME']) ? $this->values['TIME'] : $this->values['TIME_BEFORE'];
		$timeBefore = Market\Data\Time::parse($time);

		if ($timeBefore === null)
		{
			unset($this->values['DAY']);
		}
		else
		{
			$this->values['DAY'] = static::TODAY;
		}
	}

	public function applyTimeValue(ScheduleOptions $schedule)
	{
		if (empty($this->values['TIME']) || isset($this->values['TIME_BEFORE'])) { return; }

		$needTime = $this->values['TIME'];
		$scheduleTime = $schedule->firstUntilTime();
		list($diffSign, $diffTime) = Market\Data\Time::diff($scheduleTime, $needTime);

		unset($this->values['TIME']);

		if ($diffTime === null) { return; }

		list($beforeHour, $beforeMinutes) = Market\Data\Time::parse($diffTime);

		if ($diffSign === -1)
		{
			$this->values['DAY'] = (int)$this->getDays();
			$this->values['DAY'] += (int)ceil($beforeHour / 24);

			$beforeHour = (24 - $beforeHour % 24);
		}

		if ($beforeHour === 24 && $beforeMinutes === 0)
		{
			$beforeHour = 23;
			$beforeMinutes = 59;
		}

		$this->values['TIME_BEFORE'] = Market\Data\Time::makeFormatString($beforeHour, $beforeMinutes);
	}

	public function disableUseDefaults()
	{
		$this->disabledUseDefaults = true;
	}

	public function getFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return [
			'DAY' => [
				'TYPE' => 'enumeration',
				'NAME' => 'DAY',
				'VALUES' => array_merge(
					[
						[
							'ID' => static::TODAY,
							'VALUE' => self::getMessage('TODAY'),
						],
					],
					array_map(static function($index) {
						return [
							'ID' => $index,
							'VALUE' => $index . ' ' .Market\Utils::sklon($index, [
								self::getMessage('DAY_LABEL_1'),
								self::getMessage('DAY_LABEL_2'),
								self::getMessage('DAY_LABEL_5'),
							]),
						];
					}, range(1, 7))
				),
				'SETTINGS' => [
					'ALLOW_NO_VALUE' => $this->disabledUseDefaults ? 'N' : 'Y',
					'CAPTION_NO_VALUE' => self::getMessage('NO_VALUE'),
				],
			],
			'TIME_BEFORE' => [
				'TYPE' => 'time',
				'NAME' => 'TIME',
				'SETTINGS' => [
					'GLUE' => self::getMessage('TIME_BEFORE_GLUE'),
				],
				'DEPEND' => $this->disabledUseDefaults ? [] : [
					'DAY' => [
						'RULE' => Market\Utils\UserField\DependField::RULE_EMPTY,
						'VALUE' => false,
					],
				],
			],
		];
	}
}