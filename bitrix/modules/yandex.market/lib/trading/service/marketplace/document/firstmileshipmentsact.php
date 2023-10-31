<?php

namespace Yandex\Market\Trading\Service\Marketplace\Document;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @property TradingService\Marketplace\Provider $provider
*/
class FirstMileShipmentsAct extends TradingService\Reference\Document\AbstractDocument
	implements
		TradingService\Reference\Document\HasLoadItems,
		TradingService\Reference\Document\HasRenderFile
{
	use Market\Reference\Concerns\HasMessage { getMessage as protected getMessageInternal; }

	public function getTitle($version = '')
	{
		$suffix = $version !== '' ? '_' . Market\Data\TextString::toUpper($version) : '';

		return self::getMessageInternal('TITLE' . $suffix);
	}

	public function getMessage($type)
	{
		$suffix = Market\Data\TextString::toUpper($type);

		return self::getMessageInternal($suffix, null, '');
	}

	public function getSourceType()
	{
		return Market\Trading\Entity\Registry::ENTITY_TYPE_LOGISTIC_SHIPMENT;
	}

	public function getEntityType()
	{
		return Market\Trading\Entity\Registry::ENTITY_TYPE_LOGISTIC_SHIPMENT;
	}

	public function getSettings()
	{
		return [
			'BUNDLE' => [
				'TYPE' => 'boolean',
				'NAME' => self::getMessageInternal('BUNDLE'),
				'PERSISTENT' => 'Y',
			],
		];
	}

	public function loadItems($entitySelect)
	{
		$entityIds = (array)$entitySelect['LOGISTIC_SHIPMENT'];

		return array_map(static function($shipmentId) { return [ 'ID' => $shipmentId ]; }, $entityIds);
	}

	public function canRenderFile(array $items, array $settings = [])
	{
		return count($items) === 1;
	}

	public function renderFile(array $items, array $settings = [])
	{
		/** @var Market\Api\Reference\HasOauthConfiguration $options */
		$options = $this->provider->getOptions();
		$item = reset($items);

		return Market\Api\Partner\File\Facade::download($options, $this->getServicePath($item));
	}

	public function render(array $items, array $settings = [])
	{
		$this->disableAutoPrint();

		if ($this->useBundle($settings))
		{
			$result = $this->renderBundle($items, $settings);
		}
		else
		{
			$result = $this->renderFileWindow($items, $settings);
		}

		return $result;
	}

	protected function useBundle(array $settings)
	{
		return (
			isset($settings['BUNDLE'])
			&& (string)$settings['BUNDLE'] === Market\Ui\UserField\BooleanType::VALUE_Y
		);
	}

	protected function disableAutoPrint()
	{
		global $APPLICATION;

		$APPLICATION->SetPageProperty('YAMARKET_PAGE_PRINT', 'N');
	}

	protected function renderFileWindow(array $items, array $settings = [])
	{
		return $this->renderFileWindowList($items);
	}

	protected function renderFileWindowList(array $items)
	{
		$result = sprintf(
			'<h1>%s</h1>',
			self::getMessageInternal('DOWNLOAD_TITLE')
		);
		$result .= '<ul>';

		foreach ($items as $item)
		{
			$downloadUrl = $this->getDownloadLink($item);

			$result .= sprintf(
				'<li><a href="%s">%s</a></li>',
				htmlspecialcharsbx($downloadUrl),
				static::getMessageInternal('DOWNLOAD_ITEM', [ '#ID#' => $item['ID'] ])
			);
		}

		$result .= '</ul>';

		return $result;
	}

	/** @deprecated */
	protected function renderFileWindowScript(array $items)
	{
		$result = '<script>';
		$result .= '(function() {';
		$result .= 'var isBlocked = false;';
		$result .= 'var newWindow;';

		foreach ($items as $item)
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
			'url' => $this->getServicePath($item),
		]);
	}

	protected function getServicePath($item)
	{
		$request = new TradingService\Marketplace\Api\ShipmentAct\Request();
		$request->setCampaignId($this->provider->getOptions()->getCampaignId());
		$request->setShipmentId($item['ID']);

		return $request->getPath();
	}

	protected function renderBundle(array $items, array $settings = [])
	{
		$parameters = [
			'ITEMS' => $this->fillItemsUrl($items),
			'SETUP_ID' => $this->provider->getOptions()->getSetupId(),
		];
		$parameters += $settings;

		return $this->renderComponent('pdfbundle', $parameters);
	}

	protected function fillItemsUrl(array $items)
	{
		foreach ($items as &$item)
		{
			$item['URL'] = $this->getServicePath($item);
		}
		unset($item);

		return $items;
	}
}