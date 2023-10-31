<?php
namespace Yandex\Market\Component\TradingImport;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Api;
use Yandex\Market\Api\Campaigns as ApiCampaigns;
use Yandex\Market\Trading\Setup as TradingSetup;
use Yandex\Market\Trading\Settings as TradingSettings;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Facade as TradingFacade;

class EditForm extends Market\Component\Plain\EditForm
{
	use Market\Component\Concerns\HasUiService;
	use Market\Reference\Concerns\HasMessage;

	protected $entity;
	protected $fields;

	public function prepareComponentParams($params)
	{
		return $params;
	}

	public function getRequiredParams()
	{
		return [];
	}

	public function getFields(array $select = [], $item = null)
	{
		$result = parent::getFields($select, $item);

		if (!empty($result['CAMPAIGN_ID']['SETTINGS']['DEFAULT_VALUE']) && !isset($item['CAMPAIGN_ID'])) // hack for depend
		{
			$this->setComponentResult('ITEM', $item + [
				'CAMPAIGN_ID' => $result['CAMPAIGN_ID']['SETTINGS']['DEFAULT_VALUE'],
			]);
		}

		return $result;
	}

	public function load($primary, array $select = [], $isCopy = false)
	{
		return [
			'SETUP_ID' => $primary,
		];
	}

	public function add($fields)
	{
		$typedResult = new Main\Entity\AddResult();
		$importResult = $this->import($fields['SETUP_ID'], $fields);

		Market\Result\Facade::merge([$typedResult, $importResult]);

		if ($typedResult->isSuccess())
		{
			$data = $importResult->getData();

			if (!empty($data['NEW_SETUP']))
			{
				$typedResult->setId(reset($data['NEW_SETUP']));
			}
			else if (!empty($data['EXIST_SETUP']))
			{
				$typedResult->setId(reset($data['EXIST_SETUP']));
			}
		}

		return $typedResult;
	}

	public function update($primary, $fields)
	{
		$typedResult = new Main\Entity\UpdateResult();
		$importResult = $this->import($primary, $fields);

		return Market\Result\Facade::merge([$typedResult, $importResult]);
	}

	protected function import($primary, $fields)
	{
		$origin = TradingSetup\Model::loadById($primary);
		$requestedCampaignIds = (array)$fields['CAMPAIGN_ID'];
		$requestedCampaignMap = array_flip($requestedCampaignIds);
		$existsCampaignMap = $this->getExistsCampaigns($requestedCampaignIds);
		$result = new Main\Result();
		$newSetupIds = [];

		/** @var Market\Api\Partner\BusinessInfo\Model\Campaign $campaign */
		foreach ($this->getBusinessCampaigns($origin) as $campaign)
		{
			$campaignId = $campaign->getId();

			if (!isset($requestedCampaignMap[$campaignId])) { continue; }

			if (isset($existsCampaignMap[$campaignId]))
			{
				$campaignSetup = TradingSetup\Model::loadById($existsCampaignMap[$campaignId]);
				$migrateResult = $this->migrateCampaign($campaignSetup, $campaign, $fields);

				if (!$migrateResult->isSuccess())
				{
					$result->addErrors($migrateResult->getErrors());
				}
			}
			else
			{
				$importResult = $this->createCampaign($origin, $campaign, $fields);

				if ($importResult->isSuccess())
				{
					$newSetupIds[] = $importResult->getId();
				}
				else
				{
					$result->addErrors($importResult->getErrors());
				}
			}
		}

		if ($result->isSuccess())
		{
			$this->fillSuccessMessage();
			$this->fillSaveUrl($newSetupIds, $existsCampaignMap);

			$result->setData([
				'NEW_SETUP' => $newSetupIds,
				'EXIST_SETUP' => array_values(array_intersect_key($existsCampaignMap, $requestedCampaignMap)),
			]);
		}

		return $result;
	}

	protected function migrateCampaign(TradingSetup\Model $setup, ApiCampaigns\Model\Campaign $campaign, array $data)
	{
		try
		{
			$overrides = $this->campaignOverrides($setup, $campaign, $data);
			$result = new Main\Entity\UpdateResult();

			if (empty($overrides)) { return $result; }

			TradingFacade\Routine::mergeSettings($setup, $overrides);
		}
		catch (Main\SystemException $exception)
		{
			$result = new Main\Entity\UpdateResult();
			$result->addError(new Main\Error($exception->getMessage()));
		}

		return $result;
	}

