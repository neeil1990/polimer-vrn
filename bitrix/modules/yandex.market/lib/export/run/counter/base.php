<?php

namespace Yandex\Market\Export\Run\Counter;

use Yandex\Market;

abstract class Base
{
	/**
	 * ����������� ������� � ��������
	 */
	abstract public function start();

	/**
	 * ���������� ���������� ��������� �� �������
	 *
	 * @param $filter array
	 * @param $context array
	 *
	 * @return int
	 */
	abstract public function count($filter, $context);

	/**
	 * ���������� ������� �� ��������� ��������
	 */
	abstract public function finish();
}