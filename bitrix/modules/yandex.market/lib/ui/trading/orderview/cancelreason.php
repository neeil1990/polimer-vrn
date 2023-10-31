<?php

namespace Yandex\Market\Ui\Trading\OrderView;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;
use Bitrix\Main;

class CancelReason extends AbstractExtension
{
	public function isSupported()
	{
		$service = $this->setup->wakeupService();

		if (!($service instanceof TradingService\MarketplaceDbs\Provider)) { return false; }

		$options = $service->getOptions();

		if ((string)$options->getProperty('REASON_CANCELED') !== '') { return false; } // reason stored inside property

		$reasonFieldUsed = false;

		foreach ($options->getCancelStatusOptions() as $option)
		{
			if ($option->getCancelReason() === null)
			{
				$reasonFieldUsed = true;
				break;
			}
		}

		return $reasonFieldUsed;
	}

	public function initialize(array $orderInfo)
	{
		$eventManager = Main\EventManager::getInstance();
		$eventManager->addEventHandler('main', 'OnEndBufferContent', [$this, 'onEndBufferContent']);
	}

	public function onEndBufferContent(&$content)
	{
		$content = preg_replace_callback('/<textarea(?P<attributes>\s[^>]*name="FORM_REASON_CANCELED"[^>]*)>(?P<value>.*?)<\/textarea>/s', function($matches) {
			/** @var TradingService\MarketplaceDbs\CancelReason $cancelReason */
			$cancelReason = $this->setup->getService()->getCancelReason();
			$attributes = preg_replace('/min-height:\s*\d+px;/', '', $matches['attributes']);
			$currentValue = trim($matches['value']);
			$currentVariant = $cancelReason->resolveVariant($currentValue);
			$optionsHtml = '';

			foreach ($cancelReason->getVariants() as $variant)
			{
				$optionsHtml .= sprintf(
					'<option value="%s"%s>%s</option>',
					htmlspecialcharsbx($variant),
					$currentVariant === $variant ? ' selected' : '',
					htmlspecialcharsbx($cancelReason->getTitle($variant))
				);
			}

			if ($currentVariant === null && $currentValue !== '')
			{
				$optionsHtml .= sprintf(
					'<option selected>%s</option>',
					htmlspecialcharsbx($cancelReason->getTitle($currentValue))
				);
			}

			return sprintf('<select%s>%s</select>', $attributes, $optionsHtml);
		}, $content);
	}
}