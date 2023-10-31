<?php

namespace Yandex\Market\Trading\Entity\SaleCrm;

use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Setup as TradingSetup;
use Yandex\Market\Ui\Trading as UiTrading;
use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Crm;

class AdminExtension extends TradingEntity\Sale\AdminExtension
{
	use Market\Reference\Concerns\HasMessage;

	const MENU_ID = 'yandexMarketRouter';

	public static function onEntityDetailsTabsInitialized(Main\Event $event)
	{
		try
		{
			$orderId = static::eventOrderId($event);

			if ($orderId === null) { return null; }

			$orderInfo = static::getOrderInfo([ 'ID' => $orderId ]);
			$setup = TradingSetup\Model::loadByTradingInfo($orderInfo);
			$tabSet = new UiTrading\OrderViewTabSet($setup, $orderInfo['EXTERNAL_ORDER_ID']);

			$tabSet->setTemplate('bitrix24');
			$tabSet->checkReadAccess();
			$tabSet->checkSupport();
			$tabSet->preloadAssets();

			$moduleTabs = $tabSet->getTabs();
			$moduleTab = reset($moduleTabs);

			foreach (static::getExtensions('crm_order', $setup) as $extension)
			{
				if (!$extension->isSupported()) { continue; }

				$extension->initialize($orderInfo);
			}

			$tabs = (array)$event->getParameter('tabs');
			$tabs[] = [
				'id' => Market\Data\TextString::toLower('tab_' . Market\Config::getLangPrefix() . 'trading_order'),
				'name' => $moduleTab['TAB'],
				'loader' => [
					'serviceUrl' => $tabSet->getContentsUrl(),
				],
			];

			$result = new Main\EventResult(Main\EventResult::SUCCESS, [
				'tabs' => $tabs,
			]);
		}
		catch (Main\SystemException $exception)
		{
			$result = null;
		}

		return $result;
	}

	protected static function eventOrderId(Main\Event $event)
	{
		if (!defined('\CCrmOwnerType::Order')) { return null; }

		$typeId = (int)$event->getParameter('entityTypeID');
		$entityId = $event->getParameter('entityID');

		if ($typeId === \CCrmOwnerType::Order)
		{
			$result = $entityId;
		}
		else
		{
			$result = static::searchBindingOrder($typeId, $entityId);
		}

		return $result;
	}

	protected static function searchBindingOrder($typeId, $dealId)
	{
		if (!defined('ENTITY_CRM_ORDER_ENTITY_BINDING') || $typeId !== \CCrmOwnerType::Deal) { return null; }

		/** @var Crm\Order\EntityBinding $bindingClassName */
		$registry = Sale\Registry::getInstance(Sale\Registry::ENTITY_ORDER);
		$bindingClassName = $registry->get(ENTITY_CRM_ORDER_ENTITY_BINDING);
		$result = null;

		if (
			is_subclass_of($bindingClassName, Crm\Order\EntityBinding::class)
			|| strtolower(Crm\Order\EntityBinding::class) === strtolower(ltrim($bindingClassName, '\\'))
		)
		{
			$filter = [
				'=OWNER_ID' => $dealId,
				'=OWNER_TYPE_ID' => $typeId,
			];
		}
		else
		{
			$filter = [
				'=DEAL_ID' => $dealId
			];
		}

		$query = $bindingClassName::getList([
			'filter' => $filter,
			'select' => [ 'ORDER_ID' ],
			'limit' => 1,
		]);

		if ($row = $query->fetch())
		{
			$result = $row['ORDER_ID'];
		}

		return $result;
	}

	protected static function getExtensions($action, TradingSetup\Model $setup)
	{
		if ($action === 'crm_order')
		{
			return [
				new UiTrading\OrderView\CrmCancelReason($setup),
			];
		}

		return parent::getExtensions($action, $setup);
	}

	public function install()
	{
		$this->unbindParent();
		parent::install();
		$this->installCrm();
	}

	protected function unbindParent()
	{
		$parent = new TradingEntity\Sale\AdminExtension($this->environment);
		$parent->unbind();
	}

