<?php
namespace Yandex\Market\Ui\Reference;

use Bitrix\Main;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Ui;
use Yandex\Market\Utils;
use Yandex\Market\Export;
use Yandex\Market\Migration;

abstract class RunForm extends Form
{
	use Concerns\HasMessage;

	protected $actionMessage = '';

	public function hasRequest()
	{
		return $this->request->getPost('action') !== null;
	}

	public function processRequest()
	{
		try
		{
			$this->checkSession();
			$this->checkWriteAccess();

			$action = $this->request->getPost('action');

			if ($action === 'run')
			{
				session_write_close(); // release session
				$response = $this->processRun();
			}
			else if ($action === 'stop')
			{
				$response = $this->processStop();
			}
			else
			{
				throw new Main\SystemException(self::getMessage('ACTION_NOT_FOUND'));
			}

			$this->sendResponse($response);
		}
		catch (Main\SystemException $exception)
		{
			$adminMessage = new \CAdminMessage(array(
				'TYPE' => 'ERROR',
				'MESSAGE' => $exception->getMessage(),
			));

			$response = [
				'status' => 'error',
				'message' => $adminMessage->Show(),
			];

			if (Migration\Controller::canRestore($exception))
			{
				/** @noinspection HtmlUnknownTarget */
				$response['message'] .= sprintf(
					'<a class="adm-btn" href="%s">%s</a><br /><br />',
					Ui\Admin\Path::getModuleUrl('migration'),
					self::getMessage('GO_MIGRATION')
				);
			}

			$this->sendResponse($response);
		}
	}

	protected function sendResponse(array $response)
	{
		if ($this->request->isAjaxRequest())
		{
			Utils\HttpResponse::sendJson($response);
		}
		else
		{
			$this->actionMessage = $response['message'];
		}
	}

	abstract protected function processRun();

	abstract protected function processStop();

	public function show()
	{
		$this->loadAssets();

		$contextMenu = $this->createContextMenu();
		$tabControl = $this->createTabControl();

		$contextMenu->Show();

		$this->showFormProlog();
		$this->showMessage();
		$this->showTimer();

		$tabControl->Begin();
		$tabControl->BeginNextTab([ 'showTitle' => false ]);

		$this->showFormBody();

		$tabControl->Buttons();
		$this->showButtons();
		$tabControl->End();

		$this->showFormEpilog();
		$this->showJsMessages();
	}

	protected function loadAssets()
	{
		Ui\Library::load('jquery');

		Ui\Assets::loadPlugin('base', 'css');

		Ui\Assets::loadPluginCore();
		Ui\Assets::loadFieldsCore();
		Ui\Assets::loadPlugins([
			'Ui.Admin.ExportForm',
			'Ui.Input.CopyClipboard',
		]);
	}

	protected function createContextMenu()
	{
		$items = $this->getContextMenuItems();

		return new \CAdminContextMenu($items);
	}

	protected function getContextMenuItems()
	{
		return  [];
	}

	protected function createTabControl()
	{
		$tabs = $this->getTabs();

		return new \CAdminTabControl($this->getTabControlId(), $tabs, true, true);
	}

	abstract protected function getTabControlId();

	protected function getTabs()
	{
		return [
			[
				'DIV' => 'main',
				'TAB' => self::getMessage('COMMON_TAB'),
			],
		];
	}

	protected function showFormProlog()
	{
		$postUrl = $this->getFormActionUri();

		echo '<form class="js-plugin" method="post" action="' . htmlspecialcharsbx($postUrl) . '" data-plugin="Ui.Admin.ExportForm">';
		echo bitrix_sessid_post();
	}

	protected function showMessage()
	{
		echo sprintf('<div class="js-export-form__message">%s</div>', $this->actionMessage);
	}

	protected function showTimer()
	{
		echo sprintf(<<<EOL
			<div class="b-admin-text-message is--hidden js-export-form__timer-holder">
				%s: <span class="js-export-form__timer">00:00</span>
			</div>
EOL
			,
			self::getMessage('TIMER_LABEL')
		);
	}

	abstract protected function showFormBody();

	protected function showTimeField()
	{
		?>
		<tr>
			<td width="40%" align="right" valign="middle">
				<span class="b-icon icon--question indent--right b-tag-tooltip--holder">
					<span class="b-tag-tooltip--content"><?= self::getMessage('FIELD_TIME_LIMIT_HELP') ?></span>
				</span><?php
				echo self::getMessage('FIELD_TIME_LIMIT');
				?>
			</td>
			<td>
				<input type="text" name="TIME_LIMIT" value="<?= Export\Run\Admin::getTimeLimit() ?>" size="2" />
				<?= self::getMessage('FIELD_TIME_LIMIT_UNIT') ?><?php
				?><?= self::getMessage('FIELD_TIME_LIMIT_SLEEP') ?>
				<input type="text" name="TIME_SLEEP" value="<?= Export\Run\Admin::getTimeSleep() ?>" size="2" />
				<?= self::getMessage('FIELD_TIME_LIMIT_UNIT') ?>
			</td>
		</tr>
		<?php
	}

	protected function showButtons()
	{
		echo sprintf('<input type="button" class="adm-btn adm-btn-save js-export-form__run-button" value="%s" />', self::getMessage('START'));
		echo sprintf('<input type="button" class="adm-btn js-export-form__stop-button" value="%s" disabled />', self::getMessage('STOP'));
	}

	/** @noinspection BadExpressionStatementJS */
	protected function showJsMessages()
	{
		echo sprintf(
			'<script>BX.message(%s)</script>',
			Utils::jsonEncode([
				'YANDEX_MARKET_EXPORT_FORM_QUERY_ERROR_TITLE' => self::getMessage('QUERY_ERROR_TITLE'),
				'YANDEX_MARKET_EXPORT_FORM_QUERY_ERROR_TEXT' => self::getMessage('QUERY_ERROR_TEXT'),
			], JSON_UNESCAPED_UNICODE)
		);
	}
}