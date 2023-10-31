<?php

namespace Yandex\Market\Export\Xml\Format\Reference;

use Yandex\Market\Export\Xml;
use Yandex\Market\Utils;

abstract class Base
{
	/** @return string|null */
	abstract public function getDocumentationLink();

	/** @return string */
	public function getPublishNote()
	{
		return '';
	}

	/** @return string[] */
	public function getSupportedFields()
	{
		return [];
	}

	public function useOfferHashCollision()
	{
		return true;
	}

	public function getContext()
	{
		return [];
	}

	/** @return bool */
	public function isSupportDeliveryOptions()
	{
		return false;
	}

	/** @return string */
	public function getHeader()
	{
		$encoding = Utils\Encoding::getCharset();

		$result = '<?xml version="1.0" encoding="' . $encoding . '"?>';
		$result .= '<!DOCTYPE yml_catalog SYSTEM "shops.dtd">';

		return $result;
	}

	/** @return Xml\Tag\Base */
	abstract public function getRoot();

	/** @return string|null */
	public function getCategoryParentName()
	{
		return null;
	}

	/** @return Xml\Tag\Base|null */
	public function getCategory()
	{
		return null;
	}

	/** @return string */
	public function getCurrencyParentName()
	{
		return null;
	}

	/** @return string */
	public function getCollectionParentName()
	{
		return null;
	}

	/** @return Xml\Tag\Base|null */
	public function getCollection()
	{
		return null;
	}

	/** @return Xml\Tag\Base|null */
	public function getCollectionId()
	{
		return null;
	}

	/** @return Xml\Tag\Base|null */
	public function getCurrency()
	{
		return null;
	}

	/** @return string */
	public function getPromoParentName()
	{
		return null;
	}

	/**
     * @param $type string|null
     *
	 * @return Xml\Tag\Base|null
	 */
	public function getPromo($type = null)
	{
		return null;
	}

    /** @return string|null */
    public function getPromoProductParentName()
    {
		return null;
    }

	/**
     * @param $type string|null
     *
	 * @return Xml\Tag\Base
	 */
	public function getPromoProduct($type = null)
	{
		return null;
	}

    /** @return string|null */
    public function getPromoGiftParentName()
    {
		return null;
    }

	/**
     * @param $type string|null
     *
	 * @return Xml\Tag\Base
	 */
	public function getPromoGift($type = null)
	{
		return null;
	}

	/** @return string|null */
	public function getGiftParentName()
	{
		return null;
	}

	/** @return Xml\Tag\Base */
	public function getGift()
	{
		return null;
	}

	/** @return string */
	abstract public function getOfferParentName();

	/** @return Xml\Tag\Base */
    abstract public function getOffer();

	/** @return string */
	abstract public function getType();

	protected function overrideTags($tags, $overrides)
	{
		if ($overrides === null) { return; }

		/** @var Xml\Tag\Base $tag */
		foreach ($tags as $tag)
		{
			$tagName = $tag->getId();

			if (!isset($overrides[$tagName])) { continue; }

			$newValues = $overrides[$tagName];

			if (isset($newValues['attributes']))
			{
				$this->overrideAttributes($tag->getAttributes(), $newValues['attributes']);
				unset($newValues['attributes']);
			}

			$tag->extendParameters($newValues);
		}
	}

	protected function overrideAttributes($attributes, $overrides)
	{
		if ($overrides === null) { return; }

		/** @var Xml\Attribute\Base $attribute */
		foreach ($attributes as $attribute)
		{
			$id = $attribute->getId();

			if (!isset($overrides[$id])) { continue; }

			$attribute->extendParameters($overrides[$id]);
		}
	}

	protected function sortTags(&$tags, $sort)
	{
		if ($sort === null) { return; }

		$fullSort = [];
		$nextSortIndex = 10;

		foreach ($tags as $tag)
		{
			$tagId = $tag->getId();
			$fullSort[$tagId] = isset($sort[$tagId]) ? $sort[$tagId] : $nextSortIndex;

			$nextSortIndex += 10;
		}

		uasort($tags, function($tagA, $tagB) use ($fullSort) {
			$tagAId = $tagA->getId();
			$tagBId = $tagB->getId();
			$tagASort = $fullSort[$tagAId];
			$tagBSort = $fullSort[$tagBId];

			if ($tagASort === $tagBSort) { return 0; }

			return ($tagASort < $tagBSort ? -1 : 1);
		});
	}

	protected function excludeTags(&$tags, $nameMap)
	{
		if ($nameMap === null) { return; }

		foreach ($tags as $tagIndex => $tag)
		{
			$tagName = $tag->getName();

			if (isset($nameMap[$tagName]))
			{
				unset($tags[$tagIndex]);
			}
		}
	}

	protected function removeChildTags(Xml\Tag\Base $tag, $names)
	{
		foreach ($names as $name)
		{
			$childTag = $tag->getChild($name);

			if ($childTag)
			{
				$tag->removeChild($childTag);
			}
		}
	}
}
