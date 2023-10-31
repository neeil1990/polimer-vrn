<?php

namespace Yandex\Market\Ui\Trading;

use Bitrix\Main;
use Yandex\Market;

class HelloTest
{
	use Market\Reference\Concerns\HasLang;

	protected $parameters;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(array $parameters)
	{
		$this->parameters = $parameters;
	}

	public function run()
	{
		$url = $this->getHelloUrl();
		$data = $this->getHelloData();

		$this->installDebug();
		$result = $this->queryHello($url, $data);
		$this->uninstallDebug();

		return $result;
	}

	public function show(Market\Result\Base $result)
	{
		if ($result->isSuccess())
		{
			$this->showSuccess();
		}
		else
		{
			foreach ($result->getErrors() as $error)
			{
				$this->showError($error);
			}
		}
	}

	protected function showSuccess()
	{
		\CAdminMessage::ShowMessage([
			'TYPE' => 'OK',
			'MESSAGE' => static::getLang('UI_TRADING_HELLO_TEST_SUCCESS'),
		]);
	}

	public function getErrorDescription(Market\Error\Base $error)
	{
		$code = $error->getCode();
		$data = $error->getCustomData();
		$replaces = $this->makeErrorReplaces($data);

		return (string)static::getLang('UI_TRADING_HELLO_TEST_ERROR_' . $code . '_DESCRIPTION', $replaces, '');
	}

	protected function showError(Market\Error\Base $error)
	{
		$description = $this->getErrorDescription($error);

		if ($description !== '')
		{
			echo $description;
		}
		else
		{
			\CAdminMessage::ShowMessage([
				'TYPE' => 'ERROR',
				'MESSAGE' => $error->getMessage(),
			]);
		}
	}

	protected function makeErrorReplaces($data)
	{
		$result = [];

		if (is_array($data))
		{
			foreach ($data as $key => $value)
			{
				if (!is_scalar($value)) { continue; }

				$langKey = '#' . Market\Data\TextString::toUpper($key) . '#';
				$result[$langKey] = $value;
			}
		}

		return $result;
	}

	protected function installDebug()
	{
		HelloDebug\Redirect::install();
	}

	protected function uninstallDebug()
	{
		HelloDebug\Redirect::uninstall();
	}

	protected function queryHello($url, $data)
	{
		$result = new Market\Result\Base();
		$client = new Main\Web\HttpClient([
			'redirect' => false,
		]);
		$client->setHeader('Authorization', randString(10));

		$client->query(Main\Web\HttpClient::HTTP_POST, $url, $data);

		$status = (int)$client->getStatus();
		$responseRaw = $client->getResult();
		$response = $this->parseResponse($responseRaw);

		if ($status !== 200 || !$this->isValidResponse($response))
		{
			$error = $this->makeResponseError($response, $status, $client);
			$result->addError($error);
		}

		return $result;
	}

	protected function getHelloUrl()
	{
		$url = $this->getRequiredParameter('url');

		return $url . '/hello';
	}

	protected function getHelloData()
	{
		return Main\Web\Json::encode([
			'hello' => true,
		]);
	}

	protected function parseResponse($raw)
	{
		try
		{
			$result = Main\Web\Json::decode($raw);
		}
		catch (Main\SystemException $exception)
		{
			$result = null;
		}

		return $result;
	}

	protected function makeResponseError($response, $status, Main\Web\HttpClient $client)
	{
		$clientErrors = $client->getError();
		$responseError = isset($response['error']) ? $response['error'] : '';
		$message = '';
		$data = [];

		if ($status === 301 || $status === 302)
		{
			$code = 'HTTP_REDIRECT';
			$message = static::getLang('UI_TRADING_HELLO_TEST_ERROR_HTTP_REDIRECT');
			$data = [
				'from' => $client->getEffectiveUrl(),
				'to' => $client->getHeaders()->get('Location'),
			];
		}
		else if ($status === 404)
		{
			$code = $this->isUrlRewriteRuleMissing() ? 'URLREWRITE_RULE_MISSING' : 'HTTP_NOT_FOUND';
			$message = static::getLang('UI_TRADING_HELLO_TEST_ERROR_' . $code);
			$data = [
				'script_path' => $this->getRouteScriptPath(),
			];
		}
		else if ($responseError === HelloDebug\Response::ERROR_MARKER)
		{
			$code = $response['reason'];
			$message = static::getLang('UI_TRADING_HELLO_TEST_ERROR_' . $response['reason'], null, $response['reason']);
			$data = [];

			if (isset($response['data']))
			{
				$data = (array)$response['data'];
			}

			if (isset($response['trace']))
			{
				$data['trace'] = $response['trace'];
			}
		}
		else if ($status === 400 && Market\Data\TextString::getPosition($responseError, 'token') !== false)
		{
			$code = 'TOKEN_MISSING';
			$message = static::getLang('UI_TRADING_HELLO_TEST_ERROR_TOKEN_MISSING');
		}
		else if ($status === 400 && Market\Data\TextString::getPosition($responseError, 'missing') !== false)
		{
			$code = 'BODY_MISSING';
			$message = static::getLang('UI_TRADING_HELLO_TEST_ERROR_BODY_MISSING');
		}
		else if ($status === 500 && $responseError !== '')
		{
			$code = 'INTERNAL_ERROR';
			$message = $response['error'] ?: static::getLang('UI_TRADING_HELLO_TEST_ERROR_INTERNAL_ERROR');
			$data = [
				'response' => htmlspecialcharsbx(print_r($response, true)),
			];
		}
		else if (isset($clientErrors['SOCKET']))
		{
			$code = 'SOCKET_CONNECT';
			$message = $clientErrors['SOCKET'] ?: static::getLang('UI_TRADING_HELLO_TEST_ERROR_SOCKET_CONNECT');
		}
		else if (!empty($clientErrors))
		{
			$code = 'CLIENT_ERROR';
			$message = reset($clientErrors) ?: static::getLang('UI_TRADING_HELLO_TEST_ERROR_CLIENT_ERROR');
			$data = [
				'error' => htmlspecialcharsbx(print_r($clientErrors, true)),
			];
		}
		else
		{
			$code = 'UNKNOWN';
			$message = static::getLang('UI_TRADING_HELLO_TEST_ERROR_UNKNOWN');
			$responseRaw = is_array($response) ? print_r($response, true) : $client->getResult();
			$data = [
				'status' => $status,
				'response' => htmlspecialcharsbx($responseRaw),
			];
		}

		return new Market\Error\Base($message, $code, $data);
	}

	protected function isValidResponse($response)
	{
		return (
			is_array($response)
			&& isset($response['hello'])
			&& $response['hello'] === true
		);
	}

	protected function isUrlRewriteRuleMissing()
	{
		$siteId = $this->getParameter('site') ?: SITE_ID;
		$rules = Main\UrlRewriter::getList($siteId, [
			'PATH' => $this->getRouteScriptPath(),
		]);

		return empty($rules);
	}

	protected function getRouteScriptPath()
	{
		$environment = Market\Trading\Entity\Manager::createEnvironment();

		return $environment->getRoute()->getScriptPath();
	}

	protected function getRequiredParameter($key)
	{
		$value = $this->getParameter($key);

		if (Market\Utils\Value::isEmpty($value))
		{
			$message = static::getLang('UI_TRADING_HELLO_TEST_PARAMETER_REQUIRED', [ '#PARAMETER#' => $key ]);
			throw new Main\SystemException($message);
		}

		return $value;
	}

	protected function getParameter($key)
	{
		return isset($this->parameters[$key]) ? $this->parameters[$key] : null;
	}
}