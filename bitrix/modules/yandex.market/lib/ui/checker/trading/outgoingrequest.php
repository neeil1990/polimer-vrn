<?php

namespace Yandex\Market\Ui\Checker\Trading;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Ui\Checker;
use Yandex\Market\Trading\Setup as TradingSetup;
use Yandex\Market\Trading\Procedure as TradingProcedure;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class OutgoingRequest extends Checker\Reference\AbstractTest
{
	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function test()
	{
		$result = new Market\Result\Base();
		$testPath = 'admin/list';
		$collection = TradingSetup\Collection::loadByFilter([
			'filter' => [ '=ACTIVE' => TradingSetup\Table::BOOLEAN_Y ]
		]);

		/** @var TradingSetup\Model $setup */
		foreach ($collection as $setup)
		{
			if (!$setup->getService()->getRouter()->hasAction($testPath)) { continue; }

			try
			{
				$procedure = new TradingProcedure\Runner(TradingEntity\Registry::ENTITY_TYPE_ORDER, null);
				$procedure->run($setup, $testPath, [ 'useCache' => false ]);
			}
			catch (\Exception $exception)
			{
				if ($this->isIgnoreException($exception)) { continue; }

				$error = $this->makeError($setup, $exception);
				$result->addError($error);
			}
			catch (\Throwable $exception)
			{
				if ($this->isIgnoreException($exception)) { continue; }

				$error = $this->makeError($setup, $exception);
				$result->addError($error);
			}
		}

		return $result;
	}

	/**
	 * @param \Exception|\Throwable $exception
	 *
	 * @return bool
	 */
	protected function isIgnoreException($exception)
	{
		$message = $exception->getMessage();

		return Market\Data\TextString::getPositionCaseInsensitive($message, 'Hit rate limit') !== false;
	}

	/**
	 * @param TradingSetup\Model $setup
	 * @param \Exception|\Throwable $exception
	 *
	 * @return Market\Error\Base
	 */
	protected function makeError(TradingSetup\Model $setup, $exception)
	{
		$exceptionMessage = $this->sanitizeExceptionMessage($exception->getMessage());
		$setupName = $setup->getField('NAME');
		$description = $this->makeExceptionDescription($setup, $exceptionMessage);

		$result = new Checker\Reference\Error($exceptionMessage);
		$result->setGroup($setupName);
		$result->setDescription($description);

		return $result;
	}

	protected function sanitizeExceptionMessage($exceptionMessage)
	{
		$colonPart = ': ';
		$colonPosition = Market\Data\TextString::getPosition($exceptionMessage, $colonPart);

		if ($colonPosition !== false)
		{
			$exceptionMessage = Market\Data\TextString::getSubstring(
				$exceptionMessage,
				$colonPosition + Market\Data\TextString::getLength($colonPart)
			);
		}

		return $exceptionMessage;
	}

	protected function makeExceptionDescription(TradingSetup\Model $setup, $exceptionMessage)
	{
		if (Market\Data\TextString::getPositionCaseInsensitive($exceptionMessage, 'Required option') !== false)
		{
			$result = $this->getMessage('ERROR_REQUIRED_OPTION_DESCRIPTION', [
				'#SETTINGS_URL#' => $this->getSettingsUrl($setup),
			]);
		}
		else if (Market\Data\TextString::getPositionCaseInsensitive($exceptionMessage, 'Access denied') !== false)
		{
			$result = $this->getMessage('ERROR_ACCESS_DENIED_DESCRIPTION', [
				'#SETTINGS_URL#' => $this->getSettingsUrl($setup),
			]);
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	protected function getSettingsUrl(TradingSetup\Model $setup)
	{
		return Market\Ui\Admin\Path::getModuleUrl('trading_edit', [
			'lang' => LANGUAGE_ID,
			'service' => $setup->getServiceCode(),
			'id' => $setup->getId(),
		]);
	}

	protected function getLangPrefix()
	{
		return 'CHECKER_TEST_TRADING_OUTGOING_REQUEST';
	}
}