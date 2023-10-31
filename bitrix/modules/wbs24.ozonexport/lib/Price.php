<?php
namespace Wbs24\Ozonexport;

abstract class Price
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

    public function getParam()
    {
        return $this->param;
    }

    abstract public function getPrice($minPrice, $basePrice);

    abstract public function getOldPrice($minPrice, $basePrice);

    public function getPremiumPrice()
    {
        return false;
    }

    public function getMinPrice()
    {
        return false;
    }
}