	protected function createCampaign(TradingSetup\Model $origin, ApiCampaigns\Model\Campaign $campaign, array $data)
	{
		try
		{
			$overrides = $this->campaignOverrides($origin, $campaign, $data);
			$fields = [
				'NAME' => $campaign->getDomain(),
				'SITE_ID' => $origin->getSiteId(),
				'TRADING_SERVICE' => $origin->getField('TRADING_SERVICE'),
				'TRADING_BEHAVIOR' => $campaign->getTradingBehavior(),
			];
			$fields['CODE'] = $this->guessCampaignCode($campaign, array_intersect_key($fields, [
				'TRADING_SERVICE' => true,
				'TRADING_BEHAVIOR' => true,
			]));

			$newSetup = new TradingSetup\Model($fields);

			$newSetup->install();
			$newSetup->activate();

			TradingFacade\Routine::copySettings($origin, $newSetup, $overrides);

			$newSetup->wakeupService();
			$newSetup->tweak();

			$result = new Main\Entity\AddResult();
			$result->setId($newSetup->getId());
		}
		catch (Main\SystemException $exception)
		{
			$result = new Main\Entity\AddResult();
			$result->addError(new Main\Error($exception->getMessage()));
		}

		return $result;
	}

	/** @noinspection PhpDeprecationInspection */
	protected function guessCampaignCode(ApiCampaigns\Model\Campaign $campaign, array $tradingLink)
	{
		$nameCode = \CUtil::translit($campaign->getDomain(), 'ru', [
			'max_len' => 10,
			'replace_space' => '-',
			'replace_other' => '-',
		]);
		$nameCode = trim($nameCode, '-');
		$variants = [
			$nameCode,
			$campaign->getId(),
		];
		$result = null;

		foreach ($variants as $variant)
		{
			$variant = trim($variant);

			if ($variant === '') { continue; }

			for ($repeatCount = 0; $repeatCount < 3; ++$repeatCount)
			{
				$guess = $variant;

				if ($repeatCount > 0)
				{
					$guess = Market\Data\TextString::getSubstring($guess, 0, 6) . '-' . randString(3);
					$guess = str_replace('--', '-', $guess);
					$guess = Market\Data\TextString::toLower($guess);
				}

				$query = TradingSetup\Table::getList([
					'filter' => [
						'=CODE' => $guess,
						'=TRADING_SERVICE' => $tradingLink['TRADING_SERVICE'],
						'=TRADING_BEHAVIOR' => $tradingLink['TRADING_BEHAVIOR'],
					],
					'select' => [ 'ID' ],
					'limit' => 1,
				]);

				if ($query->fetch()) { continue; }

				$result = $guess;
				break;
			}

			if ($result !== null) { break; }
		}

		if ($result === null)
		{
			throw new Main\SystemException(self::getMessage('ERROR_UNIQUE_CODE', [
				'#CAMPAIGN_ID#' => $campaign->getId(),
			]));
		}

		return $result;
	}

	protected function campaignOverrides(TradingSetup\Model $origin, ApiCampaigns\Model\Campaign $campaign, array $data)
	{
		$options = $origin->wakeupService()->getOptions();
		$result = [];

		$options->suppressRequired();

		if ($options instanceof Api\Reference\HasOauthConfiguration && (int)$options->getCampaignId() !== $campaign->getId())
		{
			$result['CAMPAIGN_ID'] = $campaign->getId();
		}

		if ($options instanceof TradingService\Common\Options)
		{
			$result += $this->productStoreOverrides($options, $campaign, $data);
		}

		if ($options instanceof TradingService\Marketplace\Options)
		{
			$result += $this->useWarehousesOverrides($options);
		}

		$options->suppressRequired(false);

		return $result;
	}

	protected function productStoreOverrides(TradingService\Common\Options $options, ApiCampaigns\Model\Campaign $campaign, array $data)
	{
		$productStoreKey = 'SETTINGS_PRODUCT_STORE_' . $campaign->getId();

		if (empty($data[$productStoreKey])) { return []; }

		$selected = (array)$data[$productStoreKey];
		$current = (array)$options->getValue('PRODUCT_STORE');
		$diff = array_diff($selected, $current);

		if (empty($diff) && count($selected) === count($current)) { return []; }

		return [
			'PRODUCT_STORE' => $selected,
		];
	}

