<?php
namespace Wbs24\Ozonexport;

class ExtendPrice extends Price
{
    public function getPrice($minPrice, $fullPrice)
    {
        $ignoreSale = $this->param['ignoreSale'] ?? false;
        $basePrice = $ignoreSale ? $fullPrice : $minPrice;

        $plusPercent = $this->param['plusPercent'] ?? 0;
        $plusAdditionalSum = $this->param['plusAdditionalSum'] ?? 0;

        $price = $basePrice * (1 + ($plusPercent / 100)) + $plusAdditionalSum;

        return round($price);
    }

    public function getOldPrice($minPrice, $fullPrice)
    {
        $oldPricePlusPercent = $this->param['oldPricePlusPercent'] ?? 0;
        $oldPrice10kPlusPercent = $this->param['oldPrice10kPlusPercent'] ?? 0;

        $plusPercent = $oldPricePlusPercent;
        if ($minPrice > 10000) $plusPercent = $oldPrice10kPlusPercent;

        $oldPrice = (100 * $minPrice) / (100 - $plusPercent);

        if ($minPrice > 10000) {
            if ($minPrice + 500 >= $oldPrice) $oldPrice = 0;
        } else {
            if ($minPrice * 1.05 > $oldPrice) $oldPrice = 0;
        }

        return round($oldPrice);
    }

    public function getPremiumPrice($minPrice, $fullPrice)
    {
        $premiumPriceMinusPercent = $this->param['premiumPriceMinusPercent'] ?? 0;
        $premiumPrice = $minPrice * ((100 - $premiumPriceMinusPercent) / 100);

        if ($premiumPrice >= $minPrice) $premiumPrice = 0;

        return round($premiumPrice);
    }

    public function getMinPrice($minPrice, $fullPrice)
    {
        $newMinPriceMinusPercent = $this->param['newMinPriceMinusPercent'] ?? 0;
        $newMinPrice = $minPrice * ((100 - $newMinPriceMinusPercent) / 100);

        if ($newMinPrice >= $minPrice) $newMinPrice = 0;

        return round($newMinPrice);
    }
}
