<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

define('BX_SESSION_ID_CHANGE', false);
define('NOT_CHECK_FILE_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('yandex.market'))
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_MODULE_NOT_INSTALLED')
	]);

	return;
}

$request = Main\Context::getCurrent()->getRequest();
$requestService = trim($request->get('service'));
$actionMessage = '';

// action process

$requestAction = $request->get('action');

if ($requestAction)
{
	$response = [
		'status' => 'error',
		'message' => null
	];

	try
	{
		if (!check_bitrix_sessid() || !$USER->IsAuthorized())
		{
			throw new Main\SystemException(Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_SESSION_EXPIRED'));
		}

		if (!Market\Ui\Access::isProcessExportAllowed())
		{
			throw new Main\SystemException(Market\Config::getLang('ACCESS_DENIED'));
		}

		session_write_close(); // release session

		Main\Application::getInstance()->getExceptionHandler()->setDebugMode(true);
		ini_set('display_errors', 1);

		/** @var \Yandex\Market\Export\Setup\Model $setup */
		$setupId = (int)$request->getPost('SETUP_ID');
		$setup = Market\Export\Setup\Model::loadById($setupId);
		$initTimestamp = $request->getPost('INIT_TIME');
		$initTime = (
			$initTimestamp !== null
				? Main\Type\DateTime::createFromTimestamp($initTimestamp)
				: new Main\Type\DateTime()
		);
		$timeLimit = (int)$request->getPost('TIME_LIMIT') ?: 30;
		$timeSleep = (int)$request->getPost('TIME_SLEEP') ?: 3;

		$processor = new Market\Export\Run\Processor($setup, [
			'step' => $request->getPost('STEP'),
			'stepOffset' => $request->getPost('STEP_OFFSET'),
			'progressCount' => true,
			'timeLimit' => $timeLimit,
			'initTime' => $initTime,
			'usePublic' => false
		]);

		switch ($requestAction)
		{
			case 'run':

				Market\Export\Run\Admin::progress($setupId);

				if ($request->getPost('STEP') === null) // is first request
				{
					if ($setup->hasFullRefresh())
					{
						$setup->handleRefresh(false);
					}

					Market\Watcher\Track\StampFacade::shift(Market\Glossary::SERVICE_EXPORT, $setupId);

					Market\Export\Run\Admin::setTimeLimit($timeLimit);
					Market\Export\Run\Admin::setTimeSleep($timeSleep);
				}

				$processResult = $processor->run();

				if ($processResult->isFinished())
				{
					Market\Environment::stamp();
					Market\Export\Run\Admin::release($setupId);

					$setup->updateListener();

					$response['status'] = 'ok';
					$response['message'] = '<div class="b-admin-message-list compensate--spacing message-width--auto">';

					// publish note

					$response['message'] .= BeginNote();
					$response['message'] .=
						$setup->getFormat()->getPublishNote()
						?: Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_SUCCESS_PUBLISH');
					$response['message'] .= EndNote();

					// export result

					$adminMessage = new CAdminMessage(array(
						'MESSAGE' => Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_SUCCESS_TITLE'),
						'DETAILS' => sprintf(
							'<div class="b-grid gutter--centi">
								<div class="b-grid__item">
									<a class="b-link-complex" href="%1$s" download>
										<svg class="b-icon size--small b-link-complex__icon" width="10" height="10">
											<use xlink:href="/bitrix/images/yandex.market/yml-actions.svg#download"></use>
										</svg>
										<span class="b-link-complex__target">%2$s</span>
									</a> 
								</div>
								<div class="b-grid__item">
									<a class="b-link-complex" href="%1$s" target="_blank">
										<svg class="b-icon size--small b-link-complex__icon" width="10" height="10">
											<use xlink:href="/bitrix/images/yandex.market/yml-actions.svg#launch"></use>
										</svg>
										<span class="b-link-complex__target">%3$s</span>
									</a>
								</div>
							</div>',
							$setup->getFileRelativePath(),
							Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_SUCCESS_DOWNLOAD'),
							Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_SUCCESS_OPEN')
						),
						'TYPE' => 'OK',
						'HTML' => true
					));

					$response['message'] .= $adminMessage->Show();
					$response['message'] .= '</div>';

					// copy url

					$response['message'] .= '<div class="b-admin-text-message spacing--1x1">';
					$response['message'] .= '<input type="text" value="' . htmlspecialcharsbx($setup->getFileUrl()) . '" size="50" /> ';
					$response['message'] .= '<button class="adm-btn js-plugin-click" type="button" data-plugin="Ui.Input.CopyClipboard">' . Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_COPY_LINK') . '</button>';
					$response['message'] .= '</div>';

					// statistic

					$queryStatistic = Market\Export\Run\Storage\OfferTable::getList([
						'filter' => [ '=SETUP_ID' => $setup->getId() ],
						'group' => [ 'STATUS' ],
						'select' => [ 'STATUS', 'CNT' ],
						'runtime' => [
							new Main\Entity\ExpressionField('CNT', 'COUNT(*)')
						],
					]);

					$statistic = $queryStatistic->fetchAll();
					$statistic = array_column($statistic, 'CNT', 'STATUS');
					$usedStatuses = [
						Market\Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS,
						Market\Export\Run\Steps\Base::STORAGE_STATUS_DUPLICATE,
						Market\Export\Run\Steps\Base::STORAGE_STATUS_FAIL,
					];
					$hasErrors = false;

					$response['message'] .= '<div class="b-admin-text-message spacing--1x1">';

					foreach ($usedStatuses as $statusId)
					{
						$count = isset($statistic[$statusId]) ? (int)$statistic[$statusId] : 0;

						if ($count === 0 && $statusId !== Market\Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS) { continue; }

						if ($count > 0 && $statusId !== Market\Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS)
						{
							$hasErrors = true;
						}

						$response['message'] .= '<div class="spacing--1x4">';
						$response['message'] .= Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_SUCCESS_STAT_' . $statusId, [
							'#COUNT#' => $count,
						]);
						$response['message'] .= '</div>';
					}

					if ($hasErrors)
					{
						$logQuery = [
							'lang' => LANGUAGE_ID,
							'set_filter' => 'Y',
							'apply_filter' => 'Y',
							'find_setup' => $setupId,
						];

						if ($requestService !== '')
						{
							$logQuery['service'] = $requestService;
						}

						$logUrl = 'yamarket_log.php?' . http_build_query($logQuery);

						$response['message'] .= '<div class="spacing--1x4">';
						$response['message'] .= Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_SUCCESS_LOG', [
							'#URL#' => htmlspecialcharsbx($logUrl),
						]);
						$response['message'] .= '</div>';
					}

					$response['message'] .= '</div>';
				}
				else if ($processResult->isSuccess())
				{
					$processStepName = $processResult->getStep();
					$readyCountMessage = '';
					$stepList = Market\Export\Run\Manager::getSteps();
					$isFoundCurrentStep = false;

					foreach ($stepList as $stepName)
					{
						$isCurrentStep = ($stepName === $processStepName || ($processStepName === null && !$isFoundCurrentStep));
						$stepText = null;
						$stepTitle = Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_PROGRESS_STEP', [
							'#STEP#' => Market\Export\Run\Manager::getStepTitle($stepName)
						]);

						if ($isCurrentStep)
						{
							$isFoundCurrentStep = true;
							$readyCount = $processResult->getStepReadyCount();
							$stepText = $stepTitle;

							if ($readyCount !== null)
							{
								$stepText .= Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_PROGRESS_READY_COUNT', [
									'#COUNT#' => (int)$readyCount,
									'#LABEL#' => Market\Utils::sklon($readyCount, [
										Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_PROGRESS_READY_COUNT_LABEL_1'),
										Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_PROGRESS_READY_COUNT_LABEL_2'),
										Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_PROGRESS_READY_COUNT_LABEL_5'),
									])
								]);
							}
							else
							{
								$stepText .= '...';
							}
						}
						else if (!$isFoundCurrentStep) // is ready
						{
							$stepText = '<b>' . $stepTitle . '</b>';
						}

						if ($stepText !== null)
						{
							$readyCountMessage .= '<p>' . $stepText . '</p>';
						}
					}

					$adminMessage = new CAdminMessage(array(
						'TYPE' => 'PROGRESS',
						'MESSAGE' => Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_PROGRESS_TITLE'),
						'DETAILS' => $readyCountMessage,
						'HTML' => true,
					));

					$response['status'] = 'progress';
					$response['message'] = $adminMessage->Show();
					$response['state'] = [
						'STEP' => $processResult->getStep(),
						'STEP_OFFSET' => $processResult->getStepOffset(),
						'sessid' => bitrix_sessid(),
						'INIT_TIME' => $initTime->getTimestamp()
					];
				}
				else
				{
					Market\Export\Run\Admin::release($setupId);

					$errorMessage = $processResult->hasErrors()
						? implode('<br />', $processResult->getErrorMessages())
						: Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_ERROR_UNDEFINED');

					$adminMessage = new CAdminMessage(array(
						'TYPE' => 'ERROR',
						'MESSAGE' => Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_ERROR_TITLE'),
						'DETAILS' => $errorMessage,
						'HTML' => true,
					));
					
					$response['status'] = 'error';
					$response['message'] = $adminMessage->Show();
				}

			break;

			case 'stop':

				Market\Export\Run\Admin::release($setupId);

				$processor->clear(true);

				if ($setup->hasFullRefresh())
				{
					$setup->handleRefresh(false);
				}

				if ($setup->isAutoUpdate())
				{
					$setup->handleChanges(false);
				}

				$response['status'] = 'ok';

			break;

			default:
				throw new Main\SystemException(
					Market\Config::getLang('ADMIN_SETUP_RUN_ACTION_NOT_FOUND')
				);
			break;
		}
	}
	catch (Main\SystemException $exception)
	{
		$adminMessage = new CAdminMessage(array(
			'TYPE' => 'ERROR',
			'MESSAGE' => $exception->getMessage()
		));

		$response['status'] = 'error';
		$response['message'] = $adminMessage->Show();

		if (Market\Migration\Controller::canRestore($exception))
		{
			$response['message'] .=
				'<a class="adm-btn" href="yamarket_migration.php?lang=' . LANGUAGE_ID . '">'
				. Market\Config::getLang('ADMIN_SETUP_RUN_GO_MIGRATION')
				. '</a>'
				. '<br /><br />';
		}
	}
	catch (\Exception $exception)
	{
		$adminMessage = new CAdminMessage(array(
			'TYPE' => 'ERROR',
			'MESSAGE' => Market\Config::getLang('ADMIN_SETUP_RUN_FATAL_EXCEPTION'),
			'DETAILS' =>
				$exception->getMessage()
				. '<br />'
				. sprintf(
					'<textarea cols="90" rows="8">%s</textarea>',
					$exception->getTraceAsString()
				),
			'HTML' => true,
		));

		$response['status'] = 'error';
		$response['message'] = $adminMessage->Show();
	}
	catch (\Throwable $exception)
	{
		$adminMessage = new CAdminMessage(array(
			'TYPE' => 'ERROR',
			'MESSAGE' => Market\Config::getLang('ADMIN_SETUP_RUN_FATAL_EXCEPTION'),
			'DETAILS' =>
				$exception->getMessage()
				. '<br />'
				. sprintf(
					'<textarea cols="90" rows="8">%s</textarea>',
					$exception->getTraceAsString()
				),
			'HTML' => true,
		));

		$response['status'] = 'error';
		$response['message'] = $adminMessage->Show();
	}

	if ($request->isAjaxRequest())
	{
		Market\Utils\HttpResponse::sendJson($response);
	}
	else
	{
		$actionMessage = $response['message'];
	}
}

