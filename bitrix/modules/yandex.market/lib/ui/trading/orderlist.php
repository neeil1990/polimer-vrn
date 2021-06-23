<?php

namespace Yandex\Market\Ui\Trading;

use Yandex\Market;
use Bitrix\Main;

class OrderList extends Market\Ui\Reference\Page
{
	use Market\Reference\Concerns\HasLang;
	use Market\Ui\Trading\Concerns\HasHandleMigration;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	protected function getReadRights()
	{
		return Market\Ui\Access::RIGHTS_PROCESS_TRADING;
	}

	public function show()
	{
		$setupCollection = $this->getSetupCollection();
		$setupId = $this->getRequestSetupId();
		$setup = $this->resolveSetup($setupCollection, $setupId);

		$this->showSetupSelector($setupCollection, $setup->getId());
		$this->showOrderList($setup);
	}

	public function handleException(\Exception $exception)
	{
		$isHandled = (
			$this->handleMigration($exception)
			|| $this->handleDeprecated($exception)
		);

		if (!$isHandled)
		{
			\CAdminMessage::ShowMessage([
				'TYPE' => 'ERROR',
				'MESSAGE' => $exception->getMessage(),
			]);
		}
	}

	protected function showSetupSelector(Market\Trading\Setup\Collection $setupCollection, $selectedId)
	{
		global $APPLICATION;

		$options = $this->buildRoleOptions($setupCollection);

		if (count($options) <= 1) { return; }

		$usedBehaviors = array_column($options, 'BEHAVIOR');
		$usedSites = array_unique(array_column($options, 'SITE_ID'));
		$useOnlyGroup = true;

		if (count($usedBehaviors) !== count(array_unique($usedBehaviors)))
		{
			$useOnlyGroup = false;
		}
		else if (count($usedSites) > 1)
		{
			$useOnlyGroup = false;
		}

		echo '<div style="margin-bottom: 10px;">';

		foreach ($options as $option)
		{
			$title = $useOnlyGroup ? $option['GROUP'] : $option['VALUE'];

			if ($option['ID'] === (int)$selectedId)
			{
				echo sprintf(
					' <span class="adm-btn adm-btn-active">%s</span>',
					htmlspecialcharsbx($title)
				);
			}
			else
			{
				$url = $APPLICATION->GetCurPageParam(http_build_query([ 'setup' => $option['ID'] ]), [ 'setup' ]);

				echo sprintf(
					' <a class="adm-btn" href="%s">%s</a>',
					htmlspecialcharsbx($url),
					htmlspecialcharsbx($title)
				);
			}
		}

		echo '</div>';
	}

	protected function buildRoleOptions(Market\Trading\Setup\Collection $setupCollection)
	{
		$result = [];
		$usedBehaviors = [];

		/** @var Market\Trading\Setup\Model $setup */
		foreach ($setupCollection as $setup)
		{
			if (!$setup->isActive()) { continue; }

			$siteId = $setup->getSiteId();
			$service = $setup->getService();
			$behaviorCode = $service->getBehaviorCode();
			$behaviorTitle = $setup->getService()->getInfo()->getTitle('BEHAVIOR');
			$title = $setup->getField('NAME');

			$usedBehaviors[$behaviorCode] = true;

			if ($title === $setup->getDefaultName())
			{
				$siteEntity = $setup->getEnvironment()->getSite();
				$title = sprintf('[%s] %s (%s)', $siteId, $siteEntity->getTitle($siteId), $behaviorTitle);
			}

			$result[] = [
				'ID' => (int)$setup->getId(),
				'VALUE' => $title,
				'BEHAVIOR' => $behaviorCode,
				'SITE_ID' => $siteId,
				'GROUP' => $behaviorTitle,
			];
		}

		if (count($usedBehaviors) > 1)
		{
			$result = $this->sortRoleOptionsByBehavior($result);
		}

		return $result;
	}

	protected function sortRoleOptionsByBehavior($options)
	{
		$serviceCode = $this->getServiceCode();
		$behaviors = Market\Trading\Service\Manager::getBehaviors($serviceCode);
		$behaviorsSort = array_flip($behaviors);

		uasort($options, static function($optionA, $optionB) use ($behaviorsSort) {
			$sortA = isset($behaviorsSort[$optionA['BEHAVIOR']]) ? $behaviorsSort[$optionA['BEHAVIOR']] : 500;
			$sortB = isset($behaviorsSort[$optionB['BEHAVIOR']]) ? $behaviorsSort[$optionB['BEHAVIOR']] : 500;

			if ($sortA === $sortB) { return 0; }

			return $sortA < $sortB ? -1 : 1;
		});

		return $options;
	}

