<?php

namespace Darneo\Ozon\Export\Product;

use Bitrix\Main\EventManager;
use CCatalogSku;
use CIBlockElement;
use Darneo\Ozon\Export\Filter\Property as FilterProperty;
use Darneo\Ozon\Export\ProductTmp;
use Darneo\Ozon\Export\Table\ProductFilterTable;
use Darneo\Ozon\Export\Table\ProductListTable;
use Darneo\Ozon\Export\Table\ProductLogTable;
use Darneo\Ozon\Main\Helper\Hash as HelperHash;
use Darneo\Ozon\Main\Helper\Settings as HelperSettings;

class Manager
{
    private array $settings;
    private ProductTmp $entityProductTmp;

    public function __construct(int $exportSettingId)
    {
        $keyId = HelperSettings::getKeyIdCurrent();
        $this->settings = ProductListTable::getById($exportSettingId)->fetch();
        $this->entityProductTmp = new ProductTmp($keyId . '_' . $exportSettingId);
    }

    public function getDataTmpCount(): int
    {
        return $this->entityProductTmp->getCount();
    }

    public function initDataTmp(int $page = 1, int $limit = 100): void
    {
        if ($page === 1) {
            $this->reinstallTableTmp();
        }

        $filter = $this->getIblockFilter();
        $result = CIBlockElement::GetList(
            ['ID' => 'DESC'],
            $filter,
            false,
            ['iNumPage' => $page, 'nPageSize' => $limit, 'checkOutOfRange' => true],
            ['ID', 'NAME', 'IBLOCK_ID']
        );

        $elementIds = [];
        while ($row = $result->GetNext()) {
            $offersList = $this->getOffersList($row['ID']);
            $offers = $offersList[$row['ID']];
            if ($offers) {
                foreach ($offers as $offer) {
                    $elementIds[] = $offer['ID'];
                }
            } else {
                $elementIds[] = $row['ID'];
            }
        }
        foreach ($elementIds as $elementId) {
            $this->entityProductTmp->add(['ELEMENT_ID' => $elementId]);
        }
    }

    private function reinstallTableTmp(): void
    {
        $this->entityProductTmp->dropTable();
        $this->entityProductTmp->initTable();
    }

    private function getIblockFilter(): array
    {
        $settingIblockId = $this->settings['IBLOCK_ID'];
        $settingFilterList = $this->getSettingFilterList($this->settings['ID']);
        $filter = [['IBLOCK_ID' => $settingIblockId, 'ACTIVE' => 'Y']];
        foreach ($settingFilterList as $settingFilter) {
            $propId = $settingFilter['PROP_ID'];
            $compareType = $settingFilter['COMPARE_TYPE'];
            $compareValue = $settingFilter['COMPARE_VALUE'];
            $filter[] = (new FilterProperty($propId, $compareType, $compareValue))->get();
        }

        $mFilter = [];
        foreach ($filter as $item) {
            foreach ($item as $name => $val) {
                $mFilter[$name][] = $val;
            }
        }

        return $mFilter;
    }

