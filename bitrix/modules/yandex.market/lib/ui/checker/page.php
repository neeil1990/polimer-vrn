<?php

namespace Yandex\Market\Ui\Checker;

use Bitrix\Main;
use Yandex\Market;

class Page extends Market\Ui\Reference\Page
{
	use Market\Reference\Concerns\HasLang;

	const ACTION_TEST = 'test';
	const ACTION_FIX = 'fix';
	const ACTION_COMMIT = 'commit';

	protected $tabControl;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function hasRequest()
	{
		return $this->request->isPost();
	}

	public function handleRequest()
	{
		try
		{
			$this->checkSession();
			$this->checkWriteAccess();

			$this->processRequest();
		}
		catch (Main\SystemException $exception)
		{
			$this->sendAjaxResponse([
				'error' => $exception->getMessage(),
			]);
		}
	}

	public function processRequest()
	{
		$action = (string)$this->request->getPost('action');

		switch ($action)
		{
			case static::ACTION_TEST:
				$response = $this->processTestAction();
			break;

			case static::ACTION_FIX:
				$response = $this->processFixAction();
			break;

			case static::ACTION_COMMIT:
				$response = $this->processCommitAction();
			break;

			default:
				throw new Main\ArgumentException(sprintf('unknown action %s', $action));
			break;
		}

		$this->sendAjaxResponse($response);
	}

	protected function processTestAction()
	{
		$offset = (int)$this->request->getPost('offset');
		$test = $this->getTest($offset);
		$group = $this->getGroup($offset);
		$commonResponse = [
			'group' => $this->getGroupTitle($group),
		];
		$testResponse = $this->makeTest($test);

		return $commonResponse + $testResponse;
	}

	protected function processFixAction()
	{
		$offset = (int)$this->request->getPost('offset');
		$test = $this->getTest($offset);

		if (!($test instanceof Reference\FixableTest))
		{
			throw new Main\SystemException('test not fixable');
		}

		$test->fix();

		return $this->makeTest($test);
	}

	protected function processCommitAction()
	{
		Notify::closeError();

		return [
			'status' => 'success',
		];
	}

	protected function makeTest(Reference\AbstractTest $test)
	{
		$testResult = $this->callTest($test);
		$response = [
			'title' => $test->getTitle(),
			'description' => $test->getDescription(),
		];

		if ($testResult->hasErrors())
		{
			$response = array_merge(
				$response,
				[
					'status' => 'error',
					'fixable' => $test instanceof Reference\FixableTest,
				],
			    $this->collectTestErrorResponse($testResult->getErrors())
			);
		}
		else if ($testResult->hasWarnings())
		{
			$response = array_merge(
				$response,
				[
					'status' => 'warning',
					'fixable' => $test instanceof Reference\FixableTest,
				],
				$this->collectTestErrorResponse($testResult->getWarnings())
			);
		}
		else
		{
			$response = array_merge($response, [
				'status' => 'success',
				'message' => static::getLang('CHECKER_TEST_SUCCESS'),
			]);
		}

		return $response;
	}

	protected function callTest(Reference\AbstractTest $test)
	{
		try
		{
			$history = new History();

			$result = $test->test();

			$history->register($test, $result);
			$history->flush();
		}
		catch (Main\SystemException $exception)
		{
			$result = new Market\Result\Base();
			$error = new Market\Error\Base($exception->getMessage());

			$result->addError($error);
		}

		return $result;
	}

	protected function collectTestErrorResponse($errors)
	{
		$groups = [];
		$messages = [];
		$descriptions = [];

		/** @var Market\Error\Base $error */
		foreach ($errors as $error)
		{
			$message = (string)($error->getMessage() ?: $error->getCode());
			$description = '';
			$group = '';

			if ($error instanceof Reference\Error)
			{
				$description = (string)$error->getDescription();
				$group = (string)$error->getGroup();
				$groupUrl = (string)$error->getGroupUrl();
				$count = (int)$error->getCount();

				if ($count > 1)
				{
					$message .= ' (' . $count . ')';
				}
			}

			if ($group !== '')
			{
				if (!isset($groups[$group]))
				{
					$groups[$group] = [
						'NAME' => $group,
						'URL' => $groupUrl,
						'MESSAGES' => [],
					];
				}

				$groups[$group]['MESSAGES'][] = $message;
			}
			else
			{
				$messages[] = $message;
			}

			if ($description !== '')
			{
				$descriptions[] = $description;
			}
		}

		return array_filter([
			'message' =>
				implode('<br />', $messages)
				. $this->combineTestErrorGroups($groups),
			'description' =>
				implode('<br />', array_unique($descriptions)),
		]);
	}

	protected function combineTestErrorGroups($groups)
	{
		$result = [];

		foreach ($groups as $group)
		{
			$message = sprintf('<strong>%s</strong><br />', $group['NAME']);
			$message .= implode('<br />', $group['MESSAGES']);

			if ($group['URL'])
			{
				$message .= sprintf(
					'<br /><a href="%s" target="_blank">%s</a>',
					$group['URL'],
					static::getLang('CHECKER_TEST_ERROR_MORE')
				);
			}

			$result[] = $message;
		}

		return implode('<br /><br />', $result);
	}

	protected function sendAjaxResponse($data)
	{
		Market\Utils\HttpResponse::sendJson($data);
	}

	public function show()
	{
		$this->loadAssets();

		$contextMenu = $this->createContextMenu();
		$tabControl = $this->createTabControl();

		$contextMenu->Show();

		$tabControl->Begin();
		$tabControl->BeginNextTab([ 'showTitle' => false ]);

		$this->showDescription();
		$this->showButtons();
		$this->showProgress();
		$this->showResultHolder();

		$tabControl->End();

		$this->showCheckerPlugin();
	}

