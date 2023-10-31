<?php
namespace Yandex\Market\Trading\Service\Common\Command;

use Yandex\Market\Export;

class FeedPrimary
{
	public function exported(array $skus, array $feeds)
	{
		if (empty($skus) || empty($feeds)) { return []; }

		$result = [];

		$query = Export\Run\Storage\OfferTable::getList([
			'filter' => [
				'=SETUP_ID' => $feeds,
				'=STATUS' => Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS,
				'=PRIMARY' => $skus
			],
			'order' => [ 'TIMESTAMP_X' => 'DESC' ],
			'select' => [ 'ELEMENT_ID', 'PRIMARY' ]
		]);

		while ($row = $query->fetch())
		{
			$result[$row['PRIMARY']][] = (int)$row['ELEMENT_ID'];
		}

		return $result;
	}

	public function canUsePrimaryAsSku(array $feeds)
	{
		$result = true;

		foreach ($this->feedsIblocksWithShopSkuTag($feeds) as $iblockLinkId)
		{
			$tags = $this->feedPrimaryTagsSources($iblockLinkId);

			if (count(array_unique($tags)) > 1)
			{
				$result = false;
				break;
			}
		}

		return $result;
	}

	protected function feedsIblocksWithShopSkuTag(array $feeds)
	{
		if (empty($feeds)) { return []; }

		$query = Export\Param\Table::getList([
			'filter' => [
				'=XML_TAG' => 'shop-sku',
				'=IBLOCK_LINK.SETUP_ID' => $feeds,
			],
			'select' => [ 'IBLOCK_LINK_ID' ],
		]);

		return array_column($query->fetchAll(), 'IBLOCK_LINK_ID');
	}

	protected function feedPrimaryTagsSources($iblockLinkId)
	{
		$result = [];

		$query = Export\ParamValue\Table::getList([
			'filter' => [
				'=PARAM.IBLOCK_LINK_ID' => $iblockLinkId,
				[
					'LOGIC' => 'OR',
					[
						'=XML_TYPE' => Export\ParamValue\Table::XML_TYPE_ATTRIBUTE,
						'=XML_ATTRIBUTE_NAME' => 'id',
						'=PARAM.XML_TAG' => 'offer',
					],
					[
						'=XML_TYPE' => Export\ParamValue\Table::XML_TYPE_VALUE,
						'=PARAM.XML_TAG' => 'shop-sku',
					],
				]
			],
			'select' => [
				'XML_TAG' => 'PARAM.XML_TAG',
				'SOURCE_TYPE',
				'SOURCE_FIELD',
			],
		]);

		while ($row = $query->fetch())
		{
			$type = $row['SOURCE_TYPE'];
			$field = $row['SOURCE_FIELD'];

			if ($type === 'recommendation')
			{
				list($type, $field) = explode('|', $field, 2);
			}

			$result[$row['XML_TAG']] = $type . '.' . $field;
		}

		return $result;
	}
}