// admin page

if (!$USER->IsAuthorized())
{
	$APPLICATION->AuthForm('');
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

if (!Market\Ui\Access::isProcessExportAllowed())
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ACCESS_DENIED')
	]);
}

// load form data

$requestSetup = (int)$request->get('id');
$setupList = [];
$setupFilter = [];
$uiService = (
	Market\Ui\Service\Manager::isExists($requestService)
		? Market\Ui\Service\Manager::getInstance($requestService)
		: Market\Ui\Service\Manager::getCommonInstance()
);
$exportServices = $uiService->getExportServices();

if (!$uiService->isInverted())
{
	$setupFilter['=EXPORT_SERVICE'] = $exportServices;
}
else if (!empty($exportServices))
{
	$setupFilter['!=EXPORT_SERVICE'] = $exportServices;
}

$querySetup = Market\Export\Setup\Table::getList([
	'filter' => $setupFilter,
	'select' => [ 'ID', 'NAME' ]
]);

while ($setup = $querySetup->fetch())
{
	$setupList[] = $setup;
}

if (empty($setupList))
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Market\Config::getLang('ADMIN_SETUP_RUN_SETUP_LIST_EMPTY')
	]);

	return;
}

// form display

$APPLICATION->SetTitle(Market\Config::getLang('ADMIN_SETUP_RUN_TITLE'));

