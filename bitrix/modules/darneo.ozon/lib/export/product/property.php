<?php

namespace Darneo\Ozon\Export\Product;

use CIBlockElement;

class Property extends Base
{
    public function get(string $propId = ''): string
    {
        return $propId ? $this->getProperty($propId) : '';
    }

    private function getProperty(string $propId): string
    {
        $rows = [];

        $result = CIBlockElement::GetProperty(
            $this->iblockCatalogId,
            $this->elementId,
            'sort',
            'asc',
            ['ID' => $propId]
        );
        while ($row = $result->Fetch()) {
            $rows[] = $row['VALUE_ENUM'] ?: $row['VALUE'];
        }

        if ($this->offerId) {
            $result = CIBlockElement::GetProperty(
                $this->iblockOffersId,
                $this->offerId,
                'sort',
                'asc',
                ['ID' => $propId]
            );
            while ($row = $result->Fetch()) {
                $rows[] = $row['VALUE_ENUM'] ?: $row['VALUE'];
            }
        }

        foreach ($rows as $key => $item) {
            if ($item && $this->isSerializable($item)) {
                $tmpRow = unserialize($item, ['allowed_classes' => false]);
                if ($tmpRow['TEXT']) {
                    $rows[$key] = $tmpRow['TEXT'];
                } else {
                    $rows[$key] = $tmpRow;
                }
            }
        }

        $text = implode(', ', $rows);

        return $text;
    }

    private function isSerializable(string $value): bool
    {
        $data = unserialize($value, ['allowed_classes' => false]);

        return $data !== false;
    }
}
