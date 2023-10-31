<?php

namespace Darneo\Ozon\Export\Price;

use Bitrix\Main\EventManager;
use CCatalogSku;
use CIBlockElement;
use Darneo\Ozon\Export\Filter\Property as FilterProperty;
use Darneo\Ozon\Export\PriceTmp;
use Darneo\Ozon\Export\Table\PriceFilterTable;
use Darneo\Ozon\Export\Table\PriceListTable;
use Darneo\Ozon\Export\Table\PriceLogTable;
use Darneo\Ozon\Import\Table\ConnectionOfferProductTable;
use Darneo\Ozon\Main\Helper\Hash as HelperHash;
use Darneo\Ozon\Main\Helper\Settings as HelperSettings;

class Manager
{
    private array $settings;
    private PriceTmp $entityPriceTmp;

    public function __construct(int $exportSettingId)
    {
        $keyId = HelperSettings::getKeyIdCurrent();
        $this->settings = PriceListTable::getById($exportSettingId)->fetch();
        $this->entityPriceTmp = new PriceTmp($keyId . '_' . $exportSettingId);
    }

    public function getDataTmpCount(): int
    {
        return $this->entityPriceTmp->getCount();
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
            $this->entityPriceTmp->add(['ELEMENT_ID' => $elementId]);
        }
    }

    private function reinstallTableTmp(): void
    {
        $this->entityPriceTmp->dropTable();
        $this->entityPriceTmp->initTable();
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

        $result = PriceFilterTable::getList($parameters);

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

    public function getDataOzon(int $page = 1, int $limit = 1000): array
    {
        $rows = [];
        $parameters = [
            'limit' => $limit,
            'offset' => ($limit * $page) - $limit,
        ];
        $result = $this->entityPriceTmp->getList($parameters);
        while ($row = $result->Fetch()) {
            $productMainId = $this->getProductMainId($row['ELEMENT_ID']);
            if ($productMainId) {
                $elementId = $productMainId;
                $offerId = $row['ELEMENT_ID'];
            } else {
                $elementId = $row['ELEMENT_ID'];
                $offerId = 0;
            }

            if (!$offerId) {
                $ozonElementId = $elementId;
                $ozonVendorCode = (new Property($this->settings['ID'], $elementId))->get(
                    $this->settings['VENDOR_CODE']
                );
                $ozonVendorCode = $ozonVendorCode ?: $elementId;
                $ozonPrice = (new Price($this->settings['ID'], $elementId))->get();
            } else {
                $ozonElementId = $offerId;
                $ozonVendorCode = (new Property($this->settings['ID'], $elementId, $offerId))->get(
                    $this->settings['VENDOR_CODE']
                );
                $ozonVendorCode = $ozonVendorCode ?: $offerId;
                $ozonPrice = (new Price($this->settings['ID'], $elementId, $offerId))->get();
            }

            $ozonProductId = $this->getProductId($ozonVendorCode);

            $dataProduct = [
                'auto_action_enabled' => 'UNKNOWN',
                'offer_id' => (string)$ozonVendorCode,
                'product_id' => $ozonProductId,
                'min_price' => $ozonPrice['DISCOUNT_PRICE'],
                'price' => $ozonPrice['DISCOUNT_PRICE'],
                'old_price' => $ozonPrice['BASE_PRICE']
            ];

            foreach (EventManager::getInstance()->findEventHandlers('darneo.ozon', 'onExportPrice') as $event) {
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

    private function getProductId($offerId): int
    {
        $parameters = ['filter' => ['OFFER_ID' => $offerId], 'cache' => ['ttl' => 86400]];
        $result = ConnectionOfferProductTable::getList($parameters);
        if ($row = $result->fetch()) {
            return $row['PRODUCT_OZON_ID'] ?: 0;
        }

        return 0;
    }

    private function isOptimization(): bool
    {
        return true;
    }

    private function isSkip(int $priceId, int $elementId, array $json): bool
    {
        $parameters = [
            'filter' => ['PRICE_ID' => $priceId, 'ELEMENT_ID' => $elementId],
            'select' => ['IS_ERROR', 'SEND_JSON'],
            'order' => ['ID' => 'DESC'],
            'limit' => 1
        ];
        $result = PriceLogTable::getList($parameters);
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

    public function getSettings(): array
    {
        return $this->settings;
    }
}
