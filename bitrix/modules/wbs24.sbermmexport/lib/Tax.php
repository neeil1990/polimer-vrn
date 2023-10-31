<?php
namespace Wbs24\Sbermmexport;

class Tax
{
    protected $param;
    protected $wrappers;

    public function __construct(array $param = [])
    {
        $this->setParam($param);

        $objects = $this->param['objects'] ?? [];
        $this->wrappers = new Wrappers($objects);
    }

    public function setParam(array $param)
    {
        $this->param = $param;
    }

    public function getVat(array $item)
    {
        $vatEnable = $this->param['vatEnable'] ?? false;
        if (!$vatEnable) return false;

        $vat = 'NO_VAT';
        $vatBase = $this->param['vatBase'] ?? false;
        $vatBaseValue = $vatBase ? 'VAT_'.str_replace('%', '', $vatBase) : false;
        $vatList = $this->param['vatList'] ?? [];
        $itemVatId = $item['VAT_ID'] ?: false;

        if ($itemVatId !== false && isset($vatList[$itemVatId])) {
            $vat = $vatList[$itemVatId];
        } elseif ($vatBaseValue && in_array($vatBaseValue, $vatList)) {
            $vat = $vatBaseValue;
        }

        return $vat;
    }
}
