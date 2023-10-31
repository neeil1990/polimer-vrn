<?php
namespace Wbs24\Sbermmexport;

class ExtendPrice extends Price
{
    use PackagingRatio;

    public function getPrice($minPrice, $fullPrice)
    {
        $ignoreSale = $this->param['ignoreSale'] ?? false;
        $basePrice = $ignoreSale ? $fullPrice : $minPrice;

        $plusPercent = $this->param['plusPercent'] ?: 0;
        $plusAdditionalSum = $this->param['plusAdditionalSum'] ?: 0;

        $price = $basePrice * (1 + ($plusPercent / 100)) + $plusAdditionalSum;

        return $this->getPriceWithPackagingRatio(round($price));
    }

    public function getOldPrice($minPrice, $fullPrice)
    {
        $plusPercent = $this->param['oldPricePlusPercent'] ?: 0;
        $oldPrice = (100 * $minPrice) / (100 - $plusPercent);

        if ($minPrice >= $oldPrice) $oldPrice = 0;

        return round($oldPrice);
    }
}
