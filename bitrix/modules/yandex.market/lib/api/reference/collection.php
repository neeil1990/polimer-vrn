<?php

namespace Yandex\Market\Api\Reference;

use Yandex\Market;

abstract class Collection extends Market\Reference\Common\Collection
{
	/**
	 * @param array[] $dataList
	 * @param Market\Reference\Common\Model|null $parent
	 * @param string $relativePath
	 *
	 * @return static
	 */
	public static function initialize($dataList, Market\Reference\Common\Model $parent = null, $relativePath = '')
	{
		$result = parent::initialize($dataList, $parent);
		$result->setRelativePath($relativePath);

		return $result;
	}

	public function setRelativePath($path)
	{
		foreach ($this->collection as $index => $model)
		{
			$itemPath = $path . '[' . $index . '].';

			$model->setRelativePath($itemPath);
		}
	}
}