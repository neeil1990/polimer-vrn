<?php

namespace Darneo\Ozon\Export\Stock;

use Bitrix\Main\EventManager;
use CCatalogSku;
use CIBlockElement;
use Darneo\Ozon\Export\Filter\Property as FilterProperty;
use Darneo\Ozon\Export\StockTmp;
use Darneo\Ozon\Export\Table\StockFilterTable;
use Darneo\Ozon\Export\Table\StockListTable;
use Darneo\Ozon\Export\Table\StockLogTable;
use Darneo\Ozon\Import\Table\ConnectionOfferProductTable;
use Darneo\Ozon\Main\Helper\Hash as HelperHash;
use Darneo\Ozon\Main\Helper\Settings as HelperSettings;

class Manager
{
    private array $settings;
    private StockTmp $entityStockTmp;

    public function __construct(int $exportSettingId)
    {
        $keyId = HelperSettings::getKeyIdCurrent();
        $this->settings = StockListTable::getById($exportSettingId)->fetch();
        $this->entityStockTmp = new StockTmp($keyId . '_' . $exportSettingId);
    }

    public function getDataTmpCount(): int
    {
        return $this->entityStockTmp->getCount();
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
            $this->entityStockTmp->add(['ELEMENT_ID' => $elementId]);
        }
    }

    private function reinstallTableTmp(): void
    {
        $this->entityStockTmp->dropTable();
        $this->entityStockTmp->initTable();
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

        $result = StockFilterTable::getList($parameters);

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
        $rows = [];

        $parameters = [
            'limit' => $limit,
            'offset' => ($limit * $page) - $limit,
        ];
        $result = $this->entityStockTmp->getList($parameters);
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
                if ($this->settings['STORE_ID']) {
                    $ozonQuantity = (new Storage($this->settings['ID'], $elementId))->get();
                } else {
                    $ozonQuantity = (new Storage($this->settings['ID'], $elementId))->getCountStorageAll();
                }
            } else {
                $ozonElementId = $offerId;
                $ozonVendorCode = (new Property($this->settings['ID'], $elementId, $offerId))->get(
                    $this->settings['VENDOR_CODE']
                );
                $ozonVendorCode = $ozonVendorCode ?: $offerId;
                if ($this->settings['STORE_ID']) {
                    $ozonQuantity = (new Storage($this->settings['ID'], $elementId, $offerId))->get();
                } else {
                    $ozonQuantity = (new Storage($this->settings['ID'], $elementId, $offerId))->getCountStorageAll();
                }
            }

            $ozonProductId = $this->getProductId($ozonVendorCode);

            $maxCountSore = $this->settings['MAX_COUNT_STORE'] ?: 0;
            if ($maxCountSore > 0 && $ozonQuantity > $maxCountSore) {
                $ozonQuantity = $maxCountSore;
            }

            $minCountSore = $this->settings['MIN_COUNT_STORE'];
            if ($minCountSore > 0 && $minCountSore > $ozonQuantity) {
                $ozonQuantity = 0;
            }

            $ozonQuantity = max($ozonQuantity, 0);

            $dataProduct = [
                'offer_id' => (string)$ozonVendorCode,
                'product_id' => (int)$ozonProductId,
                'stock' => (int)$ozonQuantity,
                'warehouse_id' => (int)$this->settings['OZON_STOCK_ID']
            ];

            foreach (EventManager::getInstance()->findEventHandlers('darneo.ozon', 'onExportStock') as $event) {
                $dataProduct = ExecuteModuleEventEx($event, [$row['ELEMENT_ID'], &$dataProduct]);
            }

            if ($this->isOptimization()) {
                if (!$this->isSkip($this->settings['ID'], $ozonElementId, $dataProduct)) {
                    $rows[$ozonElementId] = $dataProduct;
                }
            } else {
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
        return (bool)$this->settings['DISABLE_OPTIMISATION'] === false;
    }

    private function isSkip(int $stockId, int $elementId, array $json): bool
    {
        $parameters = [
            'filter' => ['STOCK_ID' => $stockId, 'ELEMENT_ID' => $elementId],
            'select' => ['IS_ERROR', 'SEND_JSON'],
            'order' => ['ID' => 'DESC'],
            'limit' => 1
        ];
        $result = StockLogTable::getList($parameters);
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
