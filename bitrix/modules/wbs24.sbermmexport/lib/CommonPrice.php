<?php
namespace Wbs24\Sbermmexport;

class CommonPrice extends Price
{
    use PackagingRatio;

    public function getPrice($minPrice, $fullPrice)
    {
        $ignoreSale = $this->param['ignoreSale'] ?? false;

        $price = $ignoreSale ? $fullPrice : $minPrice;
        $this->setPriceDiscount($price);

        return $this->getPriceWithPackagingRatio($price);
    }

    public function setPriceDiscount($priceDiscount)
    {
        $this->priceDiscount = $priceDiscount;
    }

    protected function getPriceDiscount()
    {
        return $this->priceDiscount;
    }

    public function getOldPrice($minPrice, $fullPrice)
    {
        $minPrice = $this->getPriceDiscount();

        $oldPrice = 0;
        if ($minPrice < $fullPrice) {
            $oldPrice = $fullPrice;
        }

        return $this->getPriceWithPackagingRatio($oldPrice);
    }
}
