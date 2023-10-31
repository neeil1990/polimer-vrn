<?php

namespace Yandex\Market\Export\IblockLink;

use Yandex\Market;

class Collection extends Market\Reference\Storage\Collection
{
	public static function getItemReference()
	{
		return Model::getClassName();
	}

	public function getByIblockId($iblockId)
    {
        $iblockId = (int)$iblockId;
        $result = null;

        if ($iblockId > 0)
        {
            /** @var Model $item */
            foreach ($this->collection as $item)
            {
                if ($item->getIblockId() === $iblockId)
                {
                    $result = $item;
                    break;
                }
            }
        }

        return $result;
    }

	public function getByOfferIblockId($iblockId)
    {
        $iblockId = (int)$iblockId;
        $result = null;

        if ($iblockId > 0)
        {
            /** @var Model $item */
            foreach ($this->collection as $item)
            {
                if ($item->getOfferIblockId() === $iblockId)
                {
                    $result = $item;
                    break;
                }
            }
        }

        return $result;
    }
}