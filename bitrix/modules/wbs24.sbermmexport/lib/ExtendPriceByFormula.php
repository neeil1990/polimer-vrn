<?php
namespace Wbs24\Sbermmexport;

class ExtendPriceByFormula extends Price
{
    use PackagingRatio;

    protected $Formula;
    protected $currentBasePrice = 0;

    public function __construct($param = [], $objects = [])
    {
        parent::__construct($param);

        $this->Formula = $objects['Formula'] ?? new Formula();
    }

    public function getPrice($minPrice, $fullPrice)
    {
        $ignoreSale = $this->param['ignoreSale'] ?? false;
        $basePrice = $ignoreSale ? $fullPrice : $minPrice;
        $formula = $this->param['formulaPrice'] ?: false;

        $price = $this->calcByFormula($formula, [
            'PRICE' => $basePrice,
        ]);

        // сохранение текущей базовой цены для расчета в других формулах для данного товара
        // (необходимо т.к. через параметры базовая цена в другие формулы не передается)
        $this->setCurrentBasePrice($basePrice);
        $this->setPriceDiscount(round($price));

        return $this->getPriceWithPackagingRatio(round($price));
    }

    protected function setCurrentBasePrice($basePrice)
    {
        $this->currentBasePrice = $basePrice;
    }

    protected function getCurrentBasePrice()
    {
        return $this->currentBasePrice;
    }

    protected function setPriceDiscount($priceDiscount)
    {
        $this->priceDiscount = $priceDiscount;
    }

    protected function getPriceDiscount()
    {
        return $this->priceDiscount;
    }

    public function getOldPrice($minPrice, $fullPrice)
    {
        $formula = $this->param['formulaOldPrice'] ?? '';
        $minPrice = $this->getPriceDiscount();

        $oldPrice = $this->calcByFormula($formula, [
            'PRICE_DISCOUNT' => $minPrice,
            'PRICE' => $this->getCurrentBasePrice(),
        ]);

        if ($minPrice >= $oldPrice) $oldPrice = 0;

        return $this->getPriceWithPackagingRatio(round($oldPrice));
    }

    protected function calcByFormula($formula, $marks)
    {
        if ($formula) {
            $allowedMarks = array_keys($marks);
            $this->Formula->setMarks($allowedMarks);
            $this->Formula->setFormula($formula);
            $price = $this->Formula->calc($marks);
        } else {
            $price = $marks['PRICE'] ?? 0;
        }

        return $price;
    }
}
