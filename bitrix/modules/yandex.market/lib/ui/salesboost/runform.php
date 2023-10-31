<?php
/** @noinspection DuplicatedCode */
namespace Yandex\Market\Ui\SalesBoost;

use Bitrix\Main;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Result;
use Yandex\Market\Ui;
use Yandex\Market\Data\Type;
use Yandex\Market\SalesBoost;
use Yandex\Market\Utils;

class RunForm extends Ui\Reference\RunForm
{
	use Concerns\HasMessage;

	protected $activeBoostVariants = [];
	protected $notifyGroup = [
		'READY' => [],
		'INACTIVE' => [],
		'IN_FUTURE' => [],
		'IN_PAST' => [],
		'DELETE' => [],
	];

	public function setTitle()
	{
		global $APPLICATION;

		$APPLICATION->SetTitle(self::getMessage('TITLE'));
	}

	protected function getWriteRights()
	{
		return Ui\Access::RIGHTS_PROCESS_TRADING;
	}

	protected function processRun()
	{
		list($boostIds, $inactiveIds, $initTime, $step, $offset) = $this->bootRunContext();

		$processor = $this->createProcessor($boostIds, $inactiveIds, $initTime, $step, $offset);
		$processResult = $processor->run();

		if (!$processResult->isSuccess())
		{
			return $this->runErrorResponse($processResult);
		}

		if (!$processResult->isFinished())
		{
			return $this->runProgressResponse($processor, $processResult, $boostIds, $inactiveIds, $initTime);
		}

		$this->updateListener($boostIds);

		return $this->runFinishMessage();
	}

	protected function bootRunContext()
	{
		if ($this->request->getPost('INIT_TIME') !== null)
		{
			$initTime = Main\Type\DateTime::createFromTimestamp($this->request->getPost('INIT_TIME'));
			$step = $this->request->getPost('STEP');
			$offset = $this->request->getPost('STEP_OFFSET');
			$boostIds = explode(',', (string)$this->request->getPost('BOOST_ID'));
			$inactiveIds = explode(',', (string)$this->request->getPost('INACTIVE_ID'));
		}
		else
		{
			$initTime = new Main\Type\DateTime();
			$step = null;
			$offset = null;

			$boostIds = (array)$this->request->getPost('BOOST_ID');
			$inactiveIds = $this->inactiveBoostsIds();
		}

		Main\Type\Collection::normalizeArrayValuesByInt($boostIds);
		Main\Type\Collection::normalizeArrayValuesByInt($inactiveIds);

		return [$boostIds, $inactiveIds, $initTime, $step, $offset];
	}

	protected function inactiveBoostsIds()
	{
		/** @var SalesBoost\Run\Storage\CollectorTable[]|SalesBoost\Run\Storage\SubmitterTable[] $tables */
		$result = [];
		$activeBoostIds = $this->activeBoostIds();
		$commonFilter = !empty($activeBoostIds) ? [ '!=BOOST_ID' => $activeBoostIds ] : [];
		$tables = [
			SalesBoost\Run\Storage\CollectorTable::class => [
				'=STATUS' => SalesBoost\Run\Storage\CollectorTable::STATUS_ACTIVE,
			],
			SalesBoost\Run\Storage\SubmitterTable::class => [
				'=STATUS' => [
					SalesBoost\Run\Storage\SubmitterTable::STATUS_READY,
					SalesBoost\Run\Storage\SubmitterTable::STATUS_ACTIVE,
				],
			],
		];

		/** @var SalesBoost\Run\Storage\CollectorTable|SalesBoost\Run\Storage\SubmitterTable $table */
		foreach ($tables as $table => $tableFilter)
		{
			$query = $table::getList([
				'filter' => $commonFilter + $tableFilter,
				'select' => [ 'BOOST_ID' ],
				'group' => [ 'BOOST_ID' ],
			]);

			$result += array_column($query->fetchAll(), 'BOOST_ID', 'BOOST_ID');
		}

		return array_keys($result);
	}

	protected function activeBoostIds()
	{
		$result = [];
		$boosts = SalesBoost\Setup\Model::loadList([
			'filter' => [ '=ACTIVE' => true ],
		]);

		foreach ($boosts as $boost)
		{
			if ($boost->isActiveDate())
			{
				$result[] = $boost->getId();
			}
		}

		return $result;
	}

	protected function createProcessor(array $boostIds, array $inactiveIds, Main\Type\DateTime $initTime, $step = null, $offset = null)
	{
		$processor = new SalesBoost\Run\Processor([
			'step' => $step,
			'stepOffset' => $offset,
			'timeLimit' => $this->request->getPost('TIME_LIMIT'),
			'initTime' => $initTime,
			'startTime' => microtime(true),
			'boosts' => $boostIds,
			'inactiveBoosts' => $inactiveIds,
			'progressCount' => true,
		]);

		return $processor;
	}

