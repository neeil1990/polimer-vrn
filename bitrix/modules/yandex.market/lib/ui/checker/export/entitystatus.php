<?php
namespace Yandex\Market\Ui\Checker\Export;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Ui\Checker;
use Yandex\Market\Export\Run as ExportRun;

abstract class EntityStatus extends Checker\Reference\AbstractTest
{
	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function test()
	{
		$result = new Market\Result\Base();
		$models = $this->models();
		$modelIds = array_map(static function(ExportRun\Data\EntityExportable $entity) { return $entity->getId(); }, $models);
		$entityType = $this->entityType();

		ExportRun\Data\EntityStatus::preload($entityType, $modelIds);

		foreach ($models as $model)
		{
			$modelState = ExportRun\Data\EntityStatus::modelState($model);
			$exportState = ExportRun\Data\EntityStatus::exportState($entityType, $model->getId());

			if ($modelState === ExportRun\Data\EntityStatus::STATE_READY)
			{
				if ($exportState === ExportRun\Data\EntityStatus::EXPORT_FAIL)
				{
					$resolver = $this->makeLogResolve($model);
					$error = $this->makeError($model, 'EXPORT_' . $exportState, $resolver);

					$result->addWarning($error);
				}
				else if ($exportState === ExportRun\Data\EntityStatus::EXPORT_WAIT)
				{
					$resolver = $this->makeExportResolve($model);
					$error = $this->makeError($model, 'EXPORT_' . $exportState, $resolver);

					$result->addError($error);
				}
			}
			else if (
				$exportState === ExportRun\Data\EntityStatus::EXPORT_PARTIALLY
				|| $exportState === ExportRun\Data\EntityStatus::EXPORT_READY
			)
			{
				$resolver = $this->makeDeleteResolve($model);
				$error = $this->makeError($model, 'MODEL_' . $modelState, $resolver);

				$result->addError($error);
			}
		}

		return $result;
	}

	/** @return string */
	abstract protected function entityType();

	/** @return ExportRun\Data\EntityExportable[] */
	abstract protected function models();

	protected function makeLogResolve(ExportRun\Data\EntityExportable $model)
	{
		$url = Market\Ui\Admin\Path::getModuleUrl('log', [
			'lang' => LANGUAGE_ID,
			'find_' . $this->entityType() . '_id' => $model->getId(),
			'set_filter' => 'Y',
			'apply_filter' => 'Y',
		]);

		return $this->getMessage('RESOLVE_LOG', [ '#URL#' => $url ]);
	}

	protected function makeExportResolve(ExportRun\Data\EntityExportable $model)
	{
		$url = Market\Ui\Admin\Path::getModuleUrl($this->entityType() . '_run', [
			'lang' => LANGUAGE_ID,
			'id' => $model->getId(),
		]);

		return $this->getMessage('RESOLVE_EXPORT', [ '#URL#' => $url ]);
	}

	protected function makeDeleteResolve(ExportRun\Data\EntityExportable $model)
	{
		$url = Market\Ui\Admin\Path::getModuleUrl($this->entityType() . '_run', [
			'lang' => LANGUAGE_ID,
			'id' => $model->getId(),
		]);

		return $this->getMessage('RESOLVE_DELETE', [ '#URL#' => $url ]);
	}

	protected function makeError(ExportRun\Data\EntityExportable $model, $status, $resolver = '')
	{
		$statusMessage = $this->getStatusMessage($model, $status);
		$message = sprintf(
			'[%s] %s: %s',
			$model->getId(),
			$model->getName(),
			Market\Data\TextString::lcfirst($statusMessage)
		);
		$code = $status . '_' . $model->getId();

		if ((string)$resolver !== '')
		{
			$message .= $resolver;
		}

		return new Market\Error\Base($message, $code);
	}

	protected function getStatusMessage(ExportRun\Data\EntityExportable $model, $status)
	{
		$replaces = [
			'#NEXT_DATE#' => $model->getNextActiveDate(),
		];

		return $this->getMessage('STATE_' . $status, $replaces, $status);
	}

	protected function getLangPrefix()
	{
		return 'CHECKER_TEST_EXPORT_ENTITY_STATUS';
	}
}