<?php
namespace Yandex\Market\Export\Entity\Iblock\Property\Feature\Set;

use Bitrix\Iblock;
use Yandex\Market;

class Factory implements \ArrayAccess, \Countable, \IteratorAggregate
{
	use Market\Reference\Concerns\HasCollection;

	protected $context;
	protected $merged = [
		'catalog.OFFER_TREE' => 'iblock.DETAIL_PAGE_SHOW',
		'catalog.IN_BASKET' => null,
	];

	public function __construct(array $context)
	{
		$this->context = $context;

		$this->buildFeatures();
		$this->buildFilter();
	}

	protected function buildFeatures()
	{
		if ($this->isIblockFeatureEnabled())
		{
			$this->buildIblockFeatures();
		}
		else
		{
			$this->buildComponentFeatures();
		}
	}

	protected function isIblockFeatureEnabled()
	{
		return (
			class_exists(Iblock\Model\PropertyFeature::class)
			&& Iblock\Model\PropertyFeature::isEnabledFeatures()
		);
	}

	protected function buildIblockFeatures()
	{
		$property = $this->getFirstFeaturesProperty();

		if ($property === null) { return; }

		$iblockFeatures = Iblock\Model\PropertyFeature::getPropertyFeatureList($property);
		$iblockFeatures = $this->sortIblockFeatures($iblockFeatures);

		foreach ($iblockFeatures as $iblockFeature)
		{
			if (!isset($iblockFeature['MODULE_ID'], $iblockFeature['FEATURE_ID'])) { continue; }
			if ($this->isOurSibling($iblockFeature)) { continue; }

			$uniqueKey = $iblockFeature['MODULE_ID'] . '.' . $iblockFeature['FEATURE_ID'];

			$feature = new Feature($iblockFeature, $this->context);
			$feature->merge(array_keys(array_intersect($this->merged, [ $uniqueKey ])));
			$feature->deprecate(array_key_exists($uniqueKey, $this->merged));

			$this->collection[] = $feature;
		}
	}

	protected function getFirstFeaturesProperty()
	{
		$parametersVariants = [
			$this->getFeaturesPropertyParametersForOffersTree(),
			$this->getFeaturesPropertyParametersForOffersBasket(),
			$this->getFeaturesPropertyParametersForCatalog(),
			$this->getFeaturesPropertyParametersForElements(),
		];
		$result = null;

		foreach ($parametersVariants as $parametersVariant)
		{
			if ($parametersVariant === null) { continue; }

			$parametersVariant['limit'] = 1;

			$query = Iblock\PropertyTable::getList($parametersVariant);

			if ($row = $query->fetch())
			{
				$result = $row;
				break;
			}
		}

		return $result;
	}

	protected function getFeaturesPropertyParametersForOffersTree()
	{
		$result = null;

		if (isset($this->context['OFFER_IBLOCK_ID']) && (int)$this->context['OFFER_IBLOCK_ID'] > 0)
		{
			$result = [
				'filter' => [
					'=IBLOCK_ID' => $this->context['OFFER_IBLOCK_ID'],
					'=MULTIPLE' => 'N',
					[
						'LOGIC' => 'OR',
						[
							'=PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_LIST,
						],
						[
							'=PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_ELEMENT,
							'!=USER_TYPE' => \CIBlockPropertySKU::USER_TYPE,
						],
						[
							'=PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_STRING,
							'=USER_TYPE' => 'directory',
						],
					]
				],
			];
		}

		return $result;
	}

	protected function getFeaturesPropertyParametersForOffersBasket()
	{
		$result = null;

		if (isset($this->context['OFFER_IBLOCK_ID']) && (int)$this->context['OFFER_IBLOCK_ID'] > 0)
		{
			$result = [
				'filter' => [
					'=IBLOCK_ID' => $this->context['OFFER_IBLOCK_ID'],
					'!=PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_FILE,
				],
			];
		}

		return $result;
	}

	protected function getFeaturesPropertyParametersForCatalog()
	{
		$result = null;

		if (isset($this->context['IBLOCK_ID']) && (int)$this->context['IBLOCK_ID'] > 0)
		{
			$result = [
				'filter' => [
					'=IBLOCK_ID' => $this->context['IBLOCK_ID'],
					[
						'LOGIC' => 'OR',
						[
							'=MULTIPLE' => 'N',
							'=PROPERTY_TYPE' => [
								Iblock\PropertyTable::TYPE_ELEMENT,
								Iblock\PropertyTable::TYPE_LIST,
							],
						],
						[
							'=MULTIPLE' => 'Y',
							'=PROPERTY_TYPE' => [
								Iblock\PropertyTable::TYPE_ELEMENT,
								Iblock\PropertyTable::TYPE_SECTION,
								Iblock\PropertyTable::TYPE_LIST,
								Iblock\PropertyTable::TYPE_NUMBER,
								Iblock\PropertyTable::TYPE_STRING,
							],
						],
					]
				],
			];
		}

		return $result;
	}

	protected function getFeaturesPropertyParametersForElements()
	{
		$result = null;

		if (isset($this->context['IBLOCK_ID']) && (int)$this->context['IBLOCK_ID'] > 0)
		{
			$result = [
				'filter' => [ '=IBLOCK_ID' => $this->context['IBLOCK_ID'] ],
			];
		}

		return $result;
	}

	protected function sortIblockFeatures(array $iblockFeatures)
	{
		uasort($iblockFeatures, static function($featureA, $featureB) {
			$features = [
				'A' => $featureA,
				'B' => $featureB,
			];
			$sorts = array_fill_keys(array_keys($features), 500);

			foreach ($features as $featureKey => $feature)
			{
				if ($feature['MODULE_ID'] === Market\Config::getModuleName())
				{
					$sorts[$featureKey] = 1;
				}
				else if ($feature['MODULE_ID'] === 'iblock' && $feature['FEATURE_ID'] === Iblock\Model\PropertyFeature::FEATURE_ID_DETAIL_PAGE_SHOW)
				{
					$sorts[$featureKey] = 2;
				}
				else if ($feature['MODULE_ID'] === 'iblock' && $feature['FEATURE_ID'] === Iblock\Model\PropertyFeature::FEATURE_ID_LIST_PAGE_SHOW)
				{
					$sorts[$featureKey] = 3;
				}
			}

			if ($sorts['A'] === $sorts['B']) { return 0; }

			return $sorts['A'] < $sorts['B'] ? -1 : 1;
		});

		return $iblockFeatures;
	}

	protected function isOurSibling(array $iblockFeature)
	{
		if ($iblockFeature['MODULE_ID'] !== Market\Config::getModuleName()) { return false; }

		$serviceName = str_replace(Market\Ui\Iblock\PropertyFeature::FEATURE_ID_PREFIX, '', $iblockFeature['FEATURE_ID']);
		$serviceName = Market\Data\TextString::toLower($serviceName);

		if (
			$serviceName === Market\Ui\Service\Manager::TYPE_COMMON
			|| Market\Ui\Service\Manager::isExists($serviceName)
		)
		{
			$service = Market\Ui\Service\Manager::getInstance($serviceName);
			$isMatched = in_array($this->context['EXPORT_SERVICE'], $service->getExportServices(), true);

			$result = ($service->isInverted() === $isMatched);
		}
		else
		{
			$result = true;
		}

		return $result;
	}

	protected function buildComponentFeatures()
	{
		$this->collection[] = new DetailComponent($this->context);
		$this->collection[] = new ListComponent($this->context);
	}

	protected function buildFilter()
	{
		$this->collection[] = new Filter($this->context);
	}
}