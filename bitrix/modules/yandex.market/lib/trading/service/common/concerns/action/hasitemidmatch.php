<?php

namespace Yandex\Market\Trading\Service\Common\Concerns\Action;

use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * trait HasItemIdMatch
 * @property TradingService\Common\Provider $provider
 * @property TradingEntity\Reference\Environment $environment
 * @property TradingService\Common\Action\SendRequest $request
 * @method TradingEntity\Reference\Order getOrder()
 * @method Market\Api\Model\Order getExternalOrder()
 */
trait HasItemIdMatch
{
	protected function getExternalItemsBasketMap(
		Market\Api\Model\Order\ItemCollection $itemCollection,
		Market\Api\Model\Order $externalOrder = null
	)
	{
		$result = [];

		/** @var Market\Api\Model\Order\Item $item */
		foreach ($itemCollection as $item)
		{
			$basketCode = $this->getExternalItemBasketCode($item, $externalOrder);

			if ($basketCode === null) { continue; }

			$id = $item->getId();

			if ($id !== null)
			{
				$result[$id] = $basketCode;
			}
			else
			{
				$result[] = $basketCode;
			}
		}

		return $result;
	}

	protected function getExternalItemBasketCode(
		Market\Api\Model\Order\Item $item,
		Market\Api\Model\Order $externalOrder = null
	)
	{
		return $this->getItemBasketCode([
			'id' => $item->getId(),
			'xmlId' => $this->provider->getDictionary()->getOrderItemXmlId($item),
			'offerId' => $item->getOfferId(),
		], $externalOrder);
	}

	protected function getItemsBasketCodes(array $items, Market\Api\Model\Order $externalOrder = null)
	{
		$result = [];

		foreach ($items as $item)
		{
			$basketCode = $this->getItemBasketCode($item, $externalOrder);

			if ($basketCode === null) { continue; }

			if (isset($item['id']))
			{
				$result[$item['id']] = $basketCode;
			}
			else
			{
				$result[] = $basketCode;
			}
		}

		return $result;
	}

	protected function getItemBasketCode(array $item, Market\Api\Model\Order $externalOrder = null)
	{
		$methods = [
			'xmlId',
			'id',
			'productId',
			'offerId',
		];

		$offerMapFetched = false;
		$order = $this->getOrder();
		$result = null;

		foreach ($methods as $method)
		{
			if (!isset($item[$method])) { continue; }

			$value = $item[$method];

			if ($method === 'xmlId')
			{
				$result = $order->getBasketItemCode($value, 'XML_ID');
			}
			else if ($method === 'id')
			{
				$itemModel = new Market\Api\Model\Order\Item([ 'id' => $value ]);
				$xmlId = $this->provider->getDictionary()->getOrderItemXmlId($itemModel);

				$result = $order->getBasketItemCode($xmlId, 'XML_ID');
			}
			else if ($method === 'productId')
			{
				$result = $order->getBasketItemCode($value);
			}
			else if ($method === 'offerId')
			{
				if (!$offerMapFetched)
				{
					if ($externalOrder === null) { $externalOrder = $this->getExternalOrder(); }

					$offerMapFetched = true;
					$offerMap = $this->getOfferMap($externalOrder->getItems());

					if ($offerMap !== null)
					{
						if (!isset($offerMap[$value])) { continue; }

						$value = $offerMap[$value];
					}
				}

				$result = $order->getBasketItemCode($value);
			}

			if ($result !== null) { break; }
		}

		return $result;
	}

	protected function getItemId(array $item)
	{
		$externalOrder = null;
		$offerMap = null;
		$result = null;
		$methods = [
			'id',
			'xmlId',
			'productId',
		];

		foreach ($methods as $method)
		{
			if (!isset($item[$method])) { continue; }

			$value = $item[$method];

			if ($method === 'id')
			{
				$result = $value;
			}
			else if ($method === 'xmlId')
			{
				$matches = $this->provider->getDictionary()->parseOrderItemXmlId($value);
				$result = isset($matches['ID']) ? $matches['ID'] : null;
			}
			else if ($method === 'productId')
			{
				if ($externalOrder === null)
				{
					$externalOrder = $this->getExternalOrder();
					$offerMap = $this->getOfferMap($externalOrder->getItems());
				}

				/** @var Market\Api\Model\Order\Item $externalItem */
				foreach ($externalOrder->getItems() as $externalItem)
				{
					if ((string)$externalItem->mapProductId($offerMap) === (string)$value)
					{
						$result = $externalItem->getId();
						break;
					}
				}
			}

			if ($result !== null) { break; }
		}

		return $result;
	}

	protected function getOfferMap(Market\Api\Model\Order\ItemCollection $items)
	{
		$offerIds = $items->getOfferIds();
		$command = new TradingService\Common\Command\OfferMap(
			$this->provider,
			$this->environment
		);

		return $command->make($offerIds);
	}
}
