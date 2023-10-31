<?php

namespace Yandex\Market\Trading\Service\Marketplace\Document;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class BoxLabel extends TradingService\Reference\Document\AbstractDocument
	implements
		TradingService\Reference\Document\HasLoadItems,
		TradingService\Reference\Document\HasRenderFile
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
		return Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER;
	}

	public function getSettings()
	{
		return [
			'FORMAT' => [
				'TYPE' => 'enumeration',
				'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_BOX_LABEL_FORMAT'),
				'VALUES' => [
					[
						'ID' => 'A6',
						'VALUE' => static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_BOX_LABEL_FORMAT_A6', null, 'A6'),
					],
					[
						'ID' => 'A4',
						'VALUE' => static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_BOX_LABEL_FORMAT_A4', null, 'A4'),
					],
					[
						'ID' => 'A7',
						'VALUE' => static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_BOX_LABEL_FORMAT_A7', null, 'A7'),
					],
				],
				'SETTINGS' => [
					'ALLOW_NO_VALUE' => 'N',
				],
				'PERSISTENT' => 'Y',
			],
			'BUNDLE' => [
				'TYPE' => 'boolean',
				'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_DOCUMENT_BOX_LABEL_BUNDLE'),
				'PERSISTENT' => 'Y',
			],
		];
	}

	public function loadItems($entitySelect)
	{
		/** @var Market\Api\Reference\HasOauthConfiguration $options */
		$options = $this->provider->getOptions();
		$result = [];

		foreach ($entitySelect['ORDER'] as $orderId)
		{
			$result[] = [
				'URL' => sprintf(
					'https://api.partner.market.yandex.ru/campaigns/%s/orders/%s/delivery/labels.json',
					$options->getCampaignId(),
					$orderId
				),
				'ORDER_ID' => $orderId,
				'NUMBER' => $orderId,
			];
		}

		return $result;
	}

	public function canRenderFile(array $items, array $settings = [])
	{
		return count($items) === 1;
	}

	public function renderFile(array $items, array $settings = [])
	{
		/** @var Market\Api\Reference\HasOauthConfiguration $options */
		$options = $this->provider->getOptions();
		$items = $this->extendItems($items, $settings);
		$item = reset($items);

		return Market\Api\Partner\File\Facade::download($options, $item['URL']);
	}

	public function render(array $items, array $settings = [])
	{
		$items = $this->extendItems($items, $settings);

		$this->disableAutoPrint();

		if ($this->useBundle($settings))
		{
			$result = $this->renderDocument($items, $settings, 'pdfbundle');
		}
		else
		{
			$result = $this->renderFileWindow($items, $settings);
		}

		return $result;
	}

	protected function extendItems(array $items, array $settings)
	{
		if (empty($settings['FORMAT']) || $settings['FORMAT'] === 'A6') { return $items; }

		foreach ($items as &$item)
		{
			$item['URL'] .=
				(mb_strpos($item['URL'], '?') === false ? '?' : '&')
				. http_build_query([
					'format' => $settings['FORMAT'],
				]);
		}
		unset($item);

		return $items;
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

	protected function getDownloadLink($item)
	{
		return Market\Ui\Admin\Path::getModuleUrl('trading_file_download', [
			'setup' => $this->provider->getOptions()->getSetupId(),
			'url' => $item['URL'],
		]);
	}

	protected function renderDocument(array $items, array $settings = [], $template = 'boxlabel')
	{
		$options = $this->provider->getOptions();

		$parameters = [
			'ITEMS' => $items,
			'SERVICE_LOGO_SRC' => $this->provider->getInfo()->getLogoPath(),
			'COMPANY_LEGAL_NAME' => $options->getCompanyLegalName(),
			'COMPANY_LOGO' => $options->getCompanyLogo(),
			'COMPANY_NAME' => $options->getCompanyName(),
			'SETUP_ID' => $this->provider->getOptions()->getSetupId(),
		];
		$parameters += $settings;

		return $this->renderComponent($template, $parameters);
	}
}