Market\Ui\Library::load('jquery');

Market\Ui\Assets::loadPlugin('base', 'css');

Market\Ui\Assets::loadPluginCore();
Market\Ui\Assets::loadFieldsCore();
Market\Ui\Assets::loadPlugins([
	'Ui.Admin.ExportForm',
	'Ui.Input.CopyClipboard',
]);

Market\Metrika::reachGoal('generate_YML');

$tabs = [
	[ 'DIV' => 'common', 'TAB' => Market\Config::getLang('ADMIN_SETUP_RUN_TAB_COMMON') ]
];

$tabControl = new CAdminTabControl('YANDEX_MARKET_ADMIN_SETUP_RUN', $tabs, true, true);
$formQuery = [
	'lang' => LANGUAGE_ID,
];

if ($requestService !== '')
{
	$formQuery['service'] = $requestService;
}

$formUrl = $APPLICATION->GetCurPage() . '?' . http_build_query($formQuery);

?>
<form class="js-plugin" action="<?= $formUrl; ?>" method="post" data-plugin="Ui.Admin.ExportForm">
	<div class="js-export-form__message">
		<?= $actionMessage; ?>
	</div>
	<div class="b-admin-text-message is--hidden js-export-form__timer-holder">
		<?= Market\Config::getLang('ADMIN_SETUP_RUN_TIMER_LABEL'); ?>:
		<span class="js-export-form__timer">00:00</span>
	</div>
	<?
	$tabControl->Begin();

	echo bitrix_sessid_post();

	// common tab

	$tabControl->BeginNextTab([ 'showTitle' => false ]);

	?>
	<tr>
		<td width="40%" align="right"><?= Market\Config::getLang('ADMIN_SETUP_RUN_FIELD_SETUP_ID'); ?></td>
		<td width="60%">
			<select name="SETUP_ID">
				<?
				foreach ($setupList as $setup)
				{
					?>
					<option value="<?= $setup['ID']; ?>" <?= (int)$setup['ID'] === $requestSetup ? 'selected' : ''; ?>>[<?= $setup['ID']; ?>] <?= Market\Utils::htmlEscape($setup['NAME']); ?></option>
					<?
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td width="40%" align="right">
			<span class="b-icon icon--question indent--right b-tag-tooltip--holder">
				<span class="b-tag-tooltip--content"><?= Market\Config::getLang('ADMIN_SETUP_RUN_FIELD_TIME_LIMIT_HELP'); ?></span>
			</span><?
			echo Market\Config::getLang('ADMIN_SETUP_RUN_FIELD_TIME_LIMIT');
			?>
		</td>
		<td>
			<input type="text" name="TIME_LIMIT" value="<?= Market\Export\Run\Admin::getTimeLimit(); ?>" size="2" />
			<?= Market\Config::getLang('ADMIN_SETUP_RUN_FIELD_TIME_LIMIT_UNIT'); ?><?
			?><?= Market\Config::getLang('ADMIN_SETUP_RUN_FIELD_TIME_LIMIT_SLEEP'); ?>
			<input type="text" name="TIME_SLEEP" value="<?= Market\Export\Run\Admin::getTimeSleep(); ?>" size="2" />
			<?= Market\Config::getLang('ADMIN_SETUP_RUN_FIELD_TIME_LIMIT_UNIT'); ?>
		</td>
	</tr>
	<?

	// buttons

	$tabControl->Buttons();

	?>
	<input
		type="button"
		class="adm-btn adm-btn-save js-export-form__run-button"
		value="<?= Market\Config::getLang('ADMIN_SETUP_RUN_BUTTON_START'); ?>"
		<?= !Market\Ui\Access::isProcessExportAllowed() ? 'disabled' : ''; ?>
	/>
	<input type="button" class="adm-btn js-export-form__stop-button" value="<?= Market\Config::getLang('ADMIN_SETUP_RUN_BUTTON_STOP'); ?>" disabled />
	<?

	$tabControl->End();
	?>
</form>
<?
$jsLang = [
	'YANDEX_MARKET_INPUT_COPY_CLIPBOARD_SUCCESS' => Market\Config::getLang('ADMIN_SETUP_RUN_CLIPBOARD_SUCCESS'),
	'YANDEX_MARKET_INPUT_COPY_CLIPBOARD_FAIL' => Market\Config::getLang('ADMIN_SETUP_RUN_CLIPBOARD_FAIL'),
	'YANDEX_MARKET_EXPORT_FORM_QUERY_ERROR_TITLE' => Market\Config::getLang('ADMIN_SETUP_RUN_QUERY_ERROR_TITLE'),
	'YANDEX_MARKET_EXPORT_FORM_QUERY_ERROR_TEXT' => Market\Config::getLang('ADMIN_SETUP_RUN_QUERY_ERROR_TEXT'),
];
?>
<script>
	BX.message(<?= Market\Utils::jsonEncode($jsLang, JSON_UNESCAPED_UNICODE); ?>);
</script>
<?

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';