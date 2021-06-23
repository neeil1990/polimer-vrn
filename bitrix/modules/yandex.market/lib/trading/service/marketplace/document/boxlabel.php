<?php

namespace Yandex\Market\Trading\Service\Marketplace\Document;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class BoxLabel extends TradingService\Reference\Document\AbstractDocument
	implements TradingService\Reference\Document\HasLoadItems
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getTitle($version = '')
	{
		$suffix = $version !== '' ? '_' . Market\Data\TextString::toUpper($version) : '';

		return static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_BOX_LABEL' . $suffix);
	}

	public function getEntityType()
	{
		return Market\Trading\Entity\Registry::ENTITY_TYPE_BOX;
	}

	public function getSettings()
	{
		$result = [];

		if (Market\Config::isExpertMode())
		{
			$result['SIZE'] = [
				'TYPE' => 'enumeration',
				'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_BOX_LABEL_SIZE'),
				'VALUES' => [
					[
						'ID' => 'small',
						'VALUE' => static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_BOX_LABEL_SIZE_SMALL'),
					],
					[
						'ID' => 'big',
						'VALUE' => static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_BOX_LABEL_SIZE_BIG'),
					],
				],
				'PERSISTENT' => 'Y',
				'SETTINGS' => [
					'ALLOW_NO_VALUE' => 'N',
				]
			];

			$result['HTML'] = [
				'TYPE' => 'boolean',
				'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_BOX_LABEL_HTML'),
				'PERSISTENT' => 'Y',
			];
		}

		return $result;
	}

	public function loadItems($entitySelect)
	{
		$options = $this->provider->getOptions();
		$selectBoxesMap = array_flip($entitySelect['BOX']);
		$result = [];

		foreach ($entitySelect['ORDER'] as $orderId)
		{
			$boxLabels = TradingService\Marketplace\Model\Box\LabelDataCollection::fetch($options, $orderId);

			/** @var TradingService\Marketplace\Model\Box\LabelData $boxLabel */
			foreach ($boxLabels as $boxLabel)
			{
				$boxId = $boxLabel->getBoxId();

				if ($boxId === null || isset($selectBoxesMap[$boxId]))
				{
					$result[] = $this->makeItem($boxLabel);
				}
			}
		}

		return $result;
	}

	protected function makeItem(TradingService\Marketplace\Model\Box\LabelData $boxLabel)
	{
		return [
			'BOX_ID' => $boxLabel->getBoxId(),
			'NUMBER' => $boxLabel->getNumber(),
			'ORDER_ID' => $boxLabel->getOrderId(),
			'ORDER_NUM' => $boxLabel->getOrderNum(),
			'FULFILMENT_ID' => $boxLabel->getFulfilmentId(),
			'PLACE' => $boxLabel->getPlace(),
			'WEIGHT' => $boxLabel->getWeight(),
			'SUPPLIER_NAME' => $boxLabel->getSupplierName(),
			'DELIVERY_SERVICE_ID' => $boxLabel->getDeliveryServiceId(),
			'DELIVERY_SERVICE_NAME' => $boxLabel->getDeliveryServiceName(),
			'URL' => $boxLabel->getUrl(),
		];
	}

	public function render(array $items, array $settings = [])
	{
		if ($this->useRenderFile($settings))
		{
			$this->disableAutoPrint();
			$result = $this->renderFileWindow($items, $settings);
		}
		else
		{
			$result = $this->renderDocument($items, $settings);
		}

		return $result;
	}

	protected function useRenderFile(array $settings)
	{
		return (
			!isset($settings['HTML'])
			|| (string)$settings['HTML'] !== Market\Ui\UserField\BooleanType::VALUE_Y
		);
	}

	protected function disableAutoPrint()
	{
		global $APPLICATION;

		$APPLICATION->SetPageProperty('YAMARKET_PAGE_PRINT', 'N');
	}

	protected function renderFileWindow(array $items, array $settings = [])
	{
		return
			$this->renderFileWindowList($items)
			. PHP_EOL
			. $this->renderFileWindowScript($items);
	}

	protected function renderFileWindowList(array $items)
	{
		$orderIds = array_column($items, 'ORDER_ID');
		$orderIds = array_unique($orderIds);
		$hasFewOrders = count($orderIds) > 1;
		$activeOrder = null;

		$result = sprintf(
			'<h1>%s</h1>',
			static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_BOX_LABEL_PDF_TITLE')
		);
		$result .= '<ul>';

		foreach ($items as $item)
		{
			$downloadUrl = $this->getDownloadLink($item);

			if ($hasFewOrders && $activeOrder !== $item['ORDER_ID'])
			{
				$activeOrder = $item['ORDER_ID'];

				$result .= '</ul>';
				$result .= sprintf(
					'<h3>%s</h3>',
					static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_BOX_LABEL_ORDER_TITLE', [ '#ORDER_ID#' => $activeOrder ])
				);
				$result .= '<ul>';
			}

			$result .= sprintf(
				'<li><a href="%s">%s</a></li>',
				htmlspecialcharsbx($downloadUrl),
				static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_BOX_LABEL_ITEM', [ '#NUMBER#' => $item['NUMBER'] ])
			);
		}

		$result .= '</ul>';

		return $result;
	}

	protected function renderFileWindowScript(array $items)
	{
		$result = '<script>';
		$result .= '(function() {';
		$result .= 'var isBlocked = false;';
		$result .= 'var newWindow;';

		foreach ($items as $itemKey => $item)
		{
			$downloadUrl = $this->getDownloadLink($item);

			$result .= PHP_EOL . sprintf('
				newWindow = window.open("%s");
				
				if (!newWindow || newWindow.closed || typeof newWindow.closed === "undefined") {
					isBlocked = true;
				}',
				\CUtil::addslashes($downloadUrl)
			);
		}

		$result .= PHP_EOL . '!isBlocked && window.close();';
		$result .= '})();';
		$result .= PHP_EOL . '</script>';

		return $result;
	}

	protected function getDownloadLink($item)
	{
		return Market\Ui\Admin\Path::getModuleUrl('trading_file_download', [
			'setup' => $this->provider->getOptions()->getSetupId(),
			'url' => $item['URL'],
		]);
	}

	protected function renderDocument(array $items, array $settings = [])
	{
		$options = $this->provider->getOptions();

		$parameters = [
			'ITEMS' => $items,
			'SERVICE_LOGO_SRC' => $this->provider->getInfo()->getLogoPath(),
			'COMPANY_LEGAL_NAME' => $options->getCompanyLegalName(),
			'COMPANY_LOGO' => $options->getCompanyLogo(),
			'COMPANY_NAME' => $options->getCompanyName(),
		];
		$parameters += $settings;

		return $this->renderComponent('boxlabel', $parameters);
	}
}