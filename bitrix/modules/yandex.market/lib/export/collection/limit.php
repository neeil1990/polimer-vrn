<?php
namespace Yandex\Market\Export\Collection;

use Yandex\Market\Data\Number;
use Yandex\Market\Export\Entity;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Utils\UserField\DependField;

class Limit
{
	use Concerns\HasMessage;

	const ORDER_ASC = 'ASC';
	const ORDER_DESC = 'DESC';

	protected $values;

	public function __construct(array $values = [])
	{
		$this->values = $values;
	}

	public function enabled()
	{
		return $this->value('ENABLE');
	}

	public function count()
	{
		return Number::castInteger($this->value('COUNT'));
	}

	public function sortInverted()
	{
		return ($this->sortOrder() === static::ORDER_DESC);
	}

	public function sortField()
	{
		$option = (string)$this->value('SORT_FIELD');

		if ($option === '') { return null; }

		list($source, $field) = explode(':', $option, 2);

		return [
			'SOURCE' => $source,
			'FIELD' => $field,
		];
	}

	public function sortOrder()
	{
		return $this->value('SORT_ORDER');
	}

	protected function value($name)
	{
		return isset($this->values[$name]) ? $this->values[$name] : null;
	}

	public function getFields()
	{
		return [
			'ENABLE' => [
				'TYPE' => 'boolean',
				'NAME' => self::getMessage('ENABLE'),
			],
			'COUNT' => [
				'TYPE' => 'number',
				'NAME' => self::getMessage('COUNT'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 50,
				],
				'DEPEND' => [
					'ENABLE' => [
						'RULE' => DependField::RULE_EMPTY,
						'VALUE' => false,
					],
				],
			],
			'SORT_FIELD' => [
				'TYPE' => 'exportParam',
				'NAME' => self::getMessage('SORT_FIELD'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD . ':SORT',
				],
				'DEPEND' => [
					'ENABLE' => [
						'RULE' => DependField::RULE_EMPTY,
						'VALUE' => false,
					],
				],
			],
			'SORT_ORDER' => [
				'TYPE' => 'enumeration',
				'NAME' => self::getMessage('SORT_ORDER'),
				'VALUES' => $this->orderVariants(),
				'SETTINGS' => [
					'ALLOW_NO_VALUE' => 'N',
				],
				'DEPEND' => [
					'ENABLE' => [
						'RULE' => DependField::RULE_EMPTY,
						'VALUE' => false,
					],
				],
			],
		];
	}

	protected function orderVariants()
	{
		$result = [];

		foreach ([static::ORDER_ASC, static::ORDER_DESC] as $order)
		{
			$result[] = [
				'ID' => $order,
				'VALUE' => self::getMessage('ORDER_' . $order),
			];
		}

		return $result;
	}
}