	protected function installCrm()
	{
		foreach (Market\Data\Site::getVariants() as $siteId)
		{
			if (!Market\Data\Site::isCrm($siteId)) { continue; }

			list($documentRoot, $sitePath) = Market\Data\Site::getDocumentRoot($siteId);
			$publicDirectory = $this->getCrmPublicDirectory($documentRoot . $sitePath);

			if ($this->isCrmPublicAutoupdateDisabled($publicDirectory)) { continue; }

			$publicRelative = Market\Utils\IO\Path::absoluteToRelative(
				$publicDirectory->getPath(),
				$documentRoot
			);
			$publicRelative = '/' . $publicRelative;

			$this->copyCrmPublic($publicDirectory);
			$this->configureCrmRouter($siteId, $publicRelative);
			$this->addCrmMenu($siteId, $publicRelative);
		}
	}

	protected function getCrmPublicDirectory($documentRoot)
	{
		$rootRelative = '/' . str_replace('.', '', Market\Config::getModuleName());
		$repeatCount = 0;
		$repeatLimit = 10;
		$result = null;

		do
		{
			$relative = $rootRelative . ($repeatCount > 0 ? randString(3, '0123456789') : '') . '/marketplace';
			$path = Main\IO\Path::normalize($documentRoot . $relative);
			$directory = new Main\IO\Directory($path);

			if (!$directory->isExists())
			{
				$result = $directory;
				break;
			}

			$indexPath = $directory->getPath() . '/index.php';
			$indexFile = new Main\IO\File($indexPath);
			$indexContents = $indexFile->isExists() ? $indexFile->getContents() : '';
			$packageMarker = '@package ' . Market\Config::getModuleName();

			if (Market\Data\TextString::getPosition($indexContents, $packageMarker) !== false)
			{
				$result = $directory;
				break;
			}
		}
		while (++$repeatCount < $repeatLimit);

		if ($result === null)
		{
			throw new Main\SystemException('so many %s directories in %s. try repeat later', $rootRelative, $documentRoot);
		}

		return $result;
	}

	protected function isCrmPublicAutoupdateDisabled(Main\IO\Directory $directory)
	{
		if (!$directory->isExists()) { return false; }

		$indexPath = $directory->getPath() . '/index.php';
		$indexFile = new Main\IO\File($indexPath);

		if (!$indexFile->isExists()) { return true; }

		$indexContents = $indexFile->getContents();
		$marker = '@autoupdate ' . Market\Config::getModuleName();

		return (Market\Data\TextString::getPosition($indexContents, $marker) === false);
	}

	protected function copyCrmPublic(Main\IO\Directory $directory)
	{
		$from = Market\Config::getModulePath() . '/../install/public/crm/marketplace';
		$from = realpath($from);
		$to = $directory->getPath();

		CopyDirFiles($from, $to, true, true);
	}

	protected function configureCrmRouter($siteId, $relative)
	{
		Main\UrlRewriter::add($siteId, [
			'CONDITION' => '#^' . $relative . '/#',
			'RULE' => '',
			'ID' => '',
			'PATH' => $relative . '/index.php',
		]);
	}

	protected function addCrmMenu($siteId, $relative)
	{
		$optionName = 'left_menu_items_to_all_' . $siteId;
		$existsEncoded = (string)Main\Config\Option::get('intranet', $optionName, '', $siteId);
		$exists = $existsEncoded !== '' ? unserialize($existsEncoded, ['allowed_classes' => false]) : [];

		if (empty($exists)) { $exists = []; }

		if (!is_array($exists))
		{
			trigger_error(sprintf('cant parse crm menu, install manual menu link to %s for site %s', $relative, $siteId), E_USER_WARNING);
			return;
		}

		$matched = array_filter($exists, static function($item) { return $item['ID'] === static::MENU_ID; });

		if (!empty($matched)) { return; }

		$exists[] = [
			'TEXT' => self::getMessage('CRM_MENU_TITLE', null, Market\Config::getModuleName()),
			'LINK' => $relative,
			'ID' => static::MENU_ID,
		];

		Main\Config\Option::set('intranet', $optionName, serialize($exists), $siteId);
	}

	protected function getEventHandlers()
	{
		return array_merge(parent::getEventHandlers(), [
			[
				'module' => 'crm',
				'event' => 'onEntityDetailsTabsInitialized',
			],
		]);
	}
}
