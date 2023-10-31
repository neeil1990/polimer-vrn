<?php
namespace Wbs24\Sbermmexport;

class PropertiesBasedWarehouse extends Warehouse
{
    use PackagingRatio;

    protected $wrappers;
    protected $properties;

    function __construct($param = [])
    {
        parent::__construct($param);

        $objects = $this->param['objects'] ?? [];
        $this->wrappers = new Wrappers($objects);

        $this->properties = false;
    }

    public function checkNeedProperties()
    {
        return true;
    }

    public function getXml($product)
    {
        $storeId = $this->param['storeId'] ?? 1;

        $stocks = $this->getStockFromProperties($product);
        $stocks = $this->verifyAndDropStockIfLess($stocks);

        $fullStock = 0;
        foreach ($stocks as $stock) {
            $fullStock += $stock['AMOUNT'];
        }

        $fullStock = $this->getStockWithPackagingRatio($fullStock);

        $xml = '<outlets>';
        $xml .=
            '<outlet '
                .'id="'.$storeId.'" '
                .'instock="'.$fullStock.'" '
            .'></outlet>'
        ;
        $xml .= '</outlets>'."\n";

        return $xml;
    }

    protected function getStockFromProperties($product)
    {
        $stocks = [];

        for ($i = 1; $i <= 5; $i++) {
            $propertyId = $this->param['stocksProp'.$i] ?? false;
            if (!$propertyId) continue;

            $amount = $product['PROPERTIES'][$propertyId]['VALUE'] ?? false;
            if (!$amount) continue;

            $stocks[] = [
                'AMOUNT' => intval($amount),
            ];
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
