<?php
/** @noinspection PhpIncompatibleReturnTypeInspection */
/** @noinspection PhpReturnDocTypeMismatchInspection */
namespace Yandex\Market\Trading\Business;

use Bitrix\Main;
use Yandex\Market\Trading;
use Yandex\Market\Exceptions;
use Yandex\Market\Reference\Storage;
use Yandex\Market\Reference\Concerns;

class Model extends Storage\Model
{
	use Concerns\HasMessage;

	public static function getDataClass()
	{
		return Table::class;
	}

	public static function loadById($id)
	{
		try
		{
			$result = parent::loadById($id);
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			throw new Exceptions\Trading\SetupNotFound($exception->getMessage());
		}

		return $result;
	}

	public function isActive()
	{
		return (string)$this->getField('ACTIVE') === Table::BOOLEAN_Y;
	}

	/** @return Trading\Setup\Collection */
	public function getTradingCollection()
	{
		return $this->getChildCollection('TRADING');
	}

	public function getPrimaryTrading()
	{
		$collection = $this->getTradingCollection();
		/** @var Trading\Setup\Model $trading */
		$trading = $collection->offsetGet(0);

		if ($trading === null)
		{
			throw new Main\ObjectNotFoundException(self::getMessage('TRADING_NOT_FOUND', [
				'#BUSINESS_ID#' => $this->getId(),
			]));
		}

		if (!$trading->isActive())
		{
			throw new Main\ObjectNotFoundException(self::getMessage('TRADING_INACTIVE', [
				'#BUSINESS_ID#' => $this->getId(),
				'#TRADING_ID#' => $trading->getId(),
			]));
		}

		return $trading;
	}

	protected function getChildCollectionReference($fieldKey)
	{
		if ($fieldKey === 'TRADING')
		{
			return Trading\Setup\Collection::class;
		}

		return parent::getChildCollectionReference($fieldKey);
	}

	protected function getChildCollectionQueryParameters($fieldKey)
	{
		if ($fieldKey === 'TRADING')
		{
			return [
				'filter' => [ '=BUSINESS.ID' => $this->getId() ],
				'order' => [ 'ACTIVE' => 'DESC', 'ID' => 'ASC' ],
			];
		}

		return parent::getChildCollectionQueryParameters($fieldKey);
	}
}