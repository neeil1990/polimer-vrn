<?php
namespace Wbs24\Ozonexport;

class ExtendWarehouse extends Warehouse
{
    protected $wrappers;
    protected $warehouses;

    function __construct($param = [])
    {
        parent::__construct($param);

        $objects = $this->param['objects'] ?? [];
        $this->wrappers = new Wrappers($objects);

        $this->warehouses = false;
    }

    public function getAllWarehouses()
    {
        if ($this->warehouses === false) {
            $rsWarehouses = $this->wrappers->StoreTable->getList([
                'filter' => ['ACTIVE' => 'Y'],
                'select' => ['ID', 'TITLE'],
            ]);

            $allWarehouses = [];
            while($warehouse = $rsWarehouses->Fetch()){
                $allWarehouses[] = $warehouse;
            }

            $this->warehouses = $allWarehouses;
        }

        return $this->warehouses;
    }

    public function getXml($product)
    {
        $productId = $product['ID'] ?? 0;
        $stocks = $this->getStockInWarehouses($productId);
        $stocks = $this->filterAndRenameWarehouses($stocks);
        $stocks = $this->verifyAndDropStockIfLess($stocks);

        $xml = '<outlets>';
        foreach ($stocks as $stock) {
            $xml .=
                '<outlet '
                    .'instock="'.$stock['AMOUNT'].'" '
                    .'warehouse_name="'.$stock['STORE_TITLE'].'"'
                .'></outlet>'
            ;
        }
        $xml .= '</outlets>'."\n";

        return $xml;
    }

    public function getStockInWarehouses($productId)
    {
        $stocks = [];

        if ($productId) {
            $rsStoreProduct = $this->wrappers->StoreProductTable->getList([
                'filter' => ['=PRODUCT_ID' => $productId, 'STORE.ACTIVE' => 'Y'],
                'select' => ['AMOUNT', 'STORE_ID', 'STORE_TITLE' => 'STORE.TITLE'],
            ]);
            while ($stock = $rsStoreProduct->Fetch()){
                $warehouseId = $stock['STORE_ID'];
                $stocks[$warehouseId] = $stock;
            }
        }

        $warehouses = $this->getAllWarehouses();
        foreach ($warehouses as $warehouse) {
            $warehouseId = $warehouse['ID'];
            if (!isset($stocks[$warehouseId])) {
                $stocks[$warehouseId] = [
                    'AMOUNT' => 0,
                    'STORE_ID' => $warehouseId,
                    'STORE_TITLE' => $warehouse['TITLE'],
                ];
            }
        }

        return array_values($stocks);
    }

    public function filterAndRenameWarehouses($stocks)
    {
        $warehouseFilter = $this->param['extendWarehouseFilter'] ?? false;

        if ($warehouseFilter) {
            $filteredStocks = [];

            foreach ($stocks as $stock) {
                $stockId = $stock['STORE_ID'];
                $active = $this->param['warehouseId'.$stockId.'Active'] ?? false;
                $newName = $this->param['warehouseId'.$stockId.'Name'] ?? false;

                if ($active) {
                    $filteredStocks[] = [
                        'AMOUNT' => $stock['AMOUNT'],
                        'STORE_ID' => $stockId,
                        'STORE_TITLE' => $newName ?: $stock['STORE_TITLE'],
                    ];
                }
            }

            $stocks = $filteredStocks;
        }

        return $stocks;
    }

    protected function verifyAndDropStockIfLess($stocks)
    {
        $minStock = $this->param['minStock'] ?? 0;

        foreach ($stocks as $k => $stock) {
            $stocks[$k]['AMOUNT'] = $stock['AMOUNT'] >= $minStock ? $stock['AMOUNT'] : 0;
        }

        return $stocks;
    }
}
