<?php
namespace Yandex\Market\Component\SalesBoostBid;

use Bitrix\Iblock;
use Bitrix\Main;
use Yandex\Market\Component\Molecules;
use Yandex\Market\Api\Business\Bids;
use Yandex\Market\Component;
use Yandex\Market\Reference\Assert;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Result;
use Yandex\Market\SalesBoost\Run;
use Yandex\Market\Trading;
use Yandex\Market\Ui\Admin;
use Yandex\Market\Ui\UserField\Helper\Field;
use Yandex\Market\Utils\ArrayHelper;

class GridList extends Component\Base\GridList
{
	use Concerns\HasMessage;
	use Concerns\HasOnce;

	const PAGE_SIZE = 50;

	protected $paging;

	public function __construct(\CBitrixComponent $component)
	{
		parent::__construct($component);

		$this->paging = new Molecules\ApiPaging($this->getComponentParam('GRID_ID'));
	}

	public function getFields(array $select = [])
	{
		$fields = $this->makeFields([
			'SKU' => [
				'TYPE' => 'primary',
				'FILTERABLE' => true,
				'SETTINGS' => [
					'URL_FIELD' => 'ADMIN_URL',
				],
			],
			'BID' => [
				'TYPE' => 'string',
			],
			'BID_RECOMMENDATION' => [
				'TYPE' => 'bidRecommendation',
				'MULTIPLE' => 'Y',
			],
			'PRICE_RECOMMENDATION' => [
				'TYPE' => 'priceRecommendation',
				'MULTIPLE' => 'Y',
			],
			'BOOST' => [
				'TYPE' => 'compound',
				'FIELDS' => [
					'BOOST_NAME' => [
						'TYPE' => 'primary',
						'SETTINGS' => [
							'URL_FIELD' => 'BOOST_URL',
						],
					],
					'BOOST_BID' => [
						'TYPE' => 'string',
					],
					'BOOST_STATUS' => [
						'TYPE' => 'enumeration',
						'VALUES' => array_map(static function($status) {
							return [
								'ID' => $status,
								'VALUE' => self::getMessage('FIELD_BOOST_STATUS_' . $status),
							];
						}, [
							Run\Storage\SubmitterTable::STATUS_ACTIVE,
							Run\Storage\SubmitterTable::STATUS_READY,
							Run\Storage\SubmitterTable::STATUS_ERROR,
							Run\Storage\SubmitterTable::STATUS_DELETE,
						])
					],
				],
			],
		]);

		return $this->onlySelect($fields, $select);
	}

	protected function makeFields(array $fields)
	{
		foreach ($fields as $name => &$field)
		{
			if ($field['TYPE'] === 'compound')
			{
				$field['FIELDS'] = $this->makeFields($field['FIELDS']);
			}

			$field += [
				'NAME' => self::getMessage('FIELD_' . $name),
				'FILTERABLE' => false,
				'SORTABLE' => false,
				'SELECTABLE' => true,
			];

			$field = Field::extend($field, $name);
		}
		unset($field);

		return $fields;
	}

	protected function onlySelect(array $fields, array $select)
	{
		if (empty($select)) { return $fields; }

		return array_intersect_key($fields, array_flip($select));
	}

	public function load(array $queryParameters = [])
	{
		try
		{
			$fetchParameters = $this->convertQueryParameters($queryParameters);
			$bidsResponse = $this->fetchBids($fetchParameters);
			$bids = $bidsResponse->getBids();
			$nowPage = isset($fetchParameters['page']) ? $fetchParameters['page'] : 1;

			if ($bidsResponse->getPaging()->hasNext())
			{
				$lastPage = $nowPage + 1;
				$this->paging->setPageToken($fetchParameters, $lastPage, $bidsResponse->getPaging()->getNextPageToken());
			}
			else
			{
				$lastPage = $nowPage;
			}

			return [
				'ITEMS' => $this->compileItems($bids, $queryParameters['select']),
				'TOTAL_COUNT' => $lastPage * 50,
			];
		}
		catch (Main\ArgumentException $exception)
		{
			if ($exception->getParameter() !== 'pageToken') { throw $exception; }

			$lastPage = isset($fetchParameters['page']) ? $fetchParameters['page'] : 1;

			return [
				'ITEMS' => [],
				'TOTAL_COUNT' => $lastPage * 50,
			];
		}
	}

