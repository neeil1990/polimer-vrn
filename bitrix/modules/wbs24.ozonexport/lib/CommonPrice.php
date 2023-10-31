<?php
namespace Wbs24\Ozonexport;

class CommonPrice extends Price
{
    public function getPrice($minPrice, $fullPrice)
    {
        $ignoreSale = $this->param['ignoreSale'] ?? false;

        return $ignoreSale ? $fullPrice : $minPrice;
    }

    public function getOldPrice($minPrice, $fullPrice)
    {
        $oldPrice = 0;
        if ($minPrice < $fullPrice
            && $minPrice <= $fullPrice * 0.95
        ) {
            $oldPrice = $fullPrice;
        }

        return $oldPrice;
    }
}