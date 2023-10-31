<?php

namespace Yandex\Market\Components;

use Yandex\Market;
use Bitrix\Main;

class Purchase extends \CBitrixComponent
{
	const HTTP_STATUS_200 = '200 OK';
	const HTTP_STATUS_400 = '400 Bad Request';
	const HTTP_STATUS_403 = '403 Forbidden';
	const HTTP_STATUS_404 = '404 Not Found';
	const HTTP_STATUS_500 = '500 Internal Server Error';

	public function onPrepareComponentParams($params)
	{
		$params['SEF_FOLDER'] = trim($params['SEF_FOLDER']);
		$params['SERVICE_CODE'] = trim($params['SERVICE_CODE']);
		$params['SITE_ID'] = trim($params['SITE_ID']);
		$params['URL_ID'] = trim($params['URL_ID']);

		return $params;
	}

	public function executeComponent()
	{
		$logger = null;
		$routePath = null;

		try
		{
			$this->loadModules();
			$this->parseRequest();

			$routePath = $this->getRequestPath();
			$setup = $this->getSetup();
			$service = $setup->wakeupService();
			$logger = $service->getLogger();
			$environment = $setup->getEnvironment();
			$router = $service->getRouter();
			$action = $router->getHttpAction($routePath, $environment, $this->request, $this->getServer());

			$this->configureLogger($logger, [
				'URL' => $routePath,
				'AUDIT' => $action->getAudit(),
			]);

			$logger->debug($action->getRequest()->getRaw(), [
				'AUDIT' => Market\Logger\Trading\Audit::INCOMING_REQUEST,
			]);

			$action->checkAuthorization();
			$action->process();

			$status = static::HTTP_STATUS_200;
			$response = $action->getResponse()->getRaw();

			$logger->debug($response, [
				'AUDIT' => Market\Logger\Trading\Audit::INCOMING_RESPONSE,
			]);

			$this->releaseLogger($logger);
		}
		catch (\Exception $exception)
		{
			list($status, $response) = $this->processException($exception, $logger, $routePath);
		}
		catch (\Throwable $exception)
		{
			list($status, $response) = $this->processException($exception, $logger, $routePath);
		}

		$this->sendResponse($response, $status);
	}

	protected function loadModules()
	{
		$requiredModules = $this->getRequiredModules();

		foreach ($requiredModules as $requiredModule)
		{
			if (!Main\Loader::includeModule($requiredModule))
			{
				$message = $this->getLang('MODULE_NOT_INSTALLED', [ '#MODULE_ID#' => $requiredModule ]);

				throw new Main\SystemException($message);
			}
		}
	}

	protected function getRequiredModules()
	{
		return [
			'yandex.market',
		];
	}

	protected function parseRequest()
	{
		$this->request->addFilter(new Market\Api\Incoming\JsonBodyFilter());
	}

	protected function getServer()
	{
		return Main\Context::getCurrent()->getServer();
	}

	protected function processException($exception, Market\Psr\Log\LoggerInterface $logger = null, $routePath = null)
	{
		$status = $this->getExceptionStatus($exception);
		$response = $this->getExceptionResponse($exception);

		if ($this->needLogException($exception, $logger))
		{
			$this->logException($exception, $logger, $routePath);
		}

		return [$status, $response];
	}

	protected function getExceptionStatus($exception)
	{
		if ($exception instanceof Market\Exceptions\Trading\AccessDenied)
		{
			$result = static::HTTP_STATUS_403;
		}
		else if ($exception instanceof Market\Exceptions\Api\InvalidOperation)
		{
			$result = static::HTTP_STATUS_400;
		}
		else if (
			$exception instanceof Market\Exceptions\Trading\NotImplementedAction
			|| $exception instanceof Market\Exceptions\Trading\SetupNotFound
			|| $exception instanceof Market\Exceptions\Trading\SetupInactive
			|| $exception instanceof Market\Exceptions\Component\ParameterNull
		)
		{
			$result = static::HTTP_STATUS_404;
		}
		else if ($exception instanceof Market\Exceptions\Trading\NotRecoverable)
		{
			$result = static::HTTP_STATUS_200;
		}
		else
		{
			$result = static::HTTP_STATUS_500;
		}

		return $result;
	}

	protected function needLogException($exception, Market\Psr\Log\LoggerInterface $logger = null)
	{
		if (!($logger instanceof Market\Logger\Trading\Logger)) { return true; }

		$parentId = $logger->getEntityParent();

		if (empty($parentId)) { return true; }

		if ($exception instanceof Market\Exceptions\Trading\PingDenied)
		{
			$result = $this->tickPingException($parentId);
		}
		else
		{
			$result = true;
		}

		return $result;
	}