	protected function convertQueryParameters(array $queryParameters)
	{
		$result = [];

		if (isset($queryParameters['filter']))
		{
			foreach ($queryParameters['filter'] as $key => $value)
			{
				if ($key === 'SKU')
				{
					if (empty($value)) { continue; }

					$result['skus'] = is_array($value) ? $value : [ $value ];
				}
			}
		}

		$result += $this->paging->getParameters($queryParameters);

		return $result;
	}

	protected function fetchBids(array $fetchParameters)
	{
		$options = $this->options();

		$request = new Bids\Info\Request();
		$request->setBusinessId($options->getBusinessId());
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setLogger($this->logger());
		$this->passFetchParameters($request, $fetchParameters);

		$sendResult = $request->send();

		Result\Facade::handleException($sendResult);

		/** @var Bids\Info\Response */
		return $sendResult->getResponse();
	}

	protected function passFetchParameters(Bids\Info\Request $request, array $fetchParameters)
	{
		if (isset($fetchParameters['skus']))
		{
			$request->setSkus($fetchParameters['skus']);
		}

		if (isset($fetchParameters['limit']))
		{
			$request->setLimit($fetchParameters['limit']);
		}

		if (isset($fetchParameters['pageToken']))
		{
			$request->setPageToken($fetchParameters['pageToken']);
		}
	}

	protected function skuIblockData(array $skus)
	{
		$skuMap = $this->mapSkus($skus);

		if (empty($skuMap)) { return []; }

		$productMap = ArrayHelper::flipGroup($skuMap);
		$result = [];

		$query = Iblock\ElementTable::getList([
			'filter' => [ '=ID' => array_keys($productMap) ],
			'select' => [ 'IBLOCK_ID', 'ID', 'NAME', 'IBLOCK_SECTION_ID' ],
		]);

		while ($row = $query->fetch())
		{
			if (!isset($productMap[$row['ID']])) { continue; }

			/** @noinspection PhpDeprecationInspection */
			$editUrl = \CIBlock::GetAdminElementEditLink($row['IBLOCK_ID'], $row['ID'], [
				'find_section_section' => $row['IBLOCK_SECTION_ID'],
			]);

			foreach ($productMap[$row['ID']] as $sku)
			{
				$result[$sku] = [
					'URL' => $editUrl,
					'NAME' => sprintf('[%s] %s', $sku, $row['NAME']),
				];
			}
		}

		return $result;
	}

	protected function mapSkus(array $skus)
	{
		if (empty($skus)) { return []; }

		/** @var Trading\Service\Common\Provider $service */
		$trading = $this->business()->getPrimaryTrading();
		$service = $trading->wakeupService();

		Assert::typeOf($service, Trading\Service\Common\Provider::class, 'service');

		$command = new Trading\Service\Common\Command\OfferMap(
			$service,
			$trading->getEnvironment()
		);

		$map = $command->make($skus);

		if ($map === null) { return array_combine($skus, $skus); }

		return $map;
	}

	protected function needRecommendation(array $select = null)
	{
		return empty($select) || in_array('BID_RECOMMENDATION', $select, true) || in_array('PRICE_RECOMMENDATION', $select, true);
	}

	protected function recommendationMap(array $skus)
	{
		if (empty($skus)) { return []; }

		$recommendationResponse = $this->fetchRecommendations($skus);
		$result = [];

		/** @var Bids\Recommendations\Model\Recommendation $recommendation */
		foreach ($recommendationResponse->getRecommendations() as $recommendation)
		{
			$bids = [];
			$prices = [];
			$bidRecommendations = $recommendation->getBidRecommendations();
			$priceRecommendations = $recommendation->getPriceRecommendations();

			if ($bidRecommendations !== null)
			{
				/** @var Bids\Recommendations\Model\Bid $bidRecommendation */
				foreach ($bidRecommendations as $bidRecommendation)
				{
					$bids[] = [
						'BID' => $bidRecommendation->getBid(),
						'BID_PERCENT' => round($bidRecommendation->getBid() / 100, 2) . '%',
						'PERCENT' => $bidRecommendation->getShowPercent(),
					];
				}
			}

			if ($priceRecommendations !== null)
			{
				/** @var Bids\Recommendations\Model\Price $priceRecommendation */
				foreach ($priceRecommendations as $priceRecommendation)
				{
					$prices[] = [
						'PRICE' => $priceRecommendation->getPrice(),
						'CAMPAIGN_ID' => $priceRecommendation->getCampaignId(),
					];
				}
			}

			$result[$recommendation->getSku()] = [
				'BID' => $bids,
				'PRICE' => $prices,
			];
		}

		return $result;
	}