	protected function loadAssets()
	{
		static::loadMessages();

		Market\Ui\Library::load('jquery');

		Market\Ui\Assets::loadPluginCore();
		Market\Ui\Assets::loadPlugin('base', 'css');
		Market\Ui\Assets::loadPlugin('admin', 'css');
		Market\Ui\Assets::loadPlugin('grain', 'css');

		Market\Ui\Assets::loadPlugin('Ui.Checker');
		Market\Ui\Assets::loadPlugin('Ui.Checker', 'css');

		Market\Ui\Assets::loadMessages([
			'CHECKER_TEST_ERROR',
			'CHECKER_FIX',
			'CHECKER_FIX_ERROR',
			'CHECKER_DESCRIPTION_OPEN',
		]);
	}

	protected function createContextMenu()
	{
		$items = $this->getContextMenuItems();

		return new \CAdminContextMenu($items);
	}

	protected function getContextMenuItems()
	{
		return  [
			[
				'TEXT' => static::getLang('CHECKER_MENU_MIGRATION'),
				'LINK' => Market\Ui\Admin\Path::getModuleUrl('migration', [
					'lang' => LANGUAGE_ID,
				]),
			]
		];
	}

	protected function createTabControl()
	{
		$tabs = $this->getTabs();

		return new \CAdminTabControl('YANDEX_MARKET_CHECKER', $tabs, true, true);
	}

	protected function getTabs()
	{
		return [
			[
				'DIV' => 'main',
				'TAB' => static::getLang('CHECKER_TAB'),
			],
		];
	}

	protected function showDescription()
	{
		echo sprintf(
			'<tr><td colspan="2">%s</td></tr>',
			static::getLang('CHECKER_DESCRIPTION')
		);
	}

	protected function showButtons()
	{
		echo '<tr><td colspan="2"><br>';
		echo sprintf(
			'<input type="button" value="%s" class="adm-btn-green js-checker__start">',
			static::getLang('CHECKER_START')
		);
		echo sprintf(
			'<input type="button" value="%s" disabled class="js-checker__stop">',
			static::getLang('CHECKER_STOP')
		);
		echo '</td></tr>';
	}

	protected function showProgress()
	{
		echo '<tr><td colspan="2">';
		echo '<div class="js-checker__progress" style="visibility:hidden;padding-top:4px;" width="100%">';
		echo '<div class="js-checker__status" style="font-weight:bold;font-size:1.2em"></div>';
		echo '<table border="0" cellspacing="0" cellpadding="2" width="100%">
				<tr>
					<td height="20">
						<div style="border:1px solid #B9CBDF">
							<div class="js-checker__progress-indicator" style="height:20px; width:0; background-color:#B9CBDF;transition: width 0.5s;"></div>
						</div>
					</td>
					<td width="30">&nbsp;<span class="js-checker__progress-percent" style="font-size:1.4em">0%</span></td>
				</tr>
			</table>';
		echo '</div>';
		echo '</td></tr>';
	}

	protected function showResultHolder()
	{
		echo '<tr><td colspan="2" class="js-checker__result-wrapper" style="padding-top:10px"></td></tr>';
	}

	protected function showCheckerPlugin()
	{
		echo Market\Ui\Assets::initPlugin('Ui.Checker', '#main', [
			'url' => $this->getTestUrl(),
			'total' => $this->getTestCount(),
			'autostart' => ($this->request->get('autostart') === 'Y'),
		]);
	}

	protected function getTestUrl()
	{
		global $APPLICATION;

		return $APPLICATION->GetCurPage() . '?' . http_build_query([
			'lang' => LANGUAGE_ID,
		]);
	}

	protected function hasTest($offset)
	{
		return $this->searchTest($offset) !== null;
	}

	/**
	 * @param int $offset
	 *
	 * @return Reference\AbstractTest
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	protected function getTest($offset)
	{
		$test = $this->searchTest($offset);

		if ($test === null)
		{
			throw new Main\ArgumentException(sprintf(
				'test with offset %s not found',
				$offset
			));
		}

		return Factory::make($test);
	}

	protected function searchTest($offset)
	{
		$result = null;

		foreach ($this->getTests() as $group => $tests)
		{
			$testCount = count($tests);

			if ($testCount <= $offset)
			{
				$offset -= $testCount;
			}
			else
			{
				$result = $tests[$offset];
				break;
			}
		}

		return $result;
	}

	protected function getGroup($offset)
	{
		$result = null;

		foreach ($this->getTests() as $group => $tests)
		{
			$testCount = count($tests);

			if ($testCount <= $offset)
			{
				$offset -= $testCount;
			}
			else
			{
				$result = $group;
				break;
			}
		}

		return $result;
	}

	protected function getGroupTitle($group)
	{
		$groupUpper = Market\Data\TextString::toUpper($group);

		return static::getLang('CHECKER_GROUP_' . $groupUpper, null, $group);
	}

	protected function getTests()
	{
		return [
			'EXPORT' => [
				Export\SetupStatus::class,
				Export\CollectionStatus::class,
				Export\PromoStatus::class,
				Export\AgentActivity::class,
				Export\AgentLastExecution::class,
				Export\AgentLog::class,
			],
			'TRADING' => [
				Trading\IncomingRequest::class,
				Trading\OutgoingRequest::class,
				Trading\EventLog::class,
                Trading\AgentActivity::class,
                Trading\AgentLastExecution::class,
			],
		];
	}

	protected function getTestCount()
	{
		$result = 0;

		foreach ($this->getTests() as $tests)
		{
			$result += count($tests);
		}

		return $result;
	}
}