	protected function useWarehousesOverrides(TradingService\Marketplace\Options $options)
	{
		if (!$options->useWarehouses()) { return []; }

		return [
			'USE_WAREHOUSES' => TradingSettings\Table::BOOLEAN_N,
		];
	}

	protected function fillSuccessMessage()
	{
		$gridId = (string)$this->getComponentParam('GRID_ID');

		if ($gridId === '') { return; }

		$_SESSION[$gridId . '_MESSAGE'] = [
			'TYPE' => 'OK',
			'MESSAGE' => self::getMessage('SUCCESS_MESSAGE'),
			'DETAILS' => self::getMessage('SUCCESS_DETAILS'),
			'HTML' => true,
		];
	}

	protected function fillSaveUrl(array $newSetupIds, array $existsSetupIds)
	{
		$setupIds = !empty($newSetupIds) ? $newSetupIds : $existsSetupIds;

		if (empty($setupIds)) { return; }

		$listUrl = (string)$this->getComponentParam('LIST_URL');
		$listUrl .= (Market\Data\TextString::getPosition($listUrl, '?') === false ? '?' : '&');
		$listUrl .= http_build_query([
			'find_id_numsel' => 'range',
			'find_id_from' => min($setupIds),
			'find_id_to' => max($setupIds),
			'set_filter' => 'Y',
			'apply_filter' => 'Y',
		]);

		$this->setComponentParam('SAVE_URL', $listUrl);
	}

	protected function getAllFields()
	{
		if ($this->fields === null)
		{
			$this->fields = $this->makeAllFields();
		}

		return $this->fields;
	}

	protected function makeAllFields()
	{
		$result = $this->makeSetupFields();
		$setup = $this->resolveSetup($result);

		$result += $this->makeCampaignFields($setup);
		$result += $this->makeSettingsFields($setup, $result);

		return $this->compileAllFields($result);
	}

	protected function makeSetupFields()
	{
		global $APPLICATION;

		$refreshUrl = $APPLICATION->GetCurPageParam('', [ 'id' ], false);

		return [
			'SETUP_ID' => [
				'TYPE' => 'enumeration',
				'MANDATORY' => 'Y',
				'NAME' => self::getMessage('FIELD_SETUP_ID'),
				'HELP_MESSAGE' => self::getMessage('FIELD_SETUP_ID_HELP'),
				'VALUES' => $this->getSetupEnum(),
				'SETTINGS' => [
					'ALLOW_NO_VALUE' => 'N',
					'ONCHANGE' => sprintf(
						'window.location = "%s" + "&id=" + this.value',
						addslashes($refreshUrl)
					),
				],
			],
		];
	}

	protected function resolveSetup(array $fields)
	{
		if (empty($fields['SETUP_ID']['VALUES']))
		{
			throw new Main\SystemException(self::getMessage('ERROR_SETUP_EMPTY', [
				'#SETUP_URL#' => Market\Ui\Admin\Path::getModuleUrl('trading_setup', array_filter([
					'lang' => LANGUAGE_ID,
					'service' => $this->getUiServiceParameterValue(),
				]))
			]));
		}

		$input = $this->getComponentResult('ITEM');
		$setupIds = array_column($fields['SETUP_ID']['VALUES'], 'ID');
		$setupId = isset($input['SETUP_ID']) && in_array((int)$input['SETUP_ID'], $setupIds, true)
			? $input['SETUP_ID']
			: reset($setupIds);

		return TradingSetup\Model::loadById($setupId);
	}

	protected function makeCampaignFields(TradingSetup\Model $setup)
	{
		return [
			'CAMPAIGN_ID' => $this->getCampaignField($setup) + [
				'TYPE' => 'enumeration',
				'MULTIPLE' => 'Y',
				'MANDATORY' => 'Y',
				'NAME' => self::getMessage('FIELD_CAMPAIGN_ID'),
				'HELP_MESSAGE' => self::getMessage('FIELD_CAMPAIGN_ID_HELP'),
			],
		];
	}

