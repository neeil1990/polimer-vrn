<?php
namespace Wbs24\Sbermmexport;

class ExtendWarehouse extends Warehouse
{
    use PackagingRatio;

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
        $storeId = $this->param['storeId'] ?? 1;
        $productId = $product['ID'];
        $stocks = $this->getStockInWarehouses($productId);
        $stocks = $this->filterWarehouses($stocks);
        $stocks = $this->verifyAndDropStockIfLess($stocks);

        $fullStock = 0;
        foreach ($stocks as $stock) {
            $fullStock += $stock['AMOUNT'];
        }

        $fullStock = $this->getStockWithPackagingRatio($fullStock);

        $xml = '<outlets>';
        $xml .=
            '<outlet '
                .'id="'.intval($storeId).'" '
                .'instock="'.intval($fullStock).'" '
            .'></outlet>'
        ;
        $xml .= '</outlets>'."\n";

        return $xml;
    }

    public function getStockInWarehouses($productId)
    {
        $rsStoreProduct = $this->wrappers->StoreProductTable->getList([
            'filter' => ['=PRODUCT_ID' => $productId, 'STORE.ACTIVE' => 'Y'],
            'select' => ['AMOUNT', 'STORE_ID', 'STORE_TITLE' => 'STORE.TITLE'],
        ]);

        $stocks = [];
        while ($stock = $rsStoreProduct->Fetch()){
            $warehouseId = $stock['STORE_ID'];
            $stocks[$warehouseId] = $stock;
        }

        $warehouses = $this->getAllWarehouses();
        foreach ($warehouses as $warehouse) {
            $warehouseId = $warehouse['ID'];
            if (!isset($stocks[$warehouseId])) {
                $stocks[$warehouseId] = [
                    'AMOUNT' => 0,
                    'STORE_ID' => $warehouseId,
                ];
            }
        }

        return array_values($stocks);
    }

    public function filterWarehouses($stocks)
    {
        $warehouseFilter = $this->param['extendWarehouseFilter'] ?? false;

        if ($warehouseFilter) {
            $filteredStocks = [];

            foreach ($stocks as $stock) {
                $stockId = $stock['STORE_ID'];
                $active = $this->param['warehouseId'.$stockId.'Active'] ?? false;

                if ($active) {
                    $filteredStocks[] = [
                        'AMOUNT' => $stock['AMOUNT'],
                        'STORE_ID' => $stockId,
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