	protected function fetchRecommendations(array $skus)
	{
		$options = $this->options();

		$request = new Bids\Recommendations\Request();
		$request->setBusinessId($options->getBusinessId());
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setLogger($this->logger());
		$request->setSkus($skus);

		$sendResult = $request->send();

		Result\Facade::handleException($sendResult);

		/** @var Bids\Recommendations\Response */
		return $sendResult->getResponse();
	}

	protected function needSubmitted(array $select = null)
	{
		return empty($select) || in_array('BOOST', $select, true);
	}

	protected function submittedMap(array $skus)
	{
		if (empty($skus)) { return []; }

		$result = [];

		$query = Run\Storage\SubmitterTable::getList([
			'filter' => [
				'=BUSINESS_ID' => $this->business()->getId(),
				'=SKU' => $skus,
				'=STATUS' => [
					Run\Storage\SubmitterTable::STATUS_READY,
					Run\Storage\SubmitterTable::STATUS_ACTIVE,
				],
			],
			'select' => [
				'SKU',
				'BID',
				'BOOST_ID',
				'BOOST_NAME' => 'BOOST.NAME',
				'STATUS',
			],
		]);

		while ($row = $query->fetch())
		{
			$result[$row['SKU']] = [
				'NAME' => sprintf('[%s] %s', $row['BOOST_ID'], $row['BOOST_NAME']),
				'BID' => round($row['BID'] / 100, 2) . '%',
				'URL' => Admin\Path::getModuleUrl('sales_boost_edit', [
					'id' => $row['BOOST_ID'],
				]),
				'STATUS' => $row['STATUS'],
			];
		}

		return $result;
	}

	protected function compileItems(Bids\Info\Model\BidCollection $bids, array $select = null)
	{
		$result = [];
		$skusIblockData = $this->skuIblockData($bids->skus());
		$recommendationMap = $this->needRecommendation($select) ? $this->recommendationMap($bids->skus()) : [];
		$submittedMap = $this->needSubmitted($select) ? $this->submittedMap($bids->skus()) : [];

		/** @var Bids\Info\Model\Bid $bid */
		foreach ($bids as $bid)
		{
			$sku = $bid->getSku();
			$recommendations = [];

			if (isset($recommendationMap[$sku]['BID']))
			{
				$recommendations = $recommendationMap[$sku]['BID'];

				foreach ($recommendations as &$recommendation)
				{
					$recommendation['ACTIVE'] = ($recommendation['BID'] <= $bid->getBid());
				}
				unset($recommendation);
			}

			$result[] = [
				'SKU' => isset($skusIblockData[$sku]) ? $skusIblockData[$sku]['NAME'] : $sku,
				'ADMIN_URL' => isset($skusIblockData[$sku]) ? $skusIblockData[$sku]['URL'] : null,
				'BID' => round($bid->getBid() / 100, 2) . '%',

				'BID_RECOMMENDATION' => $recommendations,
				'PRICE_RECOMMENDATION' => isset($recommendationMap[$sku]['PRICE'])
					? $recommendationMap[$sku]['PRICE']
					: null,

				'BOOST_NAME' => isset($submittedMap[$sku]) ? $submittedMap[$sku]['NAME'] : null,
				'BOOST_URL' => isset($submittedMap[$sku]) ? $submittedMap[$sku]['URL'] : null,
				'BOOST_BID' => isset($submittedMap[$sku]) ? $submittedMap[$sku]['BID'] : null,
				'BOOST_STATUS' => isset($submittedMap[$sku]) ? $submittedMap[$sku]['STATUS'] : null,
			];
		}

		return $result;
	}

	public function loadTotalCount(array $queryParameters = [])
	{
		return null;
	}

	protected function logger()
	{
		return $this->business()->getPrimaryTrading()->wakeupService()->getLogger();
	}

	protected function options()
	{
		/** @var Trading\Service\Marketplace\Options $options */
		$options = $this->business()->getPrimaryTrading()->wakeupService()->getOptions();

		Assert::typeOf($options, Trading\Service\Marketplace\Options::class, 'options');

		return $options;
	}

	/** @return Trading\Business\Model */
	protected function business()
	{
		return $this->once('business', function() {
			$model = $this->getComponentParam('BUSINESS_MODEL');

			if ($model !== null)
			{
				Assert::typeOf($model, Trading\Business\Model::class, 'arParams[BUSINESS_MODEL]');

				return $model;
			}

			$id = $this->getComponentParam('BUSINESS_ID');

			Assert::notEmpty($id, Trading\Business\Model::class, 'arParams[BUSINESS_ID]');

			return Trading\Business\Model::loadById($id);
		});
	}
}