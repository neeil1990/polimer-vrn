<?php

namespace Yandex\Market\Export\PromoGift;

use Yandex\Market;
use Yandex\Market\Watcher;
use Yandex\Market\Export\Glossary;

class Model extends Market\Export\PromoProduct\Model
{
	/**
	 * Название класса таблицы
	 *
	 * @return Table
	 */
	public static function getDataClass()
	{
		return Table::getClassName();
	}

	public function getTagDescriptionList($offerPrimarySource = null)
	{
		if (empty($offerPrimarySource))
		{
			$offerPrimarySource = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD,
				'FIELD' => 'ID'
			];
		}

		return [
			[
				'TAG' => 'promo-gift',
				'VALUE' => null,
				'ATTRIBUTES' => [
					'offer-id' => $offerPrimarySource,
					'gift-id' => $offerPrimarySource,
				],
				'SETTINGS' => null
			]
		];
	}

	public function getSourceSelect()
    {
        $context = $this->getContext(true);
        $fieldSource = (
            $context['HAS_OFFER']
                ? Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD
                : Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD
        );

        return [
            $fieldSource => [
                'NAME',
                'PREVIEW_PICTURE',
                'DETAIL_PICTURE'
            ]
        ];
    }

	public function getSetupBindEntities(Market\Export\Setup\Model $setup)
	{
		$context = $this->getContext();

		if (
			!$this->isExportExternalGift($context)
			|| $setup->getIblockLinkCollection()->getByIblockId($context['IBLOCK_ID']) !== null
		)
		{
			return [];
		}

		$result = [
			new Watcher\Track\BindEntity(Glossary::ENTITY_OFFER, $context['IBLOCK_ID'], Glossary::ENTITY_GIFT, $setup->getId()),
		];

		if ($context['HAS_OFFER'])
		{
			$result[] = new Watcher\Track\BindEntity(Glossary::ENTITY_OFFER, $context['OFFER_IBLOCK_ID'], Glossary::ENTITY_GIFT, $setup->getId());
		}

		return $result;
	}

    public function getContext($isOnlySelf = false)
    {
        $result = parent::getContext($isOnlySelf);

        $result['EXPORT_GIFT'] = $this->isExportExternalGift($result);

        return $result;
    }

    /**
     * Выгружать товары, которые не попали в выгрузку
     *
     * @param $context
     *
     * @return bool
     */
    protected function isExportExternalGift(array $context)
    {
        if ($this->discount !== null)
        {
            $result = (bool)$this->discount->isExportExternalGift($context);
        }
        else
        {
            $result = ((string)$this->getField('EXPORT_GIFT') !== Table::BOOLEAN_N);
        }

        return $result;
    }

    protected function getDiscountProductFilterList($context)
    {
        $result = [];

        if ($this->discount !== null)
        {
            $result = $this->discount->getGiftFilterList($context);
        }

        return $result;
    }
}