    private function getSettingFilterList(int $elementId): array
    {
        $rows = [];

        $parameters = [
            'filter' => [
                'ELEMENT_ID' => $elementId
            ],
            'select' => [
                'ID',
                'PROP_ID',
                'COMPARE_TYPE',
                'COMPARE_VALUE',
            ],
            'cache' => ['ttl' => 86400]
        ];

        $result = ProductFilterTable::getList($parameters);

        while ($row = $result->fetch()) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function getOffersList(int $productId): array
    {
        $result = CCatalogSKU::getOffersList(
            $productId,
            0,
            ['ACTIVE' => 'Y'],
            ['ID']
        );

        return $result ?: [];
    }

    public function getDataIblockCount(): int
    {
        $filter = $this->getIblockFilter();
        $result = CIBlockElement::GetList(['SORT' => 'ASC'], $filter, false, false, ['ID']);

        return $result->SelectedRowsCount() ?: 0;
    }

    public function getDataOzon(int $page = 1, int $limit = 100): array
    {
        $settingId = $this->settings['ID'];
        $settingElementName = $this->settings['ELEMENT_NAME'];
        $settingVendorCode = $this->settings['VENDOR_CODE'];
        $settingBarCode = $this->settings['BAR_CODE'];
        $settingWeight = $this->settings['WEIGHT'];
        $settingWidth = $this->settings['WIDTH'];
        $settingHeight = $this->settings['HEIGHT'];
        $settingLength = $this->settings['LENGTH'];
        $settingDimensionUnit = $this->settings['DIMENSION_UNIT'];
        $settingWeightUnit = $this->settings['WEIGHT_UNIT'];

        $rows = [];

        $parameters = [
            'limit' => $limit,
            'offset' => ($limit * $page) - $limit,
        ];
        $result = $this->entityProductTmp->getList($parameters);
        while ($row = $result->Fetch()) {
            $productMainId = $this->getProductMainId($row['ELEMENT_ID']);
            if ($productMainId) {
                $elementId = $productMainId;
                $offerId = $row['ELEMENT_ID'];
            } else {
                $elementId = $row['ELEMENT_ID'];
                $offerId = 0;
            }

            $weightUnit = $settingWeight ? $settingWeightUnit : Dimension::WEIGHT_UNIT_G;
            $dimensionUnit = $settingWidth || $settingHeight || $settingLength ? $settingDimensionUnit : Dimension::DIMENSION_UNIT_MM;

            if (empty($offerId)) {
                $ozonElementId = $elementId;

                if ($settingElementName) {
                    $ozonElementName = (new Property($settingId, $elementId))->get($settingElementName);
                } else {
                    $ozonElementName = (new Element($settingId, $elementId))->get()['NAME'];
                }

                $ozonDimension = (new Dimension($settingId, $elementId))->get();
                $ozonImage = (new Image($settingId, $elementId))->get();
                $ozonVendorCode = (new Property($settingId, $elementId))->get($settingVendorCode);
                $ozonBarCode = (new Property($settingId, $elementId))->get($settingBarCode);
                $ozonPrice = (new Price($settingId, $elementId))->get();
                $ozonCategory = (new Category($settingId, $elementId))->get();
                $ozonAttributes = (new Attribute($settingId, $elementId))->get();
                $ozonVendorCode = $ozonVendorCode ?: $elementId;

                if ($settingWeight) {
                    $ozonDimension['WEIGHT'] = (new Property($settingId, $elementId))->get($settingWeight);
                }
                if ($settingWidth) {
                    $ozonDimension['WIDTH'] = (new Property($settingId, $elementId))->get($settingWidth);
                }
                if ($settingHeight) {
                    $ozonDimension['HEIGHT'] = (new Property($settingId, $elementId))->get($settingHeight);
                }
                if ($settingLength) {
                    $ozonDimension['LENGTH'] = (new Property($settingId, $elementId))->get($settingLength);
                }
            } else {
                $ozonElementId = $offerId;

                if ($settingElementName) {
                    $ozonElementName = (new Property($settingId, $elementId, $offerId))->get($settingElementName);
                } else {
                    $ozonElementName = (new Element($settingId, $elementId, $offerId))->get()['NAME'];
                }

                $ozonDimension = (new Dimension($settingId, $elementId, $offerId))->get();
                $ozonImage = (new Image($settingId, $elementId, $offerId))->get();
                $ozonVendorCode = (new Property($settingId, $elementId, $offerId))->get($settingVendorCode);
                $ozonBarCode = (new Property($settingId, $elementId, $offerId))->get($settingBarCode);
                $ozonPrice = (new Price($settingId, $elementId, $offerId))->get();
                $ozonCategory = (new Category($settingId, $elementId, $offerId))->get();
                $ozonAttributes = (new Attribute($settingId, $elementId, $offerId))->get();
                $ozonVendorCode = $ozonVendorCode ?: $offerId;

                if ($settingWeight) {
                    $ozonDimension['WEIGHT'] = (new Property($settingId, $elementId, $offerId))->get($settingWeight);
                }
                if ($settingWidth) {
                    $ozonDimension['WIDTH'] = (new Property($settingId, $elementId, $offerId))->get($settingWidth);
                }
                if ($settingHeight) {
                    $ozonDimension['HEIGHT'] = (new Property($settingId, $elementId, $offerId))->get($settingHeight);
                }
                if ($settingLength) {
                    $ozonDimension['LENGTH'] = (new Property($settingId, $elementId, $offerId))->get($settingLength);
                }
            }

            $ozonDimension['WIDTH'] = $this->clearNumber($ozonDimension['WIDTH']);
            $ozonDimension['HEIGHT'] = $this->clearNumber($ozonDimension['HEIGHT']);
            $ozonDimension['LENGTH'] = $this->clearNumber($ozonDimension['LENGTH']);
            $ozonDimension['WEIGHT'] = $this->clearNumber($ozonDimension['WEIGHT']);

            // приведение габаритов и размеров в миллиметры и граммы
            switch ($dimensionUnit) {
                case Dimension::DIMENSION_UNIT_CM:
                    $dimensionUnit = Dimension::DIMENSION_UNIT_MM;
                    $ozonDimension['WIDTH'] *= 10;
                    $ozonDimension['HEIGHT'] *= 10;
                    $ozonDimension['LENGTH'] *= 10;
                    break;
                case Dimension::DIMENSION_UNIT_IN:
                    $dimensionUnit = Dimension::DIMENSION_UNIT_MM;
                    $ozonDimension['WIDTH'] *= 25.4;
                    $ozonDimension['HEIGHT'] *= 25.4;
                    $ozonDimension['LENGTH'] *= 25.4;
                    break;
            }

            switch ($weightUnit) {
                case Dimension::WEIGHT_UNIT_KG:
                    $weightUnit = Dimension::WEIGHT_UNIT_G;
                    $ozonDimension['WEIGHT'] *= 1000;
                    break;
                case Dimension::WEIGHT_UNIT_LB:
                    $weightUnit = Dimension::WEIGHT_UNIT_G;
                    $ozonDimension['WEIGHT'] *= 453.6;
                    break;
            }

            $dataProduct = [
                'attributes' => $ozonAttributes,
                'name' => $ozonElementName,
                'offer_id' => (string)$ozonVendorCode,
                'barcode' => $ozonBarCode,
                'images' => $ozonImage,
                'width' => (int)$ozonDimension['WIDTH'],
                'height' => (int)$ozonDimension['HEIGHT'],
                'depth' => (int)$ozonDimension['LENGTH'],
                'dimension_unit' => $dimensionUnit,
                'weight' => (int)$ozonDimension['WEIGHT'],
                'weight_unit' => $weightUnit,
                'old_price' => $ozonPrice['BASE_PRICE'],
                'price' => $ozonPrice['DISCOUNT_PRICE'],
                'vat' => $ozonPrice['VAT_RATE'],
                'category_id' => $ozonCategory['CATEGORY_ID'],
            ];

            foreach (EventManager::getInstance()->findEventHandlers('darneo.ozon', 'onExportProduct') as $event) {
                $dataProduct = ExecuteModuleEventEx($event, [$row['ELEMENT_ID'], &$dataProduct]);
            }

            if ($this->isOptimization() && !$this->isSkip($this->settings['ID'], $ozonElementId, $dataProduct)) {
                $rows[$ozonElementId] = $dataProduct;
            }
        }

        return $rows;
    }

    private function getProductMainId(int $elementId): int
    {
        $result = CCatalogSku::GetProductInfo($elementId);
        if (is_array($result)) {
            return $result['ID'];
        }

        return 0;
    }

    public function clearNumber(string $number): float
    {
        $number = trim($number);
        $number = str_replace([' ', ','], ['', '.'], $number);

        return (float)$number;
    }

    private function isOptimization(): bool
    {
        return true;
    }

    private function isSkip(int $productId, int $elementId, array $json): bool
    {
        $parameters = [
            'filter' => ['PRODUCT_ID' => $productId, 'ELEMENT_ID' => $elementId],
            'select' => ['IS_ERROR', 'SEND_JSON'],
            'order' => ['ID' => 'DESC'],
            'limit' => 1
        ];
        $result = ProductLogTable::getList($parameters);
        if ($row = $result->fetch()) {
            if (!$row['IS_ERROR']) {
                $newHash = HelperHash::generate($json);
                $oldHash = HelperHash::generate($row['SEND_JSON']);
                if ($newHash === $oldHash) {
                    return true;
                }
            }
        }

        return false;
    }
}
