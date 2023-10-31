<?php

namespace Yandex\Market\Trading\State;

use Bitrix\Main;
use Yandex\Market;

class PushAgent extends Internals\AgentSkeleton
{
	use Market\Reference\Concerns\HasMessage;

	const ACTION_REFRESH = 'refresh';
	const ACTION_CHANGE = 'change';

	const PERIOD_STEP_DEFAULT = 5;
	const NOTIFY_DISABLED = 'PUSH_AGENT_DISABLED';
	const NOTIFY_NOT_ALLOWED = 'PUSH_AGENT_NOT_ALLOWED';

	public static function getDefaultParams()
	{
		return [
			'sort' => 300, // more priority
		];
	}

	public static function getRefreshPeriod()
	{
		return static::getPeriod('refresh', 1800);
	}

	public static function refresh($setupId, $path, $force = false)
	{
		$action = static::ACTION_REFRESH;
		$date = static::committedDate($setupId, $path, $action) ?: static::startDate($setupId, $path, $action);
		$parameters = [
			'action' => static::ACTION_REFRESH,
			'timestamp' => static::formatDate($date),
		];

		if ($force)
		{
			$parameters['force'] = $force;
		}

		static::resetRestartPeriod('refresh');
		static::register([
			'method' => 'process',
			'interval' => static::getPeriod('step', static::PERIOD_STEP_DEFAULT),
			'search' => Market\Reference\Agent\Controller::SEARCH_RULE_SOFT,
			'arguments' => [ $setupId, $path, $parameters ],
			'sort' => 200, // less priority
		]);
	}

	public static function getChangePeriod()
	{
		return static::getPeriod('restart', 300);
	}

	public static function change($setupId, $path)
	{
		$action = static::ACTION_CHANGE;
		$date = static::committedDate($setupId, $path, $action) ?: static::startDate($setupId, $path, $action);
		$limit = static::limitDate();
		$agentParameters = [
			'method' => 'process',
			'interval' => static::getPeriod('step', static::PERIOD_STEP_DEFAULT),
			'search' => Market\Reference\Agent\Controller::SEARCH_RULE_SOFT,
		];

		if (Market\Data\DateTime::compare($date, $limit) === -1)
		{
			$parameters = [
				'action' => static::ACTION_CHANGE,
				'timestamp' => static::formatDate($date),
			];

			static::unregister($agentParameters + [
				'arguments' => [ $setupId, $path, $parameters ],
			]);

			$date = static::expireDate();
			static::commitDate($setupId, $path, $action, $date);
		}

		$parameters = [
			'action' => static::ACTION_CHANGE,
			'timestamp' => static::formatDate($date),
		];

		static::resetRestartPeriod('restart');
		static::register($agentParameters + [
			'arguments' => [ $setupId, $path, $parameters ],
		]);
	}

	protected static function resetRestartPeriod($type)
	{
		global $pPERIOD;

		$periodOption = static::getPeriod($type, null);

		if ($periodOption !== null)
		{
			$pPERIOD = $periodOption;
		}
	}

	public static function process($setupId, $path, $parameters = null, $offset = null, $errorCount = 0)
	{
		if ($parameters !== null && !is_array($parameters)) // compatible change pass timestamp without parameters wrapper
		{
			$parameters = [
				'action' => static::ACTION_CHANGE,
				'timestamp' => $parameters,
			];
		}

		return static::wrapAction(
			[static::class, 'processBody'],
			[ $setupId, $path, $parameters, $offset ],
			$errorCount
		);
	}

