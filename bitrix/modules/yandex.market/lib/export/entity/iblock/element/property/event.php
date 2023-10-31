<?php

namespace Yandex\Market\Export\Entity\Iblock\Element\Property;

use Yandex\Market;

class Event extends Market\Export\Entity\Reference\ElementEvent
{
	public function onAfterSetPropertyValues($iblockId, $offerIblockId, $elementId, $elementIblockId, $propertyValues, $flags)
	{
        $sourceType = $this->getType();
        $sourceParams = [
            'IBLOCK_ID' => $iblockId,
            'OFFER_IBLOCK_ID' => $offerIblockId
        ];

		if (
			!static::isElementChangeRegistered($elementId, $sourceType, $sourceParams)
			&& static::isTargetElement($iblockId, $offerIblockId, $elementId, $elementIblockId)
		)
		{
			static::registerElementChange($elementId, $sourceType, $sourceParams);
		}
	}

	protected function getEventsForIblock($iblockId, $offerIblockId = null)
	{
		return [
			[
				'module' => 'iblock',
				'event' => 'OnAfterIBlockElementSetPropertyValuesEx',
				'method' => 'onAfterSetPropertyValues',
				'arguments' => [
					$iblockId,
					$offerIblockId
				]
			],
			[
				'module' => 'iblock',
				'event' => 'OnAfterIBlockElementSetPropertyValues',
				'method' => 'onAfterSetPropertyValues',
				'arguments' => [
					$iblockId,
					$offerIblockId
				]
			]
		];
	}
}