	protected function runErrorResponse(Result\StepProcessor $processResult)
	{
		$errorMessage = $processResult->hasErrors()
			? implode('<br />', $processResult->getErrorMessages())
			: self::getMessage('ACTION_RUN_ERROR_UNDEFINED');

		$adminMessage = new \CAdminMessage(array(
			'TYPE' => 'ERROR',
			'MESSAGE' => self::getMessage('ACTION_RUN_ERROR_TITLE'),
			'DETAILS' => $errorMessage,
			'HTML' => true,
		));

		return [
			'status' => 'error',
			'message' => $adminMessage->Show(),
		];
	}

	protected function runProgressResponse(
		SalesBoost\Run\Processor $processor,
		Result\StepProcessor $processResult,
		array $boostIds,
		array $inactiveIds,
		Main\Type\DateTime $initTime
	)
	{
		$progressMessage = '';

		foreach ($processor->steps() as $step)
		{
			if ($step->getName() !== $processResult->getStep()) { continue; }

			$readyCount = $processResult->getStepReadyCount();

			$progressMessage = '<p>';
			$progressMessage .= self::getMessage('ACTION_RUN_PROGRESS_STEP_' . mb_strtoupper($step->getName()));

			if ($readyCount !== null)
			{
				$progressMessage .= self::getMessage('ACTION_RUN_PROGRESS_READY_COUNT', [
					'#COUNT#' => (int)$readyCount,
					'#LABEL#' => Utils::sklon($readyCount, [
						self::getMessage('ACTION_RUN_PROGRESS_READY_COUNT_LABEL_1'),
						self::getMessage('ACTION_RUN_PROGRESS_READY_COUNT_LABEL_2'),
						self::getMessage('ACTION_RUN_PROGRESS_READY_COUNT_LABEL_5'),
					])
				]);
			}
			else
			{
				$progressMessage .= '...';
			}

			$progressMessage .= '</p>';

			break;
		}

		$adminMessage = new \CAdminMessage(array(
			'TYPE' => 'PROGRESS',
			'MESSAGE' => self::getMessage('ACTION_RUN_PROGRESS_TITLE'),
			'DETAILS' => $progressMessage,
			'HTML' => true,
		));

		return [
			'status' => 'progress',
			'message' => $adminMessage->Show(),
			'state' => [
				'sessid' => bitrix_sessid(),
				'BOOST_ID' => implode(',', $boostIds),
				'INACTIVE_ID' => implode(',', $inactiveIds),
				'INIT_TIME' => $initTime->getTimestamp(),
				'STEP' => $processResult->getStep(),
				'STEP_OFFSET' => $processResult->getStepOffset(),
			],
		];
	}

	protected function updateListener(array $boostIds)
	{
		$boosts = SalesBoost\Setup\Model::loadList([
			'filter' => [ '=ID' => $boostIds ],
		]);

		foreach ($boosts as $boost)
		{
			$boost->updateListener();
		}
	}

	protected function runFinishMessage()
	{
		$adminMessage = new \CAdminMessage(array(
			'TYPE' => 'OK',
			'MESSAGE' => self::getMessage('ACTION_RUN_SUCCESS_TITLE'),
			'HTML' => true,
		));

		return [
			'status' => 'ok',
			'message' => $adminMessage->Show(),
		];
	}

	protected function processStop()
	{
		return [
			'status' => 'ok',
		];
	}

	protected function getTabControlId()
	{
		return 'YANDEX_MARKET_ADMIN_SALES_BOOST_RUN';
	}

	public function preload()
	{
		$this->preloadBoosts();
		$this->unsetInactiveNotSubmitted();
		$this->compileNotifyGroup();
	}

	protected function preloadBoosts()
	{
		$selected = array_flip((array)$this->request->get('id'));
		$exists = [];

		foreach (SalesBoost\Setup\Model::loadList() as $boost)
		{
			$id = $boost->getId();
			$isActive = $boost->isActive();
			$isActiveDate = $boost->isActiveDate();

			$exists[$id] = true;

			if ($isActive && $isActiveDate)
			{
				$this->activeBoostVariants[$id] = [
					'ID' => $id,
					'NAME' => $boost->getField('NAME'),
				];
			}

			if (isset($selected[$id]))
			{
				$langFields = [
					'#ID#' => $id,
					'#NAME#' => $boost->getField('NAME'),
				];

				if (!$isActive)
				{
					$this->notifyGroup['INACTIVE'][$id] = $langFields;
				}
				else if (!$isActiveDate)
				{
					$nextDate = $boost->getNextActiveDate();

					if ($nextDate)
					{
						if ($nextDate instanceof Type\CanonicalDateTime)
						{
							$nextDate = clone $nextDate;
							$nextDate->setServerTimeZone();
						}

						$this->notifyGroup['IN_FUTURE'][$id] = $langFields + [
							'#DATE#' => $nextDate->toString()
						];
					}
					else
					{
						$this->notifyGroup['IN_PAST'][$id] = $langFields;
					}
				}
				else
				{
					$this->notifyGroup['READY'][$id] = $langFields;
				}
			}
		}

		foreach ($selected as $id => $dummy)
		{
			if (!isset($exists[$id]))
			{
				$this->notifyGroup['DELETE'][] = [
					'#ID#' => $id,
				];
			}
		}
	}

