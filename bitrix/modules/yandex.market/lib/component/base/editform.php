<?php
namespace Yandex\Market\Component\Base;

/** @property \Yandex\Market\Components\AdminFormEdit $component */
abstract class EditForm extends AbstractProvider
{
	/**
	 * @param $request
	 * @param $fields
	 *
	 * @return array
	 */
	abstract public function modifyRequest($request, $fields);

	/**
	 * @param array $select
	 * @param array|null  $item
	 *
	 * @return array
	 */
	abstract public function getFields(array $select = [], $item = null);

	/**
	 * @param       $primary
	 * @param array $select
	 *
	 * @return array
	 */
	abstract public function load($primary, array $select = [], $isCopy = false);

	/**
	 * @param $data
	 * @param $select
	 *
	 * @return array
	 */
	abstract public function extend($data, array $select = []);

	/**
	 * @param array $data
	 * @param array|null $fields
	 *
	 * @return \Bitrix\Main\Entity\Result
	 */
	abstract public function validate($data, array $fields = null);

	/**
	 * @param $primary
	 * @param $fields
	 *
	 * @return \Bitrix\Main\Entity\AddResult
	 */
	abstract public function add($fields);

	/**
	 * @param $primary
	 * @param $fields
	 *
	 * @return \Bitrix\Main\Entity\UpdateResult
	 */
	abstract public function update($primary, $fields);
}