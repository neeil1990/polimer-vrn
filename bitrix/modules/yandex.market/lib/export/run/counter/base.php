<?php

namespace Yandex\Market\Export\Run\Counter;

use Yandex\Market;

abstract class Base
{
	/**
	 * Подготовить ресурсы к подсчету
	 */
	abstract public function start();

	/**
	 * Подсчитать количество элементов по фильтру
	 *
	 * @param $filter array
	 * @param $context array
	 *
	 * @return int
	 */
	abstract public function count($filter, $context);

	/**
	 * Освободить ресурсы по окончанию подсчета
	 */
	abstract public function finish();
}