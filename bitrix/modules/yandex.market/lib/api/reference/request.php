<?php

namespace Yandex\Market\Api\Reference;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

abstract class Request
	implements Market\Psr\Log\LoggerAwareInterface
{
	const DATA_TYPE_JSON = 'json';
	const DATA_TYPE_HTTP = 'http';

	/** @var Market\Psr\Log\LoggerInterface */
	protected $logger;

	public function getUrl()
	{
		return $this->getProtocol() . '://' . $this->getHost() . $this->getPath();
	}

	public function getFullUrl()
	{
		$url = $this->getUrl();

		if ($this->getMethod() === Main\Web\HttpClient::HTTP_GET)
		{
			$query = $this->getQuery();
			$url = $this->appendUrlQuery($url, $query);
		}

		return $url;
	}

	protected function appendUrlQuery($url, $query)
	{
		$result = $url;

		if (!empty($query))
		{
			$result .=
				(Market\Data\TextString::getPosition($result, '?') === false ? '?' : '&')
				. http_build_query($query, '', '&');
		}

		return $result;
	}

	public function getProtocol()
	{
		return 'https';
	}

	abstract public function getHost();

	abstract public function getPath();

	public function getQuery()
	{
		return [];
	}

	public function getQueryFormat()
	{
		return static::DATA_TYPE_HTTP;
	}

	public function getMethod()
	{
		return Main\Web\HttpClient::HTTP_GET;
	}

	public function setLogger(Market\Psr\Log\LoggerInterface $logger = null)
	{
		$this->logger = $logger;
	}

	protected function log($level, $message, $context = null)
	{
		if ($this->logger !== null)
		{
			$this->logger->log($level, $message, $context);
		}
	}

	/**
	 * Выполнить запрос
	 *
	 * @return Market\Api\Reference\RequestResult
	 */
	public function send()
	{
		$result = new Market\Api\Reference\RequestResult();
		$client = $this->buildClient();
		$httpResponse = '';
		$httpContentType = null;

		if ($this->queryClientWithLock($client))
		{
			$httpResponse = $client->getResult();
			$httpContentType = $client->getContentType();
		}

		$errors = $client->getError();

		if ($httpResponse === '' && !empty($errors))
		{
			$this->registerHttpErrors($result, $errors);
		}
		else
		{
			$data = $this->parseHttpResponse($httpResponse, $httpContentType);
			$httpStatus = (int)$client->getStatus();

			if ($httpStatus !== 200 && empty($data))
			{
				$logContents = $httpResponse ?: sprintf('http %s', $httpStatus);

				$this->registerHttpStatusError($result, $httpStatus, $data);

				$this->log(Market\Psr\Log\LogLevel::DEBUG, $logContents, [
					'AUDIT' => Market\Logger\Trading\Audit::OUTGOING_RESPONSE,
					'URL' => $this->getUrl(),
				]);
			}
			else
			{
				$response = $this->buildResponse($data);
				$responseValidation = $response->validate();

				if (!$responseValidation->isSuccess())
				{
					$result->addErrors($responseValidation->getErrors());
				}

				$this->log(Market\Psr\Log\LogLevel::DEBUG, $response->getRaw(), [
					'AUDIT' => Market\Logger\Trading\Audit::OUTGOING_RESPONSE,
					'URL' => $this->getUrl(),
				]);

				$result->setResponse($response);
			}
		}

		return $result;
	}

	/**
	 * @param mixed $data
	 * @return Response
	 */
	abstract public function buildResponse($data);

	protected function buildClient()
	{
		$result = new Internals\HttpClient([
			'version' => '1.1',
			'socketTimeout' => 30,
			'streamTimeout' => 30,
			'redirect' => true,
			'redirectMax' => 5,
		]);

		list($markerName, $markerValue) = Market\Api\Marker::getHeader();

		$result->setHeader($markerName, $markerValue);

		switch ($this->getQueryFormat())
		{
			case static::DATA_TYPE_JSON:
				$result->setHeader('Content-Type', 'application/json');
			break;
		}

		return $result;
	}

	protected function queryClientWithLock(Main\Web\HttpClient $client)
	{
		$locker = $this->createLocker();

		try
		{
			$locker->lock();
			$result = $this->queryClient($client);
			$locker->release();
		}
		catch (\Exception $exception)
		{
			$locker->release();
			throw $exception;
		}
		catch (\Throwable $exception)
		{
			$locker->release();
			throw $exception;
		}

		return $result;
	}

	protected function createLocker()
	{
		$host = \CMain::GetServerUniqID() . '_' . $this->getHost();

		return new Market\Api\Locker($host, 0);
	}

	protected function queryClient(Main\Web\HttpClient $client)
	{
		$method = $this->getMethod();
		$url = $this->getUrl();
		$queryData = $this->getQuery();
		$postData = null;
		$result = null;

		if ($method === Main\Web\HttpClient::HTTP_GET)
		{
			$fullUrl = $this->appendUrlQuery($url, $queryData);
		}
		else
		{
			$fullUrl = $url;
			$postData = $this->formatQueryData($queryData);
		}

		$this->log(Market\Psr\Log\LogLevel::DEBUG, $queryData, [
			'AUDIT' => Market\Logger\Trading\Audit::OUTGOING_REQUEST,
			'URL' => $url,
		]);

		if ($client->query($method, $fullUrl, $postData))
		{
			$result = $client->getResult();
		}

		return $result;
	}

	protected function formatQueryData($data)
	{
		switch ($this->getQueryFormat())
		{
			case static::DATA_TYPE_JSON:
				$result = Main\Web\Json::encode($data);
			break;

			default:
				$result = $data;
			break;
		}

		return $result;
	}

	protected function registerHttpErrors(Market\Api\Reference\RequestResult $result, $errors)
	{
		foreach ($errors as $code => $message)
		{
			$result->addError(new Market\Error\Base($message, $code));
		}
	}

	protected function registerHttpStatusError(Market\Api\Reference\RequestResult $result, $httpStatus, $responseData)
	{
		$error = $this->createHttpStatusError($httpStatus, $responseData);

		$result->addError($error);
	}

	protected function createHttpStatusError($httpStatus, $responseData)
	{
		$message = (string)Market\Config::getLang('API_REQUEST_HTTP_STATUS_' . $httpStatus, null, '');
		$code = 'STATUS_' . $httpStatus;

		if ($message === '')
		{
			$message = (string)Market\Config::getLang('API_REQUEST_HTTP_STATUS', [
				'#STATUS#' => $httpStatus
			]);
		}

		return new Market\Error\Base($message, $code);
	}

	protected function parseHttpResponse($httpResponse, $contentType = 'application/json')
	{
		try
		{
			if ($httpResponse === '') { return []; }

			$result = Main\Web\Json::decode($httpResponse);
		}
		catch (\Exception $exception)
		{
			$result = [];
		}

		return $result;
	}
}
