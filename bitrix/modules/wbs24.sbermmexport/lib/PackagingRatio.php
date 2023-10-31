<?php
namespace Wbs24\Sbermmexport;

trait PackagingRatio
{
    public function setProperties($props, $productType, $commonFields, $parentProps = [])
    {
        $this->props = $props;
        $this->productType = $productType;
        $this->commonFields = $commonFields;
        $this->parentProps = $parentProps;
    }

    public function getPriceWithPackagingRatio($price)
    {
        $finalPrice = $price;
        $packageRatioPropName =
            ($this->productType == 'simpleProduct')
            ? 'PRODUCT_PACKAGE_RATIO_PROPERTY'
            : 'OFFER_PACKAGE_RATIO_PROPERTY'
        ;
        $packageRatioPropId = (int) $this->commonFields[$packageRatioPropName];

        if ($this->productType == 'offer' && !$packageRatioPropId) {
            $packageRatioPropId = $this->commonFields['PRODUCT_PACKAGE_RATIO_PROPERTY'];
        }

        if ($packageRatioPropId) {

            if ($this->productType == 'offer') {
                if (array_key_exists($packageRatioPropId, $this->props)) {
                    $packagingRatioPropValue = (int) $this->props[$packageRatioPropId]['VALUE'];
                } else {
                    $packagingRatioPropValue = (int) $this->parentProps[$packageRatioPropId]['VALUE'];
                }
            } else {
                $packagingRatioPropValue = (int) $this->props[$packageRatioPropId]['VALUE'];
            }

            if (
                !$packagingRatioPropValue
                || ($packagingRatioPropValue < 0)
            ) {
                $packagingRatioPropValue = 1;
            }

            $finalPrice = $price * $packagingRatioPropValue;
        }

        return $finalPrice;
    }

    public function getStockWithPackagingRatio($stock)
    {
        $stock = (int) $stock;
        $finalStock = $stock;

        $packageRatioPropName =
            ($this->productType == 'simpleProduct')
            ? 'PRODUCT_PACKAGE_RATIO_PROPERTY'
            : 'OFFER_PACKAGE_RATIO_PROPERTY'
        ;
        $packageRatioPropId = (int) $this->commonFields[$packageRatioPropName];

        if ($this->productType == 'offer' && !$packageRatioPropId) {
            $packageRatioPropId = $this->commonFields['PRODUCT_PACKAGE_RATIO_PROPERTY'];
        }

        if ($packageRatioPropId) {

            if ($this->productType == 'offer') {
                if (array_key_exists($packageRatioPropId, $this->props)) {
                    $packagingRatioPropValue = (int) $this->props[$packageRatioPropId]['VALUE'];
                } else {
                    $packagingRatioPropValue = (int) $this->parentProps[$packageRatioPropId]['VALUE'];
                }
            } else {
                $packagingRatioPropValue = (int) $this->props[$packageRatioPropId]['VALUE'];
            }

            if (
                !$packagingRatioPropValue
                || ($packagingRatioPropValue < 0)
            ) {
                $packagingRatioPropValue = 1;
            }

            $finalStock = floor($stock / $packagingRatioPropValue);
        }

        return $finalStock;
    }
}
