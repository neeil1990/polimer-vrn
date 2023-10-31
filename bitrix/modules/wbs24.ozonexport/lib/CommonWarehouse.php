<?php
namespace Wbs24\Ozonexport;

class CommonWarehouse extends Warehouse
{
    public function getXml($product)
    {
        $availableQuantity = $product['QUANTITY'] ?? 0;
        $warehouseName = $this->param['warehouseDefaultName'] ?? false;

        $stock = $this->getStock($availableQuantity, $minStock);
        $xml =
            '<outlets>'
                .'<outlet '
                    .'instock="'.$stock.'"'
                    .($warehouseName ? ' warehouse_name="'.$warehouseName.'"' : '')
                .'></outlet>'
            .'</outlets>'."\n"
        ;

        return $xml;
    }

    protected function getStock($availableQuantity)
    {
        $minStock = $this->param['minStock'] ?? 0;

        return $availableQuantity >= $minStock ? $availableQuantity : 0;
    }
}
