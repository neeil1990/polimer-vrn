<?php

namespace Yandex\Market\Export\Entity\Reference;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Iblock;

abstract class ElementEvent extends Event
{
	protected static $elementIblockIdCache = [];

	public function getSourceParams($context)
    {
        return [
            'IBLOCK_ID' => (int)$context['IBLOCK_ID'],
            'OFFER_IBLOCK_ID' => isset($context['OFFER_IBLOCK_ID']) ? (int)$context['OFFER_IBLOCK_ID'] : null
        ];
    }

    protected static function isElementChangeRegistered($elementId, $sourceType, $sourceParams = null)
	{
		return Market\Export\Track\Manager::isElementChangeRegistered(
            Market\Export\Run\Manager::ENTITY_TYPE_OFFER,
            $elementId,
            $sourceType,
            $sourceParams
		);
	}

	protected static function registerElementChange($elementId, $sourceType, $sourceParams = null)
	{
		Market\Export\Track\Manager::registerElementChange(
            Market\Export\Run\Manager::ENTITY_TYPE_OFFER,
            $elementId,
            $sourceType,
            $sourceParams
		);
	}

	protected static function isTargetElement($iblockId, $offerIblockId, $elementId, $elementIblockId = null)
	{
		if ($elementIblockId === null)
		{
			$elementIblockId = static::getElementIblockId($elementId);
		}
		else
		{
			$elementIblockId = (int)$elementIblockId;
		}

		return (
			$elementIblockId !== null
			&& ($elementIblockId === (int)$iblockId || $elementIblockId === (int)$offerIblockId)
		);
	}

	protected static function getElementIblockId($elementId)
	{
		$result = null;
		$elementId = (int)$elementId;

		if ($elementId <= 0)
		{
			// nothing
		}
		else if (isset(static::$elementIblockIdCache[$elementId]))
		{
			$result = static::$elementIblockIdCache[$elementId] ?: null;
		}
		else if (Main\Loader::includeModule('iblock'))
		{
			$query = Iblock\ElementTable::getList([
				'filter' => [ '=ID' => $elementId ],
				'select' => [ 'IBLOCK_ID' ],
				'limit' => 1
			]);

			if ($row = $query->fetch())
			{
				$result = (int)$row['IBLOCK_ID'];
			}

			static::$elementIblockIdCache[$elementId] = $result ?: false;
		}

		return $result;
	}

	protected function getEvents($params)
	{
		return $this->getEventsForIblock($params['IBLOCK_ID'], $params['OFFER_IBLOCK_ID']);
	}

	abstract protected function getEventsForIblock($iblockId, $offerIblockId = null);
}