	protected function showOrderList(Market\Trading\Setup\Model $setup)
	{
		global $APPLICATION;

		$documents = $this->getPrintDocuments($setup);

		$this->initializePrintActions($setup, $documents);

		$APPLICATION->IncludeComponent('yandex.market:admin.grid.list', '', [
			'GRID_ID' => 'YANDEX_MARKET_ADMIN_TRADING_ORDER_LIST',
			'PROVIDER_TYPE' => 'TradingOrder',
			'CONTEXT_MENU_EXCEL' => 'Y',
			'SETUP_ID' => $setup->getId(),
			'BASE_URL' => $this->getComponentBaseUrl($setup),
			'PAGER_LIMIT' => 50,
			'DEFAULT_FILTER_FIELDS' => [
				'STATUS',
				'DATE_CREATE',
				'DATE_SHIPMENT',
				'FAKE',
			],
			'DEFAULT_LIST_FIELDS' => [
				'ID',
				'ACCOUNT_NUMBER',
				'DATE_CREATE',
				'DATE_SHIPMENT',
				'BASKET',
				'TOTAL',
				'SUBSIDY',
				'STATUS_LANG',
			],
			'ROW_ACTIONS' => $this->getOrderListRowActions($setup, $documents),
			'ROW_ACTIONS_PERSISTENT' => 'Y',
			'GROUP_ACTIONS' => $this->getOrderListGroupActions($setup, $documents),
			'GROUP_ACTIONS_PARAMS' => [
				'disable_action_target' => true,
			],
			'CHECK_ACCESS' => !Market\Ui\Access::isWriteAllowed(),
		]);
	}

	protected function initializePrintActions(Market\Trading\Setup\Model $setup, $documents)
	{
		static::loadMessages();

		Market\Ui\Library::load('jquery');

		Market\Ui\Assets::loadPluginCore();
		Market\Ui\Assets::loadPlugins([
			'lib.dialog',
			'lib.printdialog',
			'OrderList.Print',
		]);

		Market\Ui\Assets::loadMessages([
			'PRINT_DIALOG_SUBMIT',
			'UI_TRADING_ORDER_LIST_PRINT_REQUIRE_SELECT_ORDERS'
		]);

		$pageAssets = Main\Page\Asset::getInstance();

		$printParams = [
			'url' => Market\Ui\Admin\Path::getModuleUrl('trading_order_print', [
				'view' => 'dialog',
				'setup' => $setup->getId(),
				'alone' => 'Y',
			]),
			'items' => $this->getPrintItems($documents),
		];

		$pageAssets->addString(
			'<script>
				BX.YandexMarket.OrderList.print = new BX.YandexMarket.OrderList.Print(null, ' . \CUtil::PhpToJSObject($printParams) . ');
			</script>',
			false,
			Main\Page\AssetLocation::AFTER_JS
		);
	}

	/**
	 * @param Market\Trading\Service\Reference\Document\AbstractDocument[] $documents
	 *
	 * @return array
	 */
	protected function getPrintItems($documents)
	{
		$result = [];

		foreach ($documents as $type => $document)
		{
			$result[] = [
				'TYPE' => $type,
				'TITLE' => $document->getTitle(),
			];
		}

		return $result;
	}

	/**
	 * @param Market\Trading\Setup\Model $setup
	 * @param Market\Trading\Service\Reference\Document\AbstractDocument[] $documents
	 *
	 * @return array
	 */
	protected function getOrderListRowActions(Market\Trading\Setup\Model $setup, $documents)
	{
		$result = [];

		$result['EDIT'] = [
			'ICON' => 'view',
			'TEXT' =>
				$setup->getService()->getInfo()->getMessage('ORDER_VIEW_TAB')
				?: static::getLang('UI_TRADING_ORDER_LIST_ACTION_ORDER_VIEW'),
			'MODAL' => 'Y',
			'MODAL_TITLE' => static::getLang('UI_TRADING_ORDER_LIST_ACTION_ORDER_VIEW_MODAL_TITLE'),
			'MODAL_PARAMETERS' => [
				'width' => 1024,
				'height' => 750,
			],
			'URL' => Market\Ui\Admin\Path::getModuleUrl('trading_order_view', [
				'lang' => LANGUAGE_ID,
				'view' => 'popup',
				'setup' => $setup->getId(),
				'site' => $setup->getSiteId(),
			]) . '&id=#ID#',
		];

		foreach ($documents as $type => $document)
		{
			$key = 'PRINT_' . Market\Data\TextString::toUpper($type);

			$result[$key] = [
				'TEXT' => $document->getTitle('PRINT'),
				'METHOD' => 'BX.YandexMarket.OrderList.print.openDialog("' .  $type .  '", "#ID#")',
			];
		}

		return $result;
	}

	/**
	 * @param Market\Trading\Setup\Model $setup
	 * @param Market\Trading\Service\Reference\Document\AbstractDocument[] $documents
	 *
	 * @return array
	 */
	protected function getOrderListGroupActions(Market\Trading\Setup\Model $setup, $documents)
	{
		$result = [];

		foreach ($documents as $type => $document)
		{
			$key = 'PRINT_' . Market\Data\TextString::toUpper($type);
			$needSelectOrders = $document->getEntityType() !== Market\Trading\Entity\Registry::ENTITY_TYPE_NONE;

			if ($needSelectOrders)
			{
				$action = sprintf('BX.YandexMarket.OrderList.print.openGroupDialog("%s", YANDEX_MARKET_ADMIN_TRADING_ORDER_LIST)', $type);
			}
			else
			{
				$action = sprintf('BX.YandexMarket.OrderList.print.openDialog("%s")', $type);
			}

			$result[$key] = [
				'type' => 'button',
				'value' => $key,
				'name' => $document->getTitle('PRINT'),
				'action' => $action,
			];
		}

		return $result;
	}

	protected function getPrintDocuments(Market\Trading\Setup\Model $setup)
	{
		$printer = $setup->getService()->getPrinter();
		$result = [];

		foreach ($printer->getTypes() as $type)
		{
			$result[$type] = $printer->getDocument($type);
		}

		return $result;
	}

	protected function getComponentBaseUrl(Market\Trading\Setup\Model $setup)
	{
		global $APPLICATION;

		$queryParameters = array_filter([
			'lang' => LANGUAGE_ID,
			'service' => $setup->getServiceCode(),
			'id' => $this->getRequestSetupId(),
		]);

		return $APPLICATION->GetCurPage() . '?' . http_build_query($queryParameters);
	}

	protected function getSetupCollection()
	{
		$serviceCode = $this->getServiceCode();

		return Market\Trading\Setup\Collection::loadByFilter([
			'filter' => [
				'=TRADING_SERVICE' => $serviceCode,
			],
		]);
	}

	protected function getServiceCode()
	{
		$result = (string)$this->request->get('service');

		if ($result === '')
		{
			$message = static::getLang('UI_TRADING_ORDER_LIST_SERVICE_CODE_NOT_SET');
			throw new Main\ArgumentException($message, 'service');
		}

		if (!Market\Trading\Service\Manager::isExists($result))
		{
			$message = static::getLang('UI_TRADING_ORDER_LIST_SERVICE_CODE_INVALID', [ '#SERVICE#' => $result ]);
			throw new Main\SystemException($message);
		}

		return $result;
	}

	protected function getRequestSetupId()
	{
		return $this->request->get('setup');
	}

	/**
	 * @param Market\Trading\Setup\Collection $setupCollection
	 * @param int|null $setupId
	 *
	 * @return Market\Trading\Setup\Model
	 * @throws Main\SystemException
	 */
	protected function resolveSetup(Market\Trading\Setup\Collection $setupCollection, $setupId = null)
	{
		if ($setupId !== null)
		{
			$setup = $setupCollection->getItemById($setupId);

			if ($setup === null)
			{
				$message = static::getLang('UI_TRADING_ORDER_LIST_SETUP_NOT_FOUND', [ '#ID#' => $setupId ]);
				throw new Main\ObjectNotFoundException($message);
			}

			if (!$setup->isActive())
			{
				$message = static::getLang('UI_TRADING_ORDER_LIST_SETUP_INACTIVE', [ '#ID#' => $setupId ]);
				throw new Main\SystemException($message);
			}
		}
		else
		{
			$setup = $setupCollection->getActive();

			if ($setup === null)
			{
				$message = static::getLang('UI_TRADING_ORDER_LIST_SETUP_NOT_EXISTS');
				throw new Main\ObjectNotFoundException($message);
			}
		}

		return $setup;
	}
}