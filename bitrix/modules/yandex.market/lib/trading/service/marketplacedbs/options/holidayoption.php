<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Options;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class HolidayOption extends TradingService\Reference\Options\Fieldset
{
	use Market\Reference\Concerns\HasMessage;

	const DATE_FORMAT = 'DD.MM';
	const DATE_GLUE = ',';

	protected $dateFormatPhp;
	protected $dateValuesCache = [];
	protected $calendar;

	protected function applyValues()
	{
		$this->applyCalendarMigration();
		$this->resetCalendar();
	}

	protected function applyCalendarMigration()
	{
		$calendarValue = (string)$this->getValue('CALENDAR');

		if ($calendarValue !== '') { return; }

		if ((string)$this->getValue('HOLIDAYS') === '' && (string)$this->getValue('WORKDAYS') === '')
		{
			$this->values['CALENDAR'] = Market\Data\Holiday\Registry::BLANK;
		}
		else
		{
			$this->values['CALENDAR'] = Market\Data\Holiday\Registry::MANUAL;
		}
	}

	public function isEmpty()
	{
		$holidays = $this->getHolidays();
		$workdays = $this->getWorkdays();

		return (empty($holidays) && empty($workdays));
	}

	public function isHoliday(Main\Type\Date $date)
	{
		$format = $this->getDateFormatPhp();
		$search = $date->format($format);

		return in_array($search, $this->getHolidays(), true);
	}

	public function isWorkday(Main\Type\Date $date)
	{
		$format = $this->getDateFormatPhp();
		$search = $date->format($format);

		return in_array($search, $this->getWorkdays(), true);
	}

	/** @return string[] */
	public function getHolidays()
	{
		return $this->getCalendar()->holidays();
	}

	/** @return string[] */
	public function getWorkdays()
	{
		return $this->getCalendar()->workdays();
	}

	/** @return IntervalOptions */
	public function getIntervals()
	{
		return $this->getFieldsetCollection('INTERVALS');
	}

	protected function getCalendar()
	{
		if ($this->calendar === null)
		{
			$this->calendar = $this->makeCalendar();
		}

		return $this->calendar;
	}

	protected function makeCalendar()
	{
		$type = $this->getValue('CALENDAR', Market\Data\Holiday\Registry::MANUAL);
		$result = Market\Data\Holiday\Registry::instance($type);

		if ($result instanceof Market\Data\Holiday\Manual)
		{
			$result->setup(
				$this->getDateValues('HOLIDAYS'),
				$this->getDateValues('WORKDAYS')
			);
		}

		return $result;
	}

	protected function resetCalendar()
	{
		$this->calendar = null;
	}

	protected function getDateValues($key)
	{
		if (!isset($this->dateValuesCache[$key]))
		{
			$this->dateValuesCache[$key] = $this->makeDateValues($key);
		}

		return $this->dateValuesCache[$key];
	}

	/**
	 * @param string $key
	 *
	 * @return string[]
	 */
	protected function makeDateValues($key)
	{
		$gluedValue = (string)$this->getValue($key);
		$values = explode(static::DATE_GLUE, $gluedValue);
		$result = [];

		foreach ($values as $value)
		{
			$value = trim($value);

			if ($value === '') { continue; }

			if (preg_match('/^\d\D/', $value)) // need 2 start digits
			{
				$value = '0' . $value;
			}

			$result[] = $value;
		}

		return $result;
	}

	public function getFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return [
			'CALENDAR' => [
				'TYPE' => 'enumeration',
				'NAME' => self::getMessage('CALENDAR'),
				'HELP_MESSAGE' => self::getMessage('CALENDAR_HELP'),
				'VALUES' => $this->getCalendarEnum(),
				'SETTINGS' => [
					'ALLOW_NO_VALUE' => 'N',
				],
			],
			'HOLIDAYS' => [
				'TYPE' => 'date',
				'NAME' => self::getMessage('HOLIDAYS'),
				'HELP_MESSAGE' => self::getMessage('HOLIDAYS_HELP'),
				'SETTINGS' => [
					'FORMAT' => static::DATE_FORMAT,
					'GLUE' => static::DATE_GLUE,
					'SIZE' => 20,
					'ROWS' => 2,
				],
				'DEPEND' => [
					'CALENDAR' => [
						'RULE' => 'ANY',
						'VALUE' => Market\Data\Holiday\Registry::MANUAL,
					],
				],
			],
			'WORKDAYS' => [
				'TYPE' => 'date',
				'NAME' => self::getMessage('WORKDAYS'),
				'HELP_MESSAGE' => self::getMessage('WORKDAYS_HELP'),
				'SETTINGS' => [
					'FORMAT' => static::DATE_FORMAT,
					'GLUE' => static::DATE_GLUE,
					'SIZE' => 20,
					'ROWS' => 2,
				],
				'DEPEND' => [
					'CALENDAR' => [
						'RULE' => 'ANY',
						'VALUE' => Market\Data\Holiday\Registry::MANUAL,
					],
				],
			],
			'INTERVALS' => $this->getIntervals()->getFieldDescription($environment, $siteId) + [
				'TYPE' => 'fieldset',
				'NAME' => self::getMessage('INTERVALS'),
				'HELP_MESSAGE' => self::getMessage('INTERVALS_HELP'),
				'SETTINGS' => [
					'VALIGN_PUSH' => true,
				],
			],
		];
	}

	protected function getCalendarEnum()
	{
		$result = [];

		foreach (Market\Data\Holiday\Registry::types() as $type)
		{
			$calendar = Market\Data\Holiday\Registry::instance($type);

			$result[] = [
				'ID' => $type,
				'VALUE' => $calendar->title(),
			];
		}

		return $result;
	}

	protected function getFieldsetCollectionMap()
	{
		return [
			'INTERVALS' => IntervalOptions::class,
		];
	}

	protected function getDateFormatPhp()
	{
		if ($this->dateFormatPhp === null)
		{
			$this->dateFormatPhp = Main\Type\Date::convertFormatToPhp(static::DATE_FORMAT);
		}

		return $this->dateFormatPhp;
	}
}
