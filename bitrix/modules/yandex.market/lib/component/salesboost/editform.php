<?php
namespace Yandex\Market\Component\SalesBoost;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Component\Molecules;

class EditForm extends Market\Component\Model\EditForm
{
	use Market\Reference\Concerns\HasOnce;
	use Market\Reference\Concerns\HasMessage;

	protected $productFilter;

	public function __construct(\CBitrixComponent $component)
	{
		parent::__construct($component);

		$this->productFilter = new Molecules\ProductFilter([
			'SALES_BOOST_PRODUCT',
		]);
	}

	public function getFields(array $select = [], $item = null)
	{
		$result = parent::getFields($select, $item);
		$result = $this->extendFieldBidField($result, $item);

		return $result;
	}

	protected function extendFieldBidField(array $fields, $item = null)
	{
		if (!isset($fields['BID_FIELD'])) { return $fields; }

		$fields['BID_FIELD']['SETTINGS'] = [
			'IBLOCK_ID' => !empty($item) ? $this->getUsedIblockList($item) : [],
		];

		return $fields;
	}

	public function modifyRequest($request, $fields)
	{
		$result = parent::modifyRequest($request, $fields);
		$result = $this->modifyRequestBusiness($result);
		$result = $this->productFilter->sanitizeIblock($result, $fields, $this->getUsedIblockList($result));
		$result = $this->productFilter->sanitizeFilter($result, $fields);

		return $result;
	}

	public function validate($data, array $fields = null)
	{
		$result = parent::validate($data, $fields);
		$this->productFilter->validate($result, $data, $fields);

		return $result;
	}

	protected function modifyRequestBusiness($request)
	{
		if (isset($request['BUSINESS']))
		{
			$request['BUSINESS_ID'] = $request['BUSINESS'];
		}

		return $request;
	}

	protected function getUsedIblockList($data)
	{
		$businessId = !empty($data['BUSINESS']) ? $data['BUSINESS'] : null;

		return $this->once('getUsedIblockList', [ $businessId ], function($businessId) {
			return (
				$this->getIblocksFromTrading($businessId)
				?: $this->getIblocksFromFeedByFilter([
					'=EXPORT_SERVICE' => [
						Market\Export\Xml\Format\Manager::EXPORT_SERVICE_MARKETPLACE,
						Market\Export\Xml\Format\Manager::EXPORT_SERVICE_YANDEX_MARKET,
					],
					'=AUTOUPDATE' => true,
				])
				?: $this->getIblocksFromFeedByFilter([
					'=EXPORT_SERVICE' => [
						Market\Export\Xml\Format\Manager::EXPORT_SERVICE_MARKETPLACE,
						Market\Export\Xml\Format\Manager::EXPORT_SERVICE_YANDEX_MARKET,
					],
				])
				?: $this->getIblocksFromCatalogSettings()
			);
		});
	}

	protected function getIblocksFromTrading($businessId)
	{
		try
		{
			if (empty($businessId)) { return []; }

			$used = [];
			$tradings = Market\Trading\Setup\Model::loadList([
				'filter' => [ '=BUSINESS.ID' => $businessId ],
			]);

			foreach ($tradings as $trading)
			{
				$options = $trading->wakeupService()->getOptions();

				if (!($options instanceof Market\Trading\Service\Marketplace\Options)) { return []; }

				$iblockIds =
					$this->getIblocksFromTradingSkuMap($options)
					?: $this->getIblocksFromLinkedFeed($options);

				$used += array_flip($iblockIds);
			}

			$result = array_keys($used);
		}
		catch (Market\Exceptions\Trading\SetupNotFound $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getIblocksFromTradingSkuMap(Market\Trading\Service\Marketplace\Options $options)
	{
		$skuMap = $options->getProductSkuMap();

		if (!is_array($skuMap)) { return []; }

		return $this->normalizeIblocks(array_column($skuMap, 'IBLOCK'));
	}

	protected function getIblocksFromLinkedFeed(Market\Trading\Service\Marketplace\Options $options)
	{
		$feeds = $options->getProductFeeds();

		return $this->getFeedIblocks($feeds);
	}

	protected function getIblocksFromFeedByFilter(array $filter)
	{
		$query = Market\Export\Setup\Table::getList([
			'filter' => $filter,
			'select' => [ 'ID' ],
		]);

		return $this->getFeedIblocks(array_column($query->fetchAll(), 'ID'));
	}

	protected function getIblocksFromCatalogSettings()
	{
		if (!Main\Loader::includeModule('catalog')) { return []; }

		$result = [];
		$query = \CCatalog::GetList();

		while ($row = $query->Fetch())
		{
			$iblockId = !empty($row['PRODUCT_IBLOCK_ID']) ? (int)$row['PRODUCT_IBLOCK_ID'] : (int)$row['IBLOCK_ID'];
			$result[$iblockId] = true;
		}

		return array_keys($result);
	}

	protected function normalizeIblocks(array $iblockIds)
	{
		if (!Main\Loader::includeModule('catalog')) { return $iblockIds; }

		$iblockMap = array_flip($iblockIds);

		foreach ($iblockIds as $iblockId)
		{
			$catalog = \CCatalogSku::GetInfoByIBlock($iblockId);

			if (isset($catalog['CATALOG_TYPE']) && $catalog['CATALOG_TYPE'] === \CCatalogSku::TYPE_OFFERS)
			{
				$iblockMap[$catalog['PRODUCT_IBLOCK_ID']] = true;
				unset($iblockMap[$iblockId]);
			}
		}

		return array_keys($iblockMap);
	}

	protected function getFeedIblocks(array $feedIds)
	{
		if (empty($feedIds)) { return []; }

		$iblockMap = [];

		$query = Market\Export\IblockLink\Table::getList([
			'filter' => [ '=SETUP_ID' => $feedIds ],
			'select' => [ 'IBLOCK_ID' ],
		]);

		while ($row = $query->fetch())
		{
			$iblockMap[$row['IBLOCK_ID']] = true;
		}

		return array_keys($iblockMap);
	}

	public function load($primary, array $select = [], $isCopy = false)
	{
		$result = parent::load($primary, $select, $isCopy);

		if (isset($result['BUSINESS_ID']))
		{
			$result['BUSINESS'] = $result['BUSINESS_ID'];
		}

		return $result;
	}

	public function extend($data, array $select = [])
	{
		$data = $this->productFilter->extend($data);

		return $data;
	}
}