	protected function makeSettingsFields(TradingSetup\Model $setup, array $fields)
	{
		if (empty($fields['CAMPAIGN_ID']['VALUES'])) { return []; }

		$result = [];
		$siteId = $setup->getSiteId();
		$storeEnum = $setup->getEnvironment()->getStore()->getEnum($siteId);

		// store

		foreach ($fields['CAMPAIGN_ID']['VALUES'] as $option)
		{
			$result['SETTINGS_PRODUCT_STORE_' . $option['ID']] = [
				'TYPE' => 'enumeration',
				'MANDATORY' => 'Y',
				'GROUP' => self::getMessage('FIELD_GROUP_PRODUCT_STORE'),
				'NAME' => $option['VALUE'],
				'VALUES' => $storeEnum,
				'DEPEND' => [
					'CAMPAIGN_ID' => [
						'RULE' => 'ANY',
						'VALUE' => $option['ID'],
					],
				],
			];
		}

		return $result;
	}

	protected function getSetupEnum()
	{
		$result = [];

		$query = TradingSetup\Table::getList([
			'filter' => [
				'!CAMPAIGN_ID.VALUE' => false,
				$this->getUiServiceFilter('TRADING_SERVICE', 'TRADING'),
			],
			'select' => [
				'ID',
				'NAME',
				'CAMPAIGN_ID_VALUE' => 'CAMPAIGN_ID.VALUE',
			],
			'runtime' => [
				new Main\Entity\ReferenceField('CAMPAIGN_ID', TradingSettings\Table::class, [
					'=ref.SETUP_ID' => 'this.ID',
					'=ref.NAME' => [ '?', 'CAMPAIGN_ID' ],
				], [
					'join_type' => 'inner',
				]),
			],
		]);

		while ($row = $query->fetch())
		{
			$result[] = [
				'ID' => (int)$row['ID'],
				'VALUE' => sprintf('[%s] %s', $row['CAMPAIGN_ID_VALUE'], $row['NAME']),
			];
		}

		return $result;
	}

	protected function getExistsCampaigns(array $campaignIds)
	{
		if (empty($campaignIds)) { return []; }

		$result = [];

		$query = TradingSetup\Table::getList([
			'filter' => [
				'=CAMPAIGN_ID.VALUE' => $campaignIds,
				$this->getUiServiceFilter('TRADING_SERVICE', 'TRADING'),
			],
			'select' => [
				'ID',
				'CAMPAIGN_ID_VALUE' => 'CAMPAIGN_ID.VALUE',
			],
			'runtime' => [
				new Main\Entity\ReferenceField('CAMPAIGN_ID', TradingSettings\Table::class, [
					'=ref.SETUP_ID' => 'this.ID',
					'=ref.NAME' => [ '?', 'CAMPAIGN_ID' ],
				], [
					'join_type' => 'inner',
				]),
			],
		]);

		while ($row = $query->fetch())
		{
			$result[$row['CAMPAIGN_ID_VALUE']] = (int)$row['ID'];
		}

		return $result;
	}

	protected function getCampaignField(TradingSetup\Model $setup)
	{
		$result = [
			'VALUES' => [],
			'SETTINGS' => [
				'DISPLAY' => 'CHECKBOX',
				'DEFAULT_VALUE' => [],
			],
		];

		try
		{
			/** @var ApiCampaigns\Model\Campaign $campaign */
			foreach ($this->getBusinessCampaigns($setup) as $campaign)
			{
				if ($campaign->getTradingBehavior() === null) { continue; }

				$result['SETTINGS']['DEFAULT_VALUE'][] = $campaign->getId();
				$result['VALUES'][] = [
					'ID' => $campaign->getId(),
					'VALUE' => sprintf('[%s] %s', $campaign->getId(), $campaign->getDomain()),
				];
			}
		}
		catch (Main\SystemException $exception)
		{
			$result['NOTE'] = self::getMessage(
				'ERROR_CAMPAIGN_LOAD',
				[ '#MESSAGE#' => $exception->getMessage() ],
				$exception->getMessage()
			);
		}

		return $result;
	}

	protected function getBusinessCampaigns(TradingSetup\Model $setup)
	{
		/** @var TradingService\Reference\Options&Api\Reference\HasOauthConfiguration $options */
		$service = $setup->wakeupService();
		$options = $service->getOptions();

		return Api\Campaigns\Facade::businessCampaigns($options, $service->getLogger());
	}

	protected function compileAllFields(array $fields)
	{
		$result = [];

		foreach ($fields as $name => $field)
		{
			$result[$name] = Market\Ui\UserField\Helper\Field::extend($field, $name);
		}

		return $result;
	}
}