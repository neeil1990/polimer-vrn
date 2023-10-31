<?php

namespace Yandex\Market\Trading\Service\Reference\Action;

use Bitrix\Main;
use Yandex\Market\Api as Api;

abstract class FormActivity extends AbstractActivity
{
	abstract public function getFields();

	/**
	 * @param $entity
	 *
	 * @return array
	 */
	public function getEntityValues($entity)
	{
		if ($entity instanceof Api\Model\Order)
		{
			return $this->getValues($entity);
		}

		throw new Main\NotImplementedException('missing method getEntityValues');
	}

	/**
	 * @deprecated
	 * @param Api\Model\Order $order
	 *
	 * @return array
	 */
	public function getValues(Api\Model\Order $order)
	{
		throw new Main\NotImplementedException('missing method getEntityValues');
	}

	public function extendFields(array $fields, array $values = null)
	{
		return $fields;
	}

	abstract public function getPayload(array $values);
}