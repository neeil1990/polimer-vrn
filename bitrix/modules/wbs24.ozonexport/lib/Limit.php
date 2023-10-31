<?php
namespace Wbs24\Ozonexport;

class Limit
{
    protected $param;

    function __construct($param = [])
    {
        $this->setParam($param);
    }

    public function setParam($param)
    {
        foreach ($param as $name => $value) {
            $this->param[$name] = $value;
        }
    }

    public function verifyElementShowing($element, $prices)
    {
        $limitPriceOn = $this->param['limitPriceOn'] ?? false;
        if (!$limitPriceOn) return true;

        $allowShow = true;
        $ignoreSale = $this->param['ignoreSale'] ?? false;
        $extendPrice = $this->param['extendPrice'] ?? false;
        $limitMinPrice = $this->param['limitMinPrice'] ?: false;
        $limitMaxPrice = $this->param['limitMaxPrice'] ?: false;
        $limitPriceBeforeExtPrice = $this->param['limitPriceBeforeExtPrice'] ?? false;
        $minPrice = $prices['minPrice'] ?? 0;
        $fullPrice = $prices['fullPrice'] ?? 0;
        $price = $prices['price'] ?? 0;

        $verifiedPrice = $price;
        if ($extendPrice && $limitPriceBeforeExtPrice) {
            $commonPrice = new CommonPrice(['ignoreSale' => $ignoreSale]);
            $verifiedPrice = $commonPrice->getPrice($minPrice, $fullPrice);
        }

        if ($limitMinPrice !== false && $verifiedPrice < $limitMinPrice) $allowShow = false;
        if ($limitMaxPrice !== false && $verifiedPrice > $limitMaxPrice) $allowShow = false;

        return $allowShow;
    }
}
