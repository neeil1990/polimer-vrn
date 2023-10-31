<?
namespace Sotbit\Seometa\Generater;

abstract class Common
{
    public static function Create($FilterType)
    {
        switch($FilterType)
        {
            case 'misshop_chpu':
                return new \Sotbit\Seometa\Generater\MissShopChpuGenerator();
            case 'combox_not_chpu':
                return new \Sotbit\Seometa\Generater\ComboxGenerator();
            case 'combox_chpu':
                return new \Sotbit\Seometa\Generater\ComboxChpuGenerator();
            case 'bitrix_chpu':
                return new \Sotbit\Seometa\Generater\BitrixChpuGenerator();
            default:
                return new BitrixGenerator();
        }
    }

    public function Generate($CondKey, $CondValProps)
    {
        switch ($CondKey)
        {
            case 'PRICE':
                return $this->GeneratePriceParams($CondValProps);
            case 'FILTER':
                return $this->GenerateFilterParams($CondValProps);
            default:
                return $this->GenerateParams($CondValProps);
        }
    }

    abstract protected function GeneratePriceParams($CondValProps);
    abstract protected function GenerateFilterParams($CondValProps);
    abstract protected function GenerateParams($CondValProps);
}
?>