	protected function tickPingException($setupId)
	{
		$baseName = 'trading_ping_' . $setupId;
		$dateName = $baseName . '_at';
		$counterName = $baseName . '_count';
		$dateFormatted = (string)Market\State::get($dateName);
		$now = new \DateTime();
		$result = false;

		if ($dateFormatted !== '')
		{
			$date = \DateTime::createFromFormat(\DateTime::ATOM, $dateFormatted);
			$interval = $now->diff($date);

			$expired = (
				(int)$interval->format('%a') > 0
				|| (int)$interval->format('%h') > 1
			);
		}
		else
		{
			$expired = true;
		}

		if ($expired)
		{
			Market\State::set($dateName, $now->format(\DateTime::ATOM));
			Market\State::set($counterName, 1);
		}
		else
		{
			$counter = (int)Market\State::get($counterName);
			$limit = 2;

			if ($counter < $limit)
			{
				Market\State::set($counterName, $counter + 1);
			}
			else
			{
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * @param \Throwable|\Exception $exception
	 * @param Market\Psr\Log\LoggerInterface|null $logger
	 * @param string|null $routePath
	 */
	protected function logException($exception, Market\Psr\Log\LoggerInterface $logger = null, $routePath = null)
	{
		if ($logger === null) { return; }

		$level = Market\Logger\Level::ERROR;
		$context = [
			'AUDIT' => Market\Logger\Trading\Audit::INCOMING_RESPONSE,
		];

		if ($logger instanceof Market\Logger\Reference\Logger)
		{
			$context = array_diff_key($context, $logger->getFullContext());
		}

		if ($exception instanceof Market\Exceptions\Trading\NotRecoverable)
		{
			$level = $exception->getLogLevel();
		}
		else if (
			$exception instanceof Market\Exceptions\Trading\NotImplementedAction
			|| $exception instanceof Market\Exceptions\Component\ParameterNull
		)
		{
			$level = Market\Logger\Level::DEBUG;
		}

		$logger->log($level, $exception, $context);
	}

	/**
	 * @param \Throwable|\Exception $exception
	 *
	 * @return array
	 */
	protected function getExceptionResponse($exception)
	{
		return [
			'error' => $exception->getMessage()
		];
	}

	protected function sendResponse($response, $status)
	{
		list($markerName, $markerValue) = Market\Api\Marker::getHeader();
		$options = [
			'headers' => [
				$markerName => $markerValue,
			],
		];

		\CHTTP::SetStatus($status);

		if (is_array($response))
		{
			Market\Utils\HttpResponse::sendJson($response, $options);
		}
		else
		{
			Market\Utils\HttpResponse::sendRaw((string)$response, $options);
		}
	}

	protected function getRequestPath()
	{
		$path = $this->request->getRequestedPage();
		$path = $this->normalizeDirectory($path);
		$folder = $this->getParameterSefFolder();
		$folder = $this->normalizeDirectory($folder);

		if (Market\Data\TextString::getPosition($path, $folder) !== 0)
		{
			throw new Main\SystemException($this->getLang('REQUEST_URL_OUTSIDE_SEF_FOLDER'));
		}

		return $path === $folder
			? 'root'
			: Market\Data\TextString::getSubstring(
				$path,
				Market\Data\TextString::getLength($folder) + 1
			); // remove folder and first slash
	}

	protected function normalizeDirectory($path)
	{
		$result = Main\IO\Path::normalize($path);
		$result = preg_replace('#/index\.php$#', '', $result);

		if ($result !== '/')
		{
			$result = rtrim($result, '/');
		}

		return $result;
	}

	protected function getSetup()
	{
		$urlId = $this->getParameterUrlId();
		$serviceCode = $this->getParameterServiceCode();
		$behaviorCode = $this->getParameterBehaviorCode();
		$result = $this->loadSetup($serviceCode, $urlId, $behaviorCode);

		if (!$result->isActive())
		{
			throw new Market\Exceptions\Trading\SetupInactive($this->getLang('TRADING_SETUP_INACTIVE'));
		}

		return $result;
	}

	protected function loadSetup($serviceCode, $urlId, $behaviorCode = null)
	{
		try
		{
			$result = Market\Trading\Setup\Model::loadByServiceAndUrlId($serviceCode, $urlId, $behaviorCode);
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			if (!Market\Trading\Service\Migration::isDeprecated($serviceCode)) { throw $exception; }

			$useCode = Market\Trading\Service\Migration::getDeprecateUse($serviceCode);
			$result = Market\Trading\Setup\Model::loadByServiceAndUrlId($useCode, $urlId, $behaviorCode);
		}

		return $result;
	}

	protected function getParameterSefFolder()
	{
		return $this->getRequiredParameter('SEF_FOLDER');
	}

	protected function getParameterServiceCode()
	{
		return $this->getRequiredParameter('SERVICE_CODE');
	}

	protected function getParameterBehaviorCode()
	{
		return $this->getParameter('BEHAVIOR_CODE') ?: null;
	}

	protected function getParameterUrlId()
	{
		$result = $this->getParameter('URL_ID');

		if ($result === '')
		{
			$result = $this->getParameter('SITE_ID');
		}

		if ($result === '')
		{
			$message = $this->getLang('PARAMETER_URL_ID_REQUIRED');
			throw new Market\Exceptions\Component\ParameterNull($message);
		}

		return $result;
	}

	/**
	 * @deprecated
	 * @return string
	 */
	protected function getParameterSiteId()
	{
		return $this->getRequiredParameter('SITE_ID');
	}

	protected function getRequiredParameter($key)
	{
		$result = $this->getParameter($key);

		if ($result === '')
		{
			$message = $this->getLang('PARAMETER_' . $key . '_REQUIRED');
			throw new Market\Exceptions\Component\ParameterNull($message);
		}

		return $result;
	}

	protected function getParameter($key)
	{
		return isset($this->arParams[$key]) ? (string)$this->arParams[$key] : '';
	}

	protected function getLang($code, $replace = null, $language = null)
	{
		return Main\Localization\Loc::getMessage('YANDEX_MARKET_PURCHASE_' . $code, $replace, $language);
	}

	protected function configureLogger($logger, array $context)
	{
		if ($logger instanceof Market\Logger\Reference\Logger)
		{
			if ($context['AUDIT'] === Market\Logger\Trading\Audit::INTERNAL)
			{
				$logger->setLevel(Market\Logger\Level::ERROR);
			}

			$logger->resetContext($context);
		}
	}

	protected function releaseLogger($logger)
	{
		if ($logger instanceof Market\Logger\Reference\Logger)
		{
			$logger->releaseContext();
		}
	}
}