	protected static function processBody($setupId, $path, $parameters, $offset)
	{
		global $pPERIOD;

		$action = isset($parameters['action']) ? $parameters['action'] : null;

		try
		{
			$setup = static::getSetup($setupId);

			Market\Utils\ServerStamp\Facade::check();

			do
			{
				$actionParameters = static::prepareActionParameters($parameters);
				$runner = new Market\Trading\Procedure\Runner(
					Market\Trading\Entity\Registry::ENTITY_TYPE_NONE,
					null
				);

				$response = $runner->run($setup, $path, $actionParameters + [
					'limit' => static::getPageSize(),
					'offset' => $offset,
				]);

				if ($response->getField('hasNext') !== true) { break; }

				$offset = $response->getField('offset');
				$needBreak = ($response->getField('needBreak') === true);

				Market\Reference\Assert::notNull($offset, 'offset');

				if ($needBreak || static::isTimeExpired())
				{
					return [$setupId, $path, $parameters, $offset];
				}
			}
			while (true);
		}
		catch (Market\Exceptions\Api\Request $exception)
		{
			if (in_array($exception->getErrorCode(), ['METHOD_FAILURE', 'LIMIT_EXCEEDED'], true))
			{
				$pPERIOD = static::getPeriod('pause', 60);

				return [$setupId, $path, $parameters, $offset];
			}

			throw $exception;
		}

		static::commitDate($setupId, $path, $action);

		return false;
	}

	protected static function prepareActionParameters($parameters)
	{
		if (!is_array($parameters)) { return []; }

		$dateFields = [
			'timestamp',
		];

		foreach ($dateFields as $dateField)
		{
			if (!isset($parameters[$dateField])) { continue; }

			$parameters[$dateField] = static::parseDate($parameters[$dateField]);
		}

		return $parameters;
	}

	protected static function canRepeat($exception, $errorCount)
	{
		if (static::isMethodNotAllowed($exception) || static::isRequestInvalid($exception))
		{
			return $errorCount < 1; // only first error skipped
		}

		return parent::canRepeat($errorCount, $errorCount);
	}

	protected static function logError(Market\Trading\Setup\Model $setup, $message, $arguments = null)
	{
		parent::logError($setup, $message, $arguments);

		if ($message instanceof Market\Utils\ServerStamp\ChangedException)
		{
			static::switchOff();
			static::notifyDisabled($setup, $message);
		}
		else if (static::isMethodNotAllowed($message))
		{
			$switchOffArguments = ($arguments !== null ? array_slice($arguments, 0, 2) : [ $setup->getId() ]);
			$method = isset($arguments[1]) ? $arguments[1] : null;

			static::switchOff($switchOffArguments);
			static::notifySwitchOffMethod($setup, $method, 'NOT_ALLOWED');
		}
		else if (static::isRequestInvalid($message))
		{
			$switchOffArguments = ($arguments !== null ? array_slice($arguments, 0, 2) : [ $setup->getId() ]);
			$method = isset($arguments[1]) ? $arguments[1] : null;

			static::switchOff($switchOffArguments);
			static::notifySwitchOffMethod($setup, $method, 'INVALID');
		}
	}

	protected static function isMethodNotAllowed($exception)
	{
		return (
			$exception instanceof Market\Exceptions\Api\Request
			&& $exception->getErrorCode() === 'METHOD_NOT_ALLOWED'
		);
	}

	protected static function isRequestInvalid($exception)
	{
		return (
			$exception instanceof Market\Exceptions\Api\Request
			&& Market\Data\TextString::getPosition((string)$exception->getErrorCode(), 'INVALID') !== false
		);
	}

	protected static function switchOff(array $arguments = null)
	{
		$methods = [
			'refresh',
			'change',
		];

		foreach ($methods as $method)
		{
			static::unregister([
				'method' => $method,
				'arguments' => $arguments,
				'search' => Market\Reference\Agent\Controller::SEARCH_RULE_SOFT,
			]);
		}
	}

	protected static function notifyDisabled(Market\Trading\Setup\Model $setup, Market\Utils\ServerStamp\ChangedException $exception)
	{
		$uiCode =  Market\Ui\Service\Facade::codeByTradingService($setup->getServiceCode());
		$resetUrl = Market\Ui\Admin\Path::getModuleUrl('trading_list', [
			'lang' => LANGUAGE_ID,
			'service' => $uiCode,
			'postAction' => 'reinstall',
		]);
		$logUrl = Market\Ui\Admin\Path::getModuleUrl('trading_log', [
			'lang' => LANGUAGE_ID,
			'service' => $uiCode,
			'find_level' => Market\Logger\Level::ERROR,
			'set_filter' => 'Y',
			'apply_filter' => 'Y',
		]);

		\CAdminNotify::Add([
			'NOTIFY_TYPE' => \CAdminNotify::TYPE_ERROR,
			'MODULE_ID' => Market\Config::getModuleName(),
			'TAG' => static::NOTIFY_DISABLED,
			'MESSAGE' => self::getMessage(
				'DISABLED',
				[
					'#MESSAGE#' => $exception->getMessage(),
					'#RESET_URL#' => $resetUrl,
					'#LOG_URL#' => $logUrl,
				],
				$exception->getMessage()
			),
		]);
	}

