<?php

namespace Yandex\Market\Trading\Service\Marketplace\Document;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class ReceptionTransferAct extends TradingService\Reference\Document\AbstractDocument
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getTitle($version = '')
	{
		$suffix = $version !== '' ? '_' . Market\Data\TextString::toUpper($version) : '';

		return static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_RECEPTION_TRANSFER_ACT' . $suffix);
	}

	public function getMessage($type)
	{
		$suffix = Market\Data\TextString::toUpper($type);

		return static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_RECEPTION_TRANSFER_ACT_' . $suffix, null, '');
	}

	public function getEntityType()
	{
		return Market\Trading\Entity\Registry::ENTITY_TYPE_NONE;
	}

	public function render(array $items, array $settings = [])
	{
		$this->disableAutoPrint();

		return $this->renderFileWindow($settings);
	}

	protected function disableAutoPrint()
	{
		global $APPLICATION;

		$APPLICATION->SetPageProperty('YAMARKET_PAGE_PRINT', 'N');
	}

	protected function renderFileWindow(array $settings = [])
	{
		return
			$this->renderFileWindowContents()
			. PHP_EOL
			. $this->renderFileWindowScript();
	}

	protected function renderFileWindowContents()
	{
		$result = sprintf(
			'<h1>%s</h1>',
			static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_RECEPTION_TRANSFER_ACT')
		);
		$result .= sprintf(
			'<p><a href="%s">%s</a></p>',
			$this->getDownloadLink(),
			static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_RECEPTION_TRANSFER_ACT_OPEN')
		);

		return $result;
	}

	protected function renderFileWindowScript()
	{
		$downloadUrl = $this->getDownloadLink();
		$result = '<script>';
		$result .= PHP_EOL . sprintf(
			'window.location = "%s";',
			\CUtil::addslashes($downloadUrl)
		);
		$result .= PHP_EOL . '</script>';

		return $result;
	}

	protected function getDownloadLink()
	{
		return Market\Ui\Admin\Path::getModuleUrl('trading_file_download', [
			'setup' => $this->provider->getOptions()->getSetupId(),
			'url' => $this->getDocumentUrl(),
		]);
	}

	protected function getDocumentUrl()
	{
		return sprintf(
			'/campaigns/%s/shipments/reception-transfer-act',
			$this->provider->getOptions()->getCampaignId()
		);
	}
}