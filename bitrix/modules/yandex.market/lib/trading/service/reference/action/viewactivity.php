<?php

namespace Yandex\Market\Trading\Service\Reference\Action;

abstract class ViewActivity extends AbstractActivity
{
	abstract public function getFields();

	abstract public function getEntityValues($entity);

	public function extendFields(array $fields, array $values = null)
	{
		return $fields;
	}
}