	protected function unsetInactiveNotSubmitted()
	{
		$inactive = array_merge(
			array_keys($this->notifyGroup['INACTIVE']),
			array_keys($this->notifyGroup['IN_PAST'])
		);

		if (empty($inactive)) { return; }

		$querySubmitted = SalesBoost\Run\Storage\SubmitterTable::getList([
			'filter' => [
				'=BOOST_ID' => array_unique($inactive),
				'=STATUS' => [
					SalesBoost\Run\Storage\SubmitterTable::STATUS_READY,
					SalesBoost\Run\Storage\SubmitterTable::STATUS_ACTIVE,
				],
			],
			'select' => [ 'BOOST_ID' ],
			'group' => [ 'BOOST_ID' ],
		]);

		$submitted = array_column($querySubmitted->fetchAll(), 'BOOST_ID', 'BOOST_ID');

		foreach ($inactive as $boostId)
		{
			if (isset($submitted[$boostId])) { continue; }

			if (isset($this->notifyGroup['INACTIVE'][$boostId]))
			{
				unset($this->notifyGroup['INACTIVE'][$boostId]);
			}

			if (isset($this->notifyGroup['IN_PAST'][$boostId]))
			{
				unset($this->notifyGroup['IN_PAST'][$boostId]);
			}
		}
	}

	protected function compileNotifyGroup()
	{
		foreach ($this->notifyGroup as $notifyGroup => $groupMessages)
		{
			$groupMessagesCount = count($groupMessages);
			$notifyGroupType = ($notifyGroup === 'READY' ? 'OK' : 'ERROR');

			if ($groupMessagesCount > 1)
			{
				$list = '<ul>';

				foreach ($groupMessages as $groupMessage)
				{
					$list .=
						'<li>'
						. self::getMessage('REQUEST_BOOST_' . $notifyGroup . '_GROUP_ITEM', $groupMessage)
						. '</li>';
				}

				$list .= '</ul>';

				$adminMessage = new \CAdminMessage(array(
					'TYPE' => $notifyGroupType,
					'MESSAGE' => self::getMessage('REQUEST_BOOST_' . $notifyGroup . '_GROUP'),
					'DETAILS' => $list . self::getMessage('REQUEST_BOOST_' . $notifyGroup . '_GROUP_DETAILS'),
					'HTML' => true
				));

				$this->actionMessage .= $adminMessage->Show();
			}
			else if ($groupMessagesCount === 1)
			{
				$groupMessage = reset($groupMessages);

				$adminMessage = new \CAdminMessage(array(
					'TYPE' => $notifyGroupType,
					'MESSAGE' => self::getMessage('REQUEST_BOOST_' . $notifyGroup, $groupMessage),
					'DETAILS' => self::getMessage('REQUEST_BOOST_' . $notifyGroup . '_DETAILS', $groupMessage)
				));

				$this->actionMessage .= $adminMessage->Show();
			}
		}

		if ($this->actionMessage !== '')
		{
			$this->actionMessage = '<div class="b-admin-message-list b-admin-text-message">' . $this->actionMessage . '</div>';
		}
	}

	protected function showFormBody()
	{
		$this->showBoostField();
		$this->showTimeField();
	}

	protected function showBoostField()
	{
		if (empty($this->activeBoostVariants)) { return; }

		$selected = array_flip((array)$this->request->get('id'));

		?>
		<tr>
			<td width="40%" align="right" valign="middle"><?= self::getMessage('FIELD_BOOST_ID') ?></td>
			<td width="60%">
				<?php
				foreach (array_diff_key($selected, $this->activeBoostVariants) as $inactiveId => $unused)
				{
					?>
					<input type="hidden" name="BOOST_ID[]" value="<?= (int)$inactiveId ?>" />
					<?php
				}
				?>
				<select name="BOOST_ID[]" <?= count($this->notifyGroup['READY']) > 1 ? 'multiple size="5"' : '' ?>>
					<?php
					foreach ($this->activeBoostVariants as $boost)
					{
						/** @noinspection HtmlUnknownAttribute */
						echo sprintf(
							'<option value="%s" %s>%s</option>',
							$boost['ID'],
							isset($selected[$boost['ID']]) ? 'selected' : '',
							sprintf('[%s] %s', $boost['ID'], Utils::htmlEscape($boost['NAME']))
						);
					}
					?>
				</select>
			</td>
		</tr>
		<?php
	}
}