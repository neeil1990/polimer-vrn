<?php

namespace Yandex\Market\Trading\Entity\Sale\Internals;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

class BasketDataPreserver
{
	protected $handlerKey;
	protected $preserveData = [];

	public function onBeforeBasketItemSetFields(Main\Event $event)
	{
		$basketItem = $event->getParameter('ENTITY');

		if ($basketItem instanceof Sale\BasketItem)
		{
			$basketCode = $basketItem->getBasketCode();

			if (isset($this->preserveData[$basketCode]))
			{
				$newValues = $event->getParameter('VALUES');
				$oldValues = $event->getParameter('OLD_VALUES');
				$preserveValues = $this->preserveData[$basketCode];
				$storeValues = [];

				foreach ($preserveValues as $fieldCode => $preserveValue)
				{
					if (
						array_key_exists($fieldCode, $newValues)
						&& $preserveValue == $oldValues[$fieldCode]
						&& $preserveValue != $newValues[$fieldCode]
					)
					{
						$storeValues[$fieldCode] = $preserveValue;
					}
				}

				if (!empty($storeValues))
				{
					return new Main\EventResult(Main\EventResult::SUCCESS, [
						'VALUES' => $storeValues + $newValues
					]);
				}
			}
		}
	}

	public function preserve($basketCode, $data)
	{
		$this->preserveData[$basketCode] = $data;
	}

	public function install()
	{
		if ($this->handlerKey !== null || empty($this->preserveData)) { return; }

		$eventManager = Main\EventManager::getInstance();

		$this->handlerKey = $eventManager->addEventHandler(
			'sale',
			'OnBeforeSaleBasketItemSetFields',
			[ $this, 'onBeforeBasketItemSetFields' ]
		);
	}

	public function release()
	{
		$this->preserveData = [];

		if ($this->handlerKey !== null)
		{
			$eventManager = Main\EventManager::getInstance();
			$eventManager->removeEventHandler('sale', 'OnBeforeSaleBasketItemSetFields', $this->handlerKey);

			$this->handlerKey = null;
		}
	}
}