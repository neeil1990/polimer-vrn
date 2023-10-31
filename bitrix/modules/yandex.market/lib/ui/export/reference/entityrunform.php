<?php
/** @noinspection DuplicatedCode */
namespace Yandex\Market\Ui\Export\Reference;

use Bitrix\Main;
use Yandex\Market\Data\Type;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Ui;
use Yandex\Market\Export;
use Yandex\Market\Utils;
use Yandex\Market\Logger;

abstract class EntityRunForm extends Ui\Reference\RunForm
{
	use Concerns\HasMessage;
	use Concerns\HasOnce;

	protected $activeEntityVariants = [];
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

		$APPLICATION->SetTitle(static::getMessage('TITLE'));
	}

	protected function getWriteRights()
	{
		return Ui\Access::RIGHTS_PROCESS_EXPORT;
	}

	public function processRun()
	{
		list($entityIds, $feedIds, $needAllFeeds, $initTime) = $this->bootRunContext();
		$feeds = $this->feedsForRun($feedIds, $needAllFeeds);

		if (!empty($entityIds) && empty($feeds))
		{
			throw new Main\SystemException(self::getMessage('ACTION_RUN_SETUP_NOT_FOUND'));
		}

		$setupOffset = (int)$this->request->getPost('SETUP_OFFSET');
		$setupIndex = 0;
		$startTime = microtime(true);
		$progressMessage = '';

		foreach ($feeds as $setup)
		{
			$setupDisplayName = '[' . $setup->getId() . '] ' . $setup->getField('NAME');

			if ($setupIndex < $setupOffset)
			{
				$progressMessage .= '<p><b>' . self::getMessage('ACTION_RUN_PROGRESS_SETUP', [
					'#NAME#' => $setupDisplayName
				]) . '</b></p>';
			}
			else if (!$setup->isFileReady())
			{
				$setupOffset++;
			}
			else
			{
				$isTargetOffset = ($setupIndex === $setupOffset);

				$processor = new Export\Run\Processor($setup, [
					'step' => $isTargetOffset ? $this->request->getPost('STEP') : null,
					'stepOffset' => $isTargetOffset ? $this->request->getPost('STEP_OFFSET') : null,
					'timeLimit' => $this->request->getPost('TIME_LIMIT'),
					'initTime' => $initTime,
					'startTime' => $startTime,
					'usePublic' => true,
					'progressCount' => true,
					'changes' => [
						$this->entityType() => $entityIds,
					],
				]);

				$processResult = $processor->run('change');
				$isSetupFinished = $processResult->isFinished();

				if ($isSetupFinished)
				{
					$setupOffset++;
				}

				if ($processResult->isSuccess() && (!$isSetupFinished || $processor->isTimeExpired()))
				{
					$processStepName = $processResult->getStep();

					$progressMessage .= '<p>';
					$progressMessage .= self::getMessage('ACTION_RUN_PROGRESS_SETUP', [
						'#NAME#' => $setupDisplayName
					]);

					if ($processStepName !== null)
					{
						$progressMessage .= self::getMessage('ACTION_RUN_PROGRESS_STEP', [
							'#STEP#' => Export\Run\Manager::getStepTitle($processStepName)
						]);
						$readyCount = $processResult->getStepReadyCount();

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
					}

					$progressMessage .= '</p>';

					$adminMessage = new \CAdminMessage(array(
						'TYPE' => 'PROGRESS',
						'MESSAGE' => self::getMessage('ACTION_RUN_PROGRESS_TITLE'),
						'DETAILS' => $progressMessage,
						'HTML' => true,
					));

					$response = [
						'status' => 'progress',
						'message' => $adminMessage->Show(),
						'state' => [
							'SETUP_LIST' => implode(',', $feedIds),
							'SETUP_ALL' => $needAllFeeds ? 'Y' : 'N',
							'ENTITY_LIST' => implode(',', $entityIds),
							'SETUP_OFFSET' => $setupOffset,
							'sessid' => bitrix_sessid(),
							'INIT_TIME' => $initTime->getTimestamp(),
						],
					];

					if (!$isSetupFinished)
					{
						$response['state'] += [
							'STEP' => $processResult->getStep(),
							'STEP_OFFSET' => $processResult->getStepOffset(),
						];
					}

					return $response;
				}

				if (!$isSetupFinished)
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
			}

			$setupIndex++;
		}

		$this->updateEntityListener($entityIds);

		return [
			'status' => 'ok',
			'message' => $this->finishSuccessMessage() . $this->finishLogMessage($initTime),
		];
	}

	abstract protected function entityType();

	protected function bootRunContext()
	{
		if ($this->request->getPost('INIT_TIME') !== null)
		{
			$initTime = Main\Type\DateTime::createFromTimestamp($this->request->getPost('INIT_TIME'));
			$entityIds = explode(',', $this->request->getPost('ENTITY_LIST'));
			$setupIds = explode(',', $this->request->getPost('SETUP_LIST'));
			$needAllSetup = ($this->request->getPost('SETUP_ALL') === 'Y');
		}
		else
		{
			$initTime = new Main\Type\DateTime();
			$selected = (array)$this->request->getPost('ENTITY_ID');

			Main\Type\Collection::normalizeArrayValuesByInt($selected);

			list($entityIds, $setupIds, $needAllSetup) = $this->createRunContext($selected);
		}

		return [$entityIds, $setupIds, $needAllSetup, $initTime];
	}

	protected function createRunContext(array $selected)
	{
		$entityIds = [];
		$setupIds = [];
		$needAllSetup = false;
		$exported = $this->exported();
		$models = $this->models(array_merge($selected, array_keys($exported)));

		// selected

		foreach ($selected as $id)
		{
			if (!isset($models[$id])) { continue; }

			$entityIds[$id] = true;
			$model = $models[$id];

			if ($model->isExportForAll())
			{
				$needAllSetup = true;
			}
			else if (!$needAllSetup)
			{
				$setupIds += $this->linked($model->getId());
			}
		}

		// exported

		foreach ($exported as $id => $exportedRows)
		{
			if (isset($models[$id]) && $models[$id]->isActive() && $models[$id]->isActiveDate()) { continue; }

			$setupIds += array_column($exportedRows, 'SETUP_ID', 'SETUP_ID');
			$entityIds[$id] = true;
		}

		return [
			array_keys($entityIds),
			array_keys($setupIds),
			$needAllSetup,
		];
	}

	abstract protected function linked($entityId);

	protected function feedsForRun(array $setupIdList, $isNeedAllSetup)
	{
		if (!$isNeedAllSetup && empty($setupIdList)) { return []; }

		return Export\Setup\Model::loadList([
			'filter' => $isNeedAllSetup ? [] : [ '=ID' => $setupIdList ]
		]);
	}

	protected function updateEntityListener(array $ids)
	{
		foreach ($this->models($ids) as $entity)
		{
			$entity->updateListener();
		}
	}

	protected function finishSuccessMessage()
	{
		$adminResultUrl = $this->finishResultUrl([
			'set_filter' => 'Y',
			'apply_filter' => 'Y',
		]);

		$adminMessage = new \CAdminMessage(array(
			'MESSAGE' => self::getMessage('ACTION_RUN_SUCCESS_TITLE'),
			'TYPE' => 'OK',
			'HTML' => true,
			'DETAILS' => self::getMessage('ACTION_RUN_SUCCESS_DETAILS', [
				'#URL#' => $adminResultUrl,
			])
		));

		return $adminMessage->Show();
	}

	abstract protected function finishResultUrl(array $query);

	protected function finishLogMessage(Main\Type\DateTime $initTime)
	{
		$result = '';

		$queryLog = Logger\Table::getList([
			'filter' => [
				'=ENTITY_TYPE' => $this->logEntityTypes(),
				'>=TIMESTAMP_X' => $initTime,
			],
			'select' => [ 'ENTITY_TYPE' ],
			'limit' => 1,
		]);

		if ($queryLog->fetch())
		{
			$logUrl = Ui\Admin\Path::getModuleUrl('log', [
				'set_filter' => 'Y',
				'apply_filter' => 'Y',
				'find_timestamp_x_from' => $initTime->toString(),
			]);

			$result .=
				PHP_EOL
				. '<div class="b-admin-text-message">'
				. self::getMessage('ACTION_RUN_SUCCESS_LOG', [
					'#URL#' => htmlspecialcharsbx($logUrl),
				])
				. '</div>';
		}

		return $result;
	}

	abstract protected function logEntityTypes();

	protected function processStop()
	{
		return [
			'status' => 'ok',
		];
	}

	public function preload()
	{
		$this->preloadEntities();
		$this->unsetInactiveNotExported();
		$this->compileNotifyGroup();
	}

	protected function preloadEntities()
	{
		$requested = array_flip((array)$this->request->get('id'));
		$exists = [];

		foreach ($this->models() as $model)
		{
			$id = $model->getId();
			$isActive = $model->isActive();
			$isActiveDate = $model->isActiveDate();

			$exists[$id] = true;

			if ($isActive && $isActiveDate)
			{
				$this->activeEntityVariants[] = [
					'ID' => $id,
					'NAME' => $model->getField('NAME'),
				];
			}

			if (isset($requested[$id]))
			{
				$langFields = [
					'#ID#' => $id,
					'#NAME#' => $model->getField('NAME'),
				];

				if (!$isActive)
				{
					$this->notifyGroup['INACTIVE'][$id] = $langFields;
				}
				else if (!$isActiveDate)
				{
					$nextDate = $model->getNextActiveDate();

					if ($nextDate)
					{
						if ($nextDate instanceof Type\CanonicalDateTime)
						{
							$nextDate = clone $nextDate;
							$nextDate->setServerTimeZone();
						}

						$this->notifyGroup['IN_FUTURE'][$id] = $langFields + [
							'#DATE#' => $nextDate->toString(),
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

		foreach ($requested as $id => $dummy)
		{
			if (!isset($exists[$id]))
			{
				$this->notifyGroup['DELETE'][] = [
					'#ID#' => $id,
				];
			}
		}
	}

	/** @return Export\Run\Data\EntityExportable[] */
	abstract protected function models(array $ids = null);

	protected function unsetInactiveNotExported()
	{
		$inactive = array_merge(
			array_keys($this->notifyGroup['INACTIVE']),
			array_keys($this->notifyGroup['IN_PAST'])
		);

		if (empty($inactive)) { return; }

		$inactive = array_unique($inactive, SORT_NUMERIC);
		$exported = $this->exported($inactive);

		foreach ($inactive as $entityId)
		{
			if (isset($exported[$entityId])) { continue; }

			if (isset($this->notifyGroup['INACTIVE'][$entityId]))
			{
				unset($this->notifyGroup['INACTIVE'][$entityId]);
			}

			if (isset($this->notifyGroup['IN_PAST'][$entityId]))
			{
				unset($this->notifyGroup['IN_PAST'][$entityId]);
			}
		}
	}

	abstract protected function exported(array $ids = null);

	protected function idsFilter(array $ids = null, $field = 'ID')
	{
		$filter = [];

		if ($ids !== null)
		{
			if (empty($ids)) { return null; }

			$filter = [ '=' . $field => array_unique($ids) ];
		}

		return $filter;
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
						. self::getMessage('REQUEST_ENTITY_' . $notifyGroup . '_GROUP_ITEM', $groupMessage)
						. '</li>';
				}

				$list .= '</ul>';

				$adminMessage = new \CAdminMessage(array(
					'TYPE' => $notifyGroupType,
					'MESSAGE' => self::getMessage('REQUEST_ENTITY_' . $notifyGroup . '_GROUP', $this->entityMessages()),
					'DETAILS' => $list . self::getMessage('REQUEST_ENTITY_' . $notifyGroup . '_GROUP_DETAILS', $this->entityMessages()),
					'HTML' => true
				));

				$this->actionMessage .= $adminMessage->Show();
			}
			else if ($groupMessagesCount === 1)
			{
				$groupMessage = reset($groupMessages);

				$adminMessage = new \CAdminMessage(array(
					'TYPE' => $notifyGroupType,
					'MESSAGE' => self::getMessage('REQUEST_ENTITY_' . $notifyGroup, $groupMessage + $this->entityMessages()),
					'DETAILS' => self::getMessage('REQUEST_ENTITY_' . $notifyGroup . '_DETAILS', $groupMessage + $this->entityMessages())
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
		$this->showEntityField();
		$this->showTimeField();
	}

	protected function showEntityField()
	{
		if (empty($this->activeEntityVariants)) { return; }

		$requested = array_flip((array)$this->request->get('id'));

		?>
		<tr>
			<td width="40%" align="right" valign="middle"><?= $this->entityMessage('NOMINATIVE') ?></td>
			<td width="60%">
				<select name="ENTITY_ID[]" <?= count($this->notifyGroup['READY']) > 1 ? 'multiple size="5"' : '' ?>>
					<?php
					foreach ($this->activeEntityVariants as $entity)
					{
						/** @noinspection HtmlUnknownAttribute */
						echo sprintf(
							'<option value="%s" %s>%s</option>',
							$entity['ID'],
							isset($requested[$entity['ID']]) ? 'selected' : '',
							sprintf('[%s] %s', $entity['ID'], Utils::htmlEscape($entity['NAME']))
						);
					}
					?>
				</select>
			</td>
		</tr>
		<?php
	}

	protected function entityMessage($case)
	{
		$messages = $this->entityMessages();

		return $messages['#ENTITY_' . $case . '#'];
	}

	protected function entityMessages()
	{
		return $this->once('entityMessages', null, function() {
			$result = [];
			$cases = [
				'NOMINATIVE',
				'PREPOSITIONAL',
			];
			$messages = [
				'ENTITY_%s',
				'ENTITY_%s_M',
			];

			foreach ($cases as $case)
			{
				foreach ($messages as $message)
				{
					$key = sprintf($message, $case);
					$text = static::getMessage($key);

					$result['#' . $key . '#'] = $text;
					$result['#' . $key . '_L#'] = mb_strtolower($text);
				}
			}

			return $result;
		});
	}
}