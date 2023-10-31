<?php
namespace Yandex\Market\Export\Entity\Reference;

use Yandex\Market\Watcher;
use Yandex\Market\Data;
use Yandex\Market\Glossary;

abstract class ElementEvent extends Event
{
	public function getSourceParams($context)
    {
        return [
            'IBLOCK_ID' => (int)$context['IBLOCK_ID'],
            'OFFER_IBLOCK_ID' => isset($context['OFFER_IBLOCK_ID']) ? (int)$context['OFFER_IBLOCK_ID'] : null
        ];
    }

	/** @noinspection PhpUnusedParameterInspection */
	protected static function isElementChangeRegistered($elementId, $sourceType = null, $sourceParams = null)
	{
		return Watcher\Track\ElementChange::has(Glossary::ENTITY_OFFER, $elementId);
	}

	/** @noinspection PhpUnusedParameterInspection */
	protected static function registerElementChange($elementId, $sourceType = null, $sourceParams = null)
	{
		if (empty($elementId)) { return; }

		$iblockId = isset($sourceParams['IBLOCK_ID']) ? (int)$sourceParams['IBLOCK_ID'] : null;

		Watcher\Track\ElementChange::add(Glossary::ENTITY_OFFER, $elementId, $iblockId);
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

		return $elementIblockId === (int)$iblockId || $elementIblockId === (int)$offerIblockId;
	}

	protected static function getElementIblockId($elementId)
	{
		return Data\Iblock\Element::iblockId($elementId);
	}

	protected function getEvents($params)
	{
		return $this->getEventsForIblock($params['IBLOCK_ID'], $params['OFFER_IBLOCK_ID']);
	}

	abstract protected function getEventsForIblock($iblockId, $offerIblockId = null);
}