	/** @deprecated */
	protected static function notifyNotAllowed(Market\Trading\Setup\Model $setup, $method)
	{
		static::notifySwitchOffMethod($setup, $method, 'NOT_ALLOWED');
	}

	protected static function notifySwitchOffMethod(Market\Trading\Setup\Model $setup, $method, $reason)
	{
		$uiCode =  Market\Ui\Service\Facade::codeByTradingService($setup->getServiceCode());
		$messageSuffix = Market\Data\TextString::toUpper(str_replace('/', '_', $method));
		$tag = static::NOTIFY_NOT_ALLOWED . '_' . $setup->getId() . '_' . $messageSuffix;
		$setupUrl = Market\Ui\Admin\Path::getModuleUrl('trading_edit', [
			'lang' => LANGUAGE_ID,
			'service' => $uiCode,
			'id' => $setup->getId(),
			'YANDEX_MARKET_ADMIN_TRADING_EDIT_active_tab' => 'tab1',
		]);
		$logUrl = Market\Ui\Admin\Path::getModuleUrl('trading_log', [
			'lang' => LANGUAGE_ID,
			'service' => $uiCode,
			'find_level' => Market\Logger\Level::ERROR,
			'find_setup' => $setup->getId(),
			'set_filter' => 'Y',
			'apply_filter' => 'Y',
		]);

		\CAdminNotify::Add([
			'NOTIFY_TYPE' => \CAdminNotify::TYPE_ERROR,
			'MODULE_ID' => Market\Config::getModuleName(),
			'TAG' => $tag,
			'MESSAGE' => self::getMessage($reason . '_' . $messageSuffix, [
				'#SETUP_URL#' => $setupUrl,
				'#LOG_URL#' => $logUrl,
			]),
		]);
	}

	protected static function getPageSize()
	{
		$name = static::optionName('page_size');
		$option = (int)Market\Config::getOption($name, 500);

		return max(1, min(2000, $option));
	}

	protected static function committedDate($setupId, $path, $action = self::ACTION_CHANGE)
	{
		$name = static::getStateDateName($setupId, $path);
		$name .= ($action === static::ACTION_CHANGE ? '' : '_' . $action);
		$stored = (string)Market\State::get($name);

		return ($stored !== '' ? static::parseDate($stored) : null);
	}

	protected static function startDate($setupId, $path, $action = self::ACTION_CHANGE)
	{
		$result = new Main\Type\DateTime();
		$result->add('-PT1H');

		return static::commitDate($setupId, $path, $action, $result);
	}

	protected static function limitDate()
	{
		$result = static::expireDate();
		$result->add('-PT3H'); // 3 hours for process

		return $result;
	}

	protected static function expireDate()
	{
		$result = new Main\Type\DateTime();
		$result->add('-P1D');

		return $result;
	}

	protected static function commitDate($setupId, $path, $action = self::ACTION_CHANGE, Main\Type\DateTime $date = null)
	{
		$result = $date !== null ? $date : new Main\Type\DateTime();
		$name = static::getStateDateName($setupId, $path);
		$name .= ($action === static::ACTION_CHANGE ? '': '_' . $action);

		Market\State::set($name, static::formatDate($result));

		return $result;
	}

	protected static function parseDate($dateString)
	{
		return new Main\Type\DateTime($dateString, \DateTime::ATOM);
	}

	protected static function formatDate(Main\Type\DateTime $date)
	{
		return $date->format(\DateTime::ATOM);
	}

	protected static function getStateDateName($setupId, $path)
	{
		return implode('_', [
			static::getOptionPrefix(),
			$setupId,
			str_replace('/', '_', $path)
		]);
	}

	protected static function getOptionPrefix()
	{
		return 'trading_push';
	}
}