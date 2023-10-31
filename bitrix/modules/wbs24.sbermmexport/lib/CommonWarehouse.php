<?php
namespace Wbs24\Sbermmexport;

class CommonWarehouse extends Warehouse
{
    use PackagingRatio;

    public function getXml($product)
    {
        $availableQuantity = $product['QUANTITY'];
        $storeId = $this->param['storeId'] ?? 1;

        $stock = $this->getStock($availableQuantity, $minStock);
        $stock = $this->getStockWithPackagingRatio($stock);
        $xml =
            '<outlets>'
                .'<outlet '
                    .'id="'.intval($storeId).'" '
                    .'instock="'.intval($stock).'"'
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
