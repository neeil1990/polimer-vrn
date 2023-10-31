<?php

namespace Darneo\Ozon\Export\Product;

use Bitrix\Iblock\ElementTable;
use CFile;
use CIBlockElement;

class Image extends Base
{
    public function get(): array
    {
        switch ($this->settings['PHOTO_MAIN']) {
            case 'CATALOG_PREVIEW_PICTURE':
                $imageMain = $this->getImageMain($this->elementId, 'PREVIEW_PICTURE');
                break;
            case 'CATALOG_DETAIL_PICTURE':
                $imageMain = $this->getImageMain($this->elementId, 'DETAIL_PICTURE');
                break;
            case 'OFFERS_PREVIEW_PICTURE':
                $imageMain = $this->offerId ? $this->getImageMain($this->offerId, 'PREVIEW_PICTURE') : [];
                break;
            case 'OFFERS_DETAIL_PICTURE':
                $imageMain = $this->offerId ? $this->getImageMain($this->offerId, 'DETAIL_PICTURE') : [];
                break;
            default:
                $imageMain = $this->settings['PHOTO_MAIN'] ? $this->getImageOther($this->settings['PHOTO_MAIN']) : [];
                break;
        }
        $imageOther = $this->settings['PHOTO_OTHER'] ? $this->getImageOther($this->settings['PHOTO_OTHER']) : [];

        return array_merge($imageMain, $imageOther);
    }

    private function getImageMain(int $elementId, string $select): array
    {
        $image = [];
        $parameters = [
            'filter' => [
                'ID' => $elementId
            ],
            'select' => [$select],
        ];
        $result = ElementTable::getList($parameters);
        if (($row = $result->fetch()) && $row[$select]) {
            $pic = CFile::GetPath($row[$select]) ?: '';
            if ($pic) {
                if (strpos($pic, 'http') === false) {
                    $pic = $this->settings['DOMAIN'] . $pic;
                }
                $image[] = $pic;
            }
        }

        return $image;
    }

    private function getImageOther(int $propId): array
    {
        $image = [];

        $propImages = $this->getProperty($propId);
        foreach ($propImages as $imageId) {
            if (!$imageId) {
                continue;
            }

            $pic = CFile::GetPath($imageId) ?: '';
            if ($pic) {
                if (strpos($pic, 'http') === false) {
                    $pic = $this->settings['DOMAIN'] . $pic;
                }
                $image[] = $pic;
            }
        }

        return $image;
    }

    private function getProperty(string $propId): array
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

        return $rows;
    }
}
