<?php

namespace Yandex\Market\Component\TradingSetup;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Setup as TradingSetup;
use Bitrix\Main;

class EditForm extends Market\Component\Model\EditForm
{
	use Market\Component\Concerns\HasUiService;

	protected $repository;

	public function getFields(array $select = [], $item = null)
	{
		$result = parent::getFields($select, $item);
		$result = $this->getRepository()->extendCommonFields($result);

		if (!empty($item['ID']))
		{
			$result = $this->getRepository()->markNotEditableFields($result);
		}

		return $result;
	}

	public function modifyRequest($request, $fields)
	{
		if (isset($fields['CODE'], $request['SITE_ID']) && trim($request['CODE']) === '')
		{
			$request['CODE'] = $request['SITE_ID'];
		}

		if (isset($request['NAME']) && trim($request['NAME']) === '')
		{
			unset($request['NAME']);
		}

		if (!empty($request['ID']))
		{
			$request = $this->getRepository()->unsetNotEditableValues($request);
		}

		return parent::modifyRequest($request, $fields);
	}

	public function validate($data, array $fields = null)
	{
		if ($fields !== null && !isset($data['NAME']))
		{
			$fields = array_filter($fields, static function(array $field) {
				return ($field['FIELD_NAME'] !== 'NAME');
			});
		}

		return parent::validate($data, $fields);
	}

	public function add($fields)
	{
		$result = new Main\Entity\AddResult();

		try
		{
			$modelClass = $this->getModelClass();
			$model = new $modelClass($fields);

			if (!($model instanceof TradingSetup\Model))
			{
				throw new Main\InvalidOperationException();
			}

			if (!$model->isInstalled() && TradingService\Migration::isDeprecated($model->getServiceCode()))
			{
				throw new Main\SystemException('cant install deprecated service');
			}

			$model->install();
			$model->activate();

			$result->setId($model->getId());
		}
		catch (Main\SystemException $exception)
		{
			$result->addError(new Main\Error(
				$exception->getMessage()
			));
		}

		return $result;
	}

	protected function getRepository()
	{
		if ($this->repository === null)
		{
			$this->repository = $this->makeRepository();
		}

		return $this->repository;
	}

	protected function makeRepository()
	{
		$uiService = $this->getUiService();
		$modelClass = $this->getModelClass();

		return new Repository($uiService, $modelClass);
	}
}