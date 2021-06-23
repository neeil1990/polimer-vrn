<?php

namespace Yandex\Market\Ui\Trading;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class SetupEdit extends Market\Ui\Reference\Form
{
	const STATE_NOT_INSTALLED = 'notInstalled';
	const STATE_DEPRECATED = 'deprecated';
	const STATE_MIGRATE = 'migrate';
	const STATE_EDIT = 'edit';

	const ACTION_ACTIVATE = 'activate';
	const ACTION_DEACTIVATE = 'deactivate';
	const ACTION_DEPRECATE = 'deprecate';
	const ACTION_MIGRATE = 'migrate';

	protected $setup;

	public function hasRequest()
	{
		return ($this->getRequestAction() !== null);
	}

	public function isRequestHandledByView($state)
	{
		if ($state === static::STATE_EDIT)
		{
			$action = $this->getRequestAction();
			$selfHandled = [
				static::ACTION_ACTIVATE => true,
				static::ACTION_DEACTIVATE => true,
				static::ACTION_DEPRECATE => true,
			];

			$result = !isset($selfHandled[$action]);
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	protected function getRequestAction()
	{
		return $this->request->get('action');
	}

	public function refreshPage()
	{
		global $APPLICATION;

		$setup = $this->getSetup();
		$query = [
			'site' => $setup->getSiteId(),
			'service' => $setup->getServiceCode(),
			'id' => $setup->getId(),
		];
		$url = $APPLICATION->GetCurPageParam(
			http_build_query($query),
			array_merge(
				array_keys($query),
				[ 'action', 'sessid' ]
			)
		);

		LocalRedirect($url);
	}

	public function processRequest()
	{
		$action = $this->getRequestAction();

		switch ($action)
		{
			case static::ACTION_DEPRECATE:
				$this->processRequestDeprecate();
			break;

			case static::ACTION_MIGRATE:
				$this->processRequestMigrate();
			break;

			case static::ACTION_ACTIVATE:
				$this->processRequestActivate();
			break;

			case static::ACTION_DEACTIVATE:
				$this->processRequestDeactivate();
			break;

			default:
				throw new Main\SystemException('requested action not implemented');
			break;
		}
	}

	protected function processRequestDeprecate()
	{
		$migrationService = $this->getDeprecationUseService();
		$setup = $this->getSetup();

		$this->applyMigration($setup, $migrationService);
	}

	protected function processRequestMigrate()
	{
		$service = $this->getSetup()->getService();

		foreach ($this->getMigratedSetupCollection() as $migrationSetup)
		{
			$this->applyMigration($migrationSetup, $service);
		}
	}

	protected function applyMigration(Market\Trading\Setup\Model $setup, Market\Trading\Service\Reference\Provider $service = null)
	{
		$connection = Market\Trading\Setup\Table::getEntity()->getConnection();

		try
		{
			$connection->startTransaction();

			$setup->migrate($service);
			$setup->validate();

			$connection->commitTransaction();
		}
		catch (Main\SystemException $exception)
		{
			$setup->rollback(); // myisam transaction emulation
			$connection->rollbackTransaction();

			throw $exception;
		}
	}

	protected function processRequestActivate()
	{
		$setup = $this->getSetup();

		if (!$setup->isInstalled() && $this->isServiceDeprecated())
		{
			throw new Main\SystemException('cant install deprecated service');
		}

		$setup->install();
		$setup->activate();
	}

	protected function processRequestDeactivate()
	{
		$setup = $this->getSetup();

		$setup->deactivate();
		$setup->uninstall();
	}

	public function resolveState()
	{
		$setup = $this->getSetup();
		$isInstalled = $setup->isInstalled();

		if ($isInstalled)
		{
			$result = static::STATE_EDIT;
		}
		else if ($this->isServiceDeprecated())
		{
			$result = static::STATE_DEPRECATED;
		}
		else if ($this->hasServiceMigrated() && $this->existsMigratedSetup())
		{
			$result = static::STATE_MIGRATE;
		}
		else
		{
			$result = static::STATE_NOT_INSTALLED;
		}

		return $result;
	}

	public function setTitle()
	{
		$this->applyTitle('INTRO');
	}

	public function setStateTitle($state = self::STATE_NOT_INSTALLED)
	{
		if ($state === static::STATE_EDIT)
		{
			$this->applyTitle();
		}
	}

	protected function applyTitle($version = '')
	{
		global $APPLICATION;

		$service = $this->getSetup()->getService();
		$title = $service->getOptions()->getTitle($version);

		$APPLICATION->SetTitle($title);
	}

	public function handleException(\Exception $exception)
	{
		\CAdminMessage::ShowMessage([
			'TYPE' => 'ERROR',
			'MESSAGE' => $exception->getMessage(),
		]);

		$this->handleEditById($exception);
		$this->handleMigration($exception);
	}

	protected function handleEditById(\Exception $exception)
	{
		global $APPLICATION;

		if (
			$exception instanceof Main\ArgumentException
			&& $exception->getParameter() === 'service'
			&& $this->request->getRequestMethod() === Main\Web\HttpClient::HTTP_GET
		)
		{
			$id = $this->request->getQuery('id');

			if ($id !== null)
			{
				$setup = Market\Trading\Setup\Model::loadById($id);
				$query = [
					'lang' => LANGUAGE_ID,
					'service' => $setup->getServiceCode(),
					'id' => $setup->getId(),
				];
				$url = $APPLICATION->GetCurPageParam(
					http_build_query($query),
					array_merge(array_keys($query), [ 'id' ])
				);

				LocalRedirect($url);
				die();
			}
		}
	}

	protected function handleMigration(\Exception $exception)
	{
		if (!Market\Migration\Controller::canRestore($exception)) { return; }

		$url = Market\Ui\Admin\Path::getModuleUrl('migration', [
			'lang' => LANGUAGE_ID,
		]);
		$title = Market\Config::getLang('ADMIN_TRADING_GO_RESTORE');

		echo sprintf('<a class="adm-btn" href="%s">%s</a><br /><br />', $url, $title);
	}

	public function show($state = self::STATE_NOT_INSTALLED)
	{
		switch ($state)
		{
			case static::STATE_DEPRECATED:
				$this->showDeprecatedMessage();
			break;

			case static::STATE_MIGRATE:
				$this->showMigrationForm();
			break;

			case static::STATE_NOT_INSTALLED:
				$this->redirectToAddForm();
			break;

			case static::STATE_EDIT:
				$this->showDeprecateProposal();
				$this->showEditForm();
				$this->showCheckAnnouncement();
			break;

			default:
				throw new Main\SystemException('view not implemented');
			break;
		}
	}

	protected function getFormActionUri()
	{
		global $APPLICATION;

		$query = array_filter([
			'service' => $this->getServiceCode(),
			'behaviour' => $this->getRequestBehaviorCode(),
			'id' => $this->getRequestedId(),
		]);

		return $APPLICATION->GetCurPageParam(
			http_build_query($query),
			array_keys($query)
		);
	}

	protected function showDeprecatedMessage()
	{
		global $APPLICATION;

		$code = $this->getDeprecationUse();
		$url = $APPLICATION->GetCurPageParam(http_build_query([ 'service' => $code ]), [ 'service' ]);

		\CAdminMessage::ShowMessage([
			'TYPE' => 'ERROR',
			'MESSAGE' => Market\Config::getLang('ADMIN_TRADING_DEPRECATED'),
			'DETAILS' => Market\Config::getLang('ADMIN_TRADING_DEPRECATED_DETAILS', [
				'#URL#' => $url,
			]),
			'HTML' => true,
		]);
	}

	protected function showDeprecateProposal()
	{
		global $APPLICATION;

		if (!$this->isServiceDeprecated()) { return; }

		$url = $APPLICATION->GetCurPageParam(
			http_build_query(['sessid' => bitrix_sessid(), 'action' => static::ACTION_DEPRECATE]),
			[ 'sessid', 'action ']
		);

		\CAdminMessage::ShowMessage([
			'TYPE' => 'ERROR',
			'MESSAGE' => Market\Config::getLang('ADMIN_TRADING_DEPRECATE_PROPOSAL'),
			'DETAILS' => Market\Config::getLang('ADMIN_TRADING_DEPRECATE_PROPOSAL_DETAILS', [
				'#URL#' => $url,
			]),
			'HTML' => true,
		]);
	}

	protected function showMigrationForm()
	{
		$this->showFormProlog();
		$this->showMigrateButton();
		$this->showFallbackButton();
		$this->showFormEpilog();
	}

	protected function showMigrateButton()
	{
		$isAllowed = $this->isAuthorized($this->getWriteRights());

		echo BeginNote();
		echo Market\Config::getLang('ADMIN_TRADING_MIGRATE_NOTE');
		echo EndNote();

		echo sprintf('<input type="hidden" name="action" value="%s" />', static::ACTION_MIGRATE);
		echo '<input 
			class="adm-btn-save ' . ($isAllowed ? '' : 'adm-btn-disabled') . '" 
			type="submit" 
			value="' . Market\Config::getLang('ADMIN_TRADING_MIGRATE_BUTTON') . '" 
			' . ($isAllowed ? '' : 'disabled') . '
		 />';
	}

	protected function showFallbackButton()
	{
		global $APPLICATION;

		$migratedCollection = $this->getMigratedSetupCollection();
		$migratedSetup = $migratedCollection->getActive() ?: $migratedCollection->offsetGet(0);

		if ($migratedSetup !== null)
		{
			$url = $APPLICATION->GetCurPageParam(
				http_build_query([
					'service' => $migratedSetup->getServiceCode(),
					'site' => $migratedSetup->getSiteId(),
				]),
				[ 'service', 'site' ]
			);

			echo ' ';
			echo sprintf(
				'<a class="adm-btn" href="%s">%s</a><br /><br />',
				$url,
				Market\Config::getLang('ADMIN_TRADING_FALLBACK_DEPRECATED')
			);
		}
	}

	protected function redirectToAddForm()
	{
		$url = Market\Ui\Admin\Path::getModuleUrl('trading_setup', [
			'lang' => LANGUAGE_ID,
			'service' => $this->getServiceCode(),
		]);

		LocalRedirect($url);
		die();
	}

	protected function showEditForm()
	{
		global $APPLICATION;

		$setup = $this->getSetup();
		$tabs = $this->getEditFormTabs();
		$fields = $this->getEditFormFields();
		$contextMenu = $this->getEditFormContextMenu();
		$writeRights = $this->getWriteRights();
		$resetConfirmMessage = Market\Config::getLang('ADMIN_TRADING_RESET_BUTTON_CONFIRM');

		$APPLICATION->IncludeComponent('yandex.market:admin.form.edit', '', [
			'FORM_ID' => 'YANDEX_MARKET_ADMIN_TRADING_EDIT',
			'PROVIDER_TYPE' => 'TradingSettings',
			'CONTEXT_MENU' => $contextMenu,
			'PRIMARY' => $setup->getId(),
			'TABS' => $tabs,
			'FIELDS' => $fields,
			'BUTTONS' => [
				[
					'BEHAVIOR' => 'save',
					'NAME' => Market\Config::getLang('ADMIN_TRADING_SAVE_BUTTON'),
				],
				[
					'NAME' => Market\Config::getLang('ADMIN_TRADING_RESET_BUTTON'),
					'ATTRIBUTES' => [
						'name' => 'postAction',
						'value' => 'reset',
						'onclick' => 'if (!confirm("' . addslashes($resetConfirmMessage) . '")) { return false; }'
					],
				],
			],
			'ALLOW_SAVE' => $this->isAuthorized($writeRights),
			'SAVE_PARTIALLY' => true,
			'FORM_ACTION_URI' => $this->getFormActionUri(),
		]);
	}

	protected function getEditFormContextMenu()
	{
		return array_filter([
			$this->getEditFormContextListItem(),
			$this->getEditFormContextActivateItem(),
			$this->getEditFormContextDeactivateItem(),
		]);
	}

	protected function getEditFormContextListItem()
	{
		return [
			'ICON' => 'btn_list',
			'LINK' => Market\Ui\Admin\Path::getModuleUrl('trading_list', [
				'service' => $this->getServiceCode(),
				'lang' => LANGUAGE_ID,
			]),
			'TEXT' => Market\Config::getLang('ADMIN_TRADING_MENU_LIST')
		];
	}

	protected function getEditFormContextActivateItem()
	{
		global $APPLICATION;

		if ($this->getSetup()->isActive()) { return null; }

		return [
			'TEXT' => Market\Config::getLang('ADMIN_TRADING_ACTIVATE_BUTTON'),
			'LINK' => $APPLICATION->GetCurPageParam(
				http_build_query(['sessid' => bitrix_sessid(), 'action' => static::ACTION_ACTIVATE]),
				[ 'sessid', 'action' ]
			),
		];
	}

	protected function getEditFormContextDeactivateItem()
	{
		global $APPLICATION;

		if (!$this->getSetup()->isActive()) { return null; }

		return [
			'TEXT' => Market\Config::getLang('ADMIN_TRADING_DEACTIVATE_BUTTON'),
			'LINK' => $APPLICATION->GetCurPageParam(
				http_build_query(['sessid' => bitrix_sessid(), 'action' => static::ACTION_DEACTIVATE]),
				[ 'sessid', 'action' ]
			),
		];
	}

	protected function getEditFormTabs()
	{
		$setup = $this->getSetup();
		$service = $setup->getService();

		return $service->getOptions()->getTabs();
	}

	protected function getEditFormFields()
	{
		$setup = $this->getSetup();
		$service = $setup->wakeupService();
		$environment = $setup->getEnvironment();
		$siteId = $setup->getSiteId();

		return $service->getOptions()->getFields($environment, $siteId);
	}

	protected function showCheckAnnouncement()
	{
		Market\Ui\Checker\Announcement::show();
	}

	public function getServiceCode()
	{
		$result = (string)$this->request->get('service');

		if ($result === '')
		{
			$message = Market\Config::getLang('ADMIN_TRADING_SERVICE_CODE_NOT_SET');
			throw new Main\ArgumentException($message, 'service');
		}

		if (!Market\Trading\Service\Manager::isExists($result))
		{
			$message = Market\Config::getLang('ADMIN_TRADING_SERVICE_CODE_INVALID', [ '#SERVICE#' => $result ]);
			throw new Main\SystemException($message);
		}

		return $result;
	}

	public function getBehaviorCode()
	{
		$result = $this->getRequestBehaviorCode();
		$enum = $this->getServiceBehaviorEnum();
		$behaviors = array_column($enum, 'ID');

		if ($result === null)
		{
			$result = reset($behaviors);
		}
		else if (!in_array($result, $behaviors, true))
		{
			$message = Market\Config::getLang('ADMIN_TRADING_BEHAVIOR_CODE_INVALID', [ '#BEHAVIOR#' => $result ]);
			throw new Main\SystemException($message);
		}

		return $result;
	}

	protected function getRequestBehaviorCode()
	{
		$result = $this->request->get('behaviour');

		return $result !== null ? (string)$result : null;
	}

	protected function isServiceDeprecated()
	{
		$serviceCode = $this->getServiceCode();

		return Market\Trading\Service\Migration::isDeprecated($serviceCode);
	}

	protected function getDeprecationUse()
	{
		$serviceCode = $this->getServiceCode();

		return Market\Trading\Service\Migration::getDeprecateUse($serviceCode);
	}

	protected function getDeprecationUseService()
	{
		$targetCode = $this->getDeprecationUse();

		return Market\Trading\Service\Manager::createProvider($targetCode);
	}

	protected function hasServiceMigrated()
	{
		$serviceCode = $this->getServiceCode();

		return Market\Trading\Service\Migration::hasMigrated($serviceCode);
	}

	protected function existsMigratedSetup()
	{
		$serviceCode = $this->getServiceCode();
		$migratedTypes = Market\Trading\Service\Migration::getMigrated($serviceCode);

		return Market\Trading\Setup\Facade::hasServiceSetup($migratedTypes);
	}

	protected function getMigratedSetupCollection()
	{
		$serviceCode = $this->getServiceCode();
		$migratedTypes = Market\Trading\Service\Migration::getMigrated($serviceCode);

		return Market\Trading\Setup\Collection::loadByService($migratedTypes);
	}

	protected function getSiteEnum()
	{
		$siteEntity = $this->getSetup()->getEnvironment()->getSite();
		$result = [];

		foreach ($siteEntity->getVariants() as $siteId)
		{
			$result[] = [
				'ID' => $siteId,
				'VALUE' => '[' . $siteId . '] ' . $siteEntity->getTitle($siteId),
			];
		}

		return $result;
	}

	protected function getServiceBehaviorEnum()
	{
		$serviceCode = $this->getServiceCode();
		$behaviors = Market\Trading\Service\Manager::getBehaviors($serviceCode);
		$result = [];

		foreach ($behaviors as $behavior)
		{
			$provider = Market\Trading\Service\Manager::createProvider($serviceCode, $behavior);

			if (
				$provider->isExperiment()
				&& !Market\Trading\Setup\Facade::hasServiceBehaviorSetup($serviceCode, $behavior)
			)
			{
				continue;
			}

			$result[] = [
				'ID' => $behavior,
				'VALUE' => $provider->getInfo()->getTitle('BEHAVIOR'),
			];
		}

		return $result;
	}

	/**
	 * @return Market\Trading\Setup\Model
	 */
	public function getSetup()
	{
		if ($this->setup === null)
		{
			$this->setup = $this->resolveSetup();
		}

		return $this->setup;
	}

	protected function resolveSetup()
	{
		$requestId = $this->getRequestedId();
		$requestedSiteId = $this->getRequestedSiteId();
		$serviceSetupCollection = $this->getServiceSetupCollection();

		if ($requestId !== null)
		{
			$result = Market\Trading\Setup\Model::loadById($requestId);
		}
		else if ($requestedSiteId !== null)
		{
			$result = $serviceSetupCollection->getBySite($requestedSiteId);

			if ($result === null)
			{
				$result = $this->initializeNewSetup($requestedSiteId);
			}

			if (!$this->checkExistsSiteId($result, $requestedSiteId))
			{
				$message = Market\Config::getLang('ADMIN_TRADING_REQUEST_SITE_ID_NOT_EXISTS', [ '#SITE_ID#' => $requestedSiteId ]);
				throw new Main\SystemException($message);
			}
		}
		else if (count($serviceSetupCollection) > 0)
		{
			$firstSetup = $this->chooseFirstSetup($serviceSetupCollection);
			$siteVariants = $this->getSetupSiteVariants($firstSetup);
			$activeSites = $this->getActiveSites($serviceSetupCollection);
			$existActiveSites = array_intersect($siteVariants, $activeSites);
			$siteId = null;

			if (!empty($existActiveSites))
			{
				$siteId = reset($existActiveSites);
			}
			else if (!empty($siteVariants))
			{
				$siteId = reset($siteVariants);
			}

			if ($siteId === null)
			{
				$message = Market\Config::getLang('ADMIN_TRADING_CANT_RESOLVE_SITE_ID');
				throw new Main\SystemException($message);
			}

			$result = $serviceSetupCollection->getBySite($siteId, [
				'TRADING_BEHAVIOR' => $firstSetup->getBehaviorCode(),
			]);

			if ($result === null)
			{
				$result = $serviceSetupCollection->getBySite($siteId);
			}

			if ($result === null)
			{
				$result = $this->initializeNewSetup();
				$result->setField('SITE_ID', $siteId);
			}
		}
		else
		{
			$result = $this->initializeNewSetup();
			$siteId = $this->resolveNewSetupSiteId($result);

			if ($siteId === null)
			{
				$message = Market\Config::getLang('ADMIN_TRADING_CANT_RESOLVE_SITE_ID');
				throw new Main\SystemException($message);
			}

			$result->setField('SITE_ID', $siteId);
		}

		return $result;
	}

	protected function chooseFirstSetup(Market\Trading\Setup\Collection $collection)
	{
		$result = $collection[0];

		foreach ($this->getServiceBehaviorEnum() as $option)
		{
			$setup = $collection->getByBehavior($option['ID']);

			if ($setup !== null)
			{
				$result = $setup;

				if ($setup->isActive()) { break; }
			}
		}

		return $result;
	}

	protected function initializeNewSetup($siteId = null)
	{
		return Market\Trading\Setup\Model::initialize([
			'ACTIVE' => Market\Trading\Setup\Table::BOOLEAN_N,
			'TRADING_SERVICE' => $this->getServiceCode(),
			'TRADING_BEHAVIOR' => $this->getBehaviorCode(),
			'SITE_ID' => $siteId,
		]);
	}

	protected function getSetupSiteVariants(Market\Trading\Setup\Model $setup)
	{
		return $setup->getEnvironment()->getSite()->getVariants();
	}

	protected function getActiveSites(Market\Trading\Setup\Collection $setupCollection)
	{
		$result = [];

		/** @var Market\Trading\Setup\Model $setup */
		foreach ($setupCollection as $setup)
		{
			if ($setup->isActive())
			{
				$result[] = $setup->getSiteId();
			}
		}

		return $result;
	}

	protected function checkExistsSiteId(Market\Trading\Setup\Model $setup, $siteId)
	{
		$siteVariants = $setup->getEnvironment()->getSite()->getVariants();

		return in_array($siteId, $siteVariants, true);
	}

	protected function resolveNewSetupSiteId(Market\Trading\Setup\Model $setup)
	{
		$siteVariants = $setup->getEnvironment()->getSite()->getVariants();
		$result = null;

		if (!empty($siteVariants))
		{
			$result = reset($siteVariants);
		}

		return $result;
	}

	protected function getRequestedId()
	{
		return $this->request->get('id');
	}

	protected function getRequestedSiteId()
	{
		return $this->request->get('site');
	}

	protected function getServiceSetupCollection()
	{
		$serviceCode = $this->getServiceCode();
		$behaviorCode = $this->getRequestBehaviorCode();

		return Market\Trading\Setup\Collection::loadByService($serviceCode, $behaviorCode);
	}
}