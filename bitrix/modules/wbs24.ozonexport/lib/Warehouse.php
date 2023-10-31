<?php
namespace Wbs24\Ozonexport;

abstract class Warehouse
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

    abstract public function getXml($product);
}
