<?php
namespace Wbs24\Sbermmexport;

abstract class Price
{
    protected $param;

    function __construct($param = [])
    {
        $this->setParam($param);
    }

    public function getPriceFromProps(array $product, array $commonFields): array
    {
        $productType = $product['TYPE'] ?: false;
        if (!$productType) return [];

        $productPriceProp = $commonFields['PRODUCT_PRICE_PROPERTY'] ?: false;
        $offerPriceProp = $commonFields['OFFER_PRICE_PROPERTY'] ?: false;

        $pricePropId = ($productType == 4 ? $offerPriceProp : $productPriceProp);
        if (!$pricePropId) return [];

        $priceValue = $product['PROPERTIES'][$pricePropId]['VALUE'] ?: false;
        $priceValue = intval($priceValue);
        if (!$priceValue) return [];

        $priceArray = [
            'RESULT_PRICE' => [
                'DISCOUNT_PRICE' => $priceValue,
                'BASE_PRICE' => $priceValue,
                'CURRENCY' => 'RUB',
            ],
        ];

        return $priceArray;
    }

    public function setParam($param)
    {
        foreach ($param as $name => $value) {
            $this->param[$name] = $value;
        }
    }

    public function getParam()
    {
        return $this->param;
    }

    abstract public function getPrice($minPrice, $basePrice);

    abstract public function getOldPrice($minPrice, $basePrice);
}
