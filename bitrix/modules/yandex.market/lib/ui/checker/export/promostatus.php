<?php

namespace Yandex\Market\Ui\Checker\Export;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Ui\Checker;
use Yandex\Market\Export\Run as ExportRun;
use Yandex\Market\Export\Promo as ExportPromo;

class PromoStatus extends Checker\Reference\AbstractTest
{
	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function test()
	{
		$result = new Market\Result\Base();
		$promoList = ExportPromo\Model::loadList();
		$promoIds = array_map(static function(ExportPromo\Model $promo) { return $promo->getId(); }, $promoList);

		ExportRun\Data\PromoStatus::preload($promoIds);

		foreach ($promoList as $promo)
		{
			$promoState = ExportRun\Data\PromoStatus::getPromoState($promo);
			$exportState = ExportRun\Data\PromoStatus::getExportState($promo);

			if ($promoState === ExportRun\Data\PromoStatus::PROMO_READY)
			{
				if ($exportState === ExportRun\Data\PromoStatus::EXPORT_FAIL)
				{
					$resolver = $this->makeLogResolve($promo);
					$error = $this->makeError($promo, 'EXPORT_' . $exportState, $resolver);

					$result->addWarning($error);
				}
				else if ($exportState === ExportRun\Data\PromoStatus::EXPORT_WAIT)
				{
					$resolver = $this->makeExportResolve($promo);
					$error = $this->makeError($promo, 'EXPORT_' . $exportState, $resolver);

					$result->addError($error);
				}
			}
			else if (
				$exportState === ExportRun\Data\PromoStatus::EXPORT_PARTIALLY
				|| $exportState === ExportRun\Data\PromoStatus::EXPORT_READY
			)
			{
				$resolver = $this->makeDeleteResolve($promo);
				$error = $this->makeError($promo, 'PROMO_' . $promoState, $resolver);

				$result->addError($error);
			}
		}

		return $result;
	}

	protected function getLastExportDate(ExportPromo\Model $promo)
	{
		$result = null;

		$query = Market\Export\Run\Storage\PromoTable::getList([
			'filter' => [
				'=ELEMENT_ID' => $promo->getId(),
				'>SETUP.REFRESH_PERIOD' => 0,
			],
			'select' => [ 'TIMESTAMP_X' ],
			'order' => [ 'TIMESTAMP_X' => 'desc' ],
			'limit' => 1,
		]);

		if ($row = $query->fetch())
		{
			$result = $row['TIMESTAMP_X'];
		}

		return $result;
	}

	protected function makeLogResolve(ExportPromo\Model $promo)
	{
		$url = Market\Ui\Admin\Path::getModuleUrl('log', [
			'lang' => LANGUAGE_ID,
			'find_promo_id' => $promo->getId(),
			'set_filter' => 'Y',
			'apply_filter' => 'Y',
		]);

		return $this->getMessage('RESOLVE_LOG', [ '#URL#' => $url ]);
	}

	protected function makeExportResolve(ExportPromo\Model $promo)
	{
		$url = Market\Ui\Admin\Path::getModuleUrl('promo_run', [
			'lang' => LANGUAGE_ID,
			'id' => $promo->getId(),
		]);

		return $this->getMessage('RESOLVE_EXPORT', [ '#URL#' => $url ]);
	}

	protected function makeDeleteResolve(ExportPromo\Model $promo)
	{
		$url = Market\Ui\Admin\Path::getModuleUrl('promo_run', [
			'lang' => LANGUAGE_ID,
			'id' => $promo->getId(),
		]);

		return $this->getMessage('RESOLVE_DELETE', [ '#URL#' => $url ]);
	}

	protected function makeError(ExportPromo\Model $promo, $status, $resolver = '')
	{
		$statusMessage = $this->getStatusMessage($promo, $status);
		$message = sprintf(
			'[%s] %s: %s',
			$promo->getId(),
			$promo->getField('NAME'),
			Market\Data\TextString::lcfirst($statusMessage)
		);
		$code = $status . '_' . $promo->getId();

		if ((string)$resolver !== '')
		{
			$message .= $resolver;
		}

		return new Market\Error\Base($message, $code);
	}

	protected function getStatusMessage(ExportPromo\Model $promo, $status)
	{
		$replaces = [
			'#NEXT_DATE#' => $promo->getNextActiveDate(),
		];

		return $this->getMessage('STATE_' . $status, $replaces, $status);
	}

	protected function getLangPrefix()
	{
		return 'CHECKER_TEST_EXPORT_PROMO_STATUS';
	}
}