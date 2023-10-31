<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\OrderCancellationNotify;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;

class Notification extends TradingService\Reference\Action\AbstractNotification
{
	use Market\Reference\Concerns\HasMessage;

	public function getType($type)
	{
		return 'YAMARKET_ORDER_CANCELLATION_ACCEPT_' . $type;
	}

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	public function getTemplateSubject($type)
	{
		return self::getMessage('TEMPLATE_SUBJECT');
	}

	public function getTemplateBody($type)
	{
		return self::getMessage('TEMPLATE_BODY_' . $type, [
			'#DOCUMENTS_URL#' =>
				Market\Ui\Admin\Path::getModuleUrl('trading_order_list')
				. '?lang=#LANGUAGE_ID#'
				. '&service=#TRADING_SERVICE#'
				. '&setup=#TRADING_SETUP#',
		]);
	}

	public function getVariables()
	{
		return [
			'INTERNAL_ID',
			'ORDER_ID',
			'ORDER_DATE',
			'EXTERNAL_ID',
		];
	}

	public function getVariableTitle($code)
	{
		return self::getMessage('VARIABLE_' . $code);
	}

	protected function getFields($siteId, array $parameters)
	{
		/** @var TradingEntity\Reference\Order $order */
		$order = $this->extractParameter($parameters, 'ORDER', TradingEntity\Reference\Order::class);
		$result = [
			'INTERNAL_ID' => $order->getId(),
			'ORDER_ID' => $order->getAccountNumber(),
			'ORDER_DATE' => (string)$order->getCreationDate(),
			'EXTERNAL_ID' => $this->extractParameter($parameters, 'EXTERNAL_ID'),
		];
		$result += $this->getCommonFields($siteId, $parameters);

		return $result;
	}
}