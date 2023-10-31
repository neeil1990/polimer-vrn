<?php

namespace Yandex\Market\Components;

use Bitrix\Main;
use Yandex\Market;

class TradingOrderViewAjax extends Main\Engine\Controller
{
	public function saveAction($data)
	{
		try
		{
			$this->loadModule();

			$request = $this->emulateRequest([ 'YAMARKET_ORDER' => $data ]);
			$controller = new Market\Ui\Trading\ShipmentSubmit($request);

			$response = $controller->processRequest();
			$message = $this->combineUiMessage($response);

			if ($response['status'] !== 'ok')
			{
				throw new Main\SystemException($message);
			}

			$result = $this->reloadAction();
		}
		catch (Main\SystemException $exception)
		{
			$result = null;
			$this->addError(new Main\Error($exception->getMessage(), $exception->getCode()));
		}

		return $result;
	}

	public function reloadAction()
	{
		global $APPLICATION;

		$parameters = $this->getUnsignedParameters() + [
			'MODE' => 'RELOAD',
		];

		return $APPLICATION->IncludeComponent('yandex.market:trading.order.view', 'bitrix24', $parameters);
	}

	protected function loadModule()
	{
		if (!Main\Loader::includeModule('yandex.market'))
		{
			throw new Main\SystemException('Module yandex.market is required');
		}
	}

	protected function emulateRequest($data)
	{
		$context = Main\Context::getCurrent();

		return new Main\HttpRequest($context->getServer(), [], $data, [], []);
	}

	protected function combineUiMessage($response)
	{
		if (isset($response['message']))
		{
			$result = $response['message'];
		}
		else if (isset($response['messages']))
		{
			$messages = array_column($response['messages'], 'text');
			$result = implode('<br />', $messages);
		}
		else
		{
			throw new Main\ArgumentException('unknown ui response format');
		}

		return $result;
	}
}