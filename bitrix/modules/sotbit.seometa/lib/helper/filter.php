<?php
namespace Sotbit\Seometa\Helper;
use Sotbit\Seometa\Orm\ConditionTable;
use Sotbit\Seometa\SeoMeta;
use Bitrix\Main\Loader;

class Filter
{
    private $data = array();
    private $offerData = array();
    private $isOfferPropertyCode = false;

    public function GetFields($offer = false)
    {
        return ($offer) ? $this->offerData : $this->data;
    }

    public function HasOfferProperty()
    {
        return $this->isOfferPropertyCode;
    }

    public function AddItem($arParams = array())
    {
        foreach($arParams as $key => $value)
            if(!isset($this->data[$key]))
                $this->data[$key] = $value;
            else
                $this->data[$key] = array_merge($this->toArray($this->data[$key]), $this->toArray($value));
    }

    public function SetItem($arParams = array(), $key = false)
    {
        if($key === false)
            $this->data = $arParams;
        else
            $this->data[$key] = $arParams;
    }

    private function toArray($val)
    {
        return !is_array($val) ? array($val) : $val;
    }

    public function AddItemByProperty($Condition, $cond_properties, $prop_string_values)
    {
        if(!is_array($cond_properties))
            return;

        foreach($cond_properties as $code => $vals)
        {
            if($code == 'PRICE')
            {
                foreach($vals as $price_code => $price)
                {
                    if(isset($price['FROM']) && $price['FROM'] !== '')
                        $this->data['>=CATALOG_PRICE_' . $price_code] = $price['FROM'];
                    if(isset($price['TO']) && $price['TO'] !== '')
                        $this->data['<=CATALOG_PRICE_' . $price_code] = $price['TO'];
                }
            }
            elseif($code == 'FILTER')
            {
                foreach( $vals as $filter_code => $filter )
                {
                    if(isset( $filter['FROM'] ) && $filter['FROM'] !== '')
                    {
                        $this->data['>=PROPERTY_' . $filter_code . '_VALUE'] = $filter['FROM'];
                    }
                    if(isset( $filter['TO'] ) && $filter['TO'] !== '')
                    {
                        $this->data['<=PROPERTY_' . $filter_code . '_VALUE'] = $filter['TO'];
                    }
                }
            }
            else
            {
                $pr = $this->getProperty($Condition['INFOBLOCK'], $code);
                if(class_exists('CCatalogSku')) {
                    if (empty($pr)) {
                        $sku = \CCatalogSKU::GetInfoByProductIBlock($Condition['INFOBLOCK']);

                        if (is_array($sku)) {
                            $idProducts = array();
                            $rsProducts = \CIBlockElement::GetList(array(),
                                $this->data,
                                false,
                                false,
                                array('ID'));
                            while ($arProduct = $rsProducts->fetch()) {
                                array_push($idProducts,
                                    $arProduct['ID']);
                            }

                            $arOffers = \CCatalogSKU::getOffersList($idProducts);

                            $idOffers = array();
                            if (is_array($arOffers)) {
                                foreach ($arOffers as $idProduct => $offers) {
                                    $idOffers = array_merge($idOffers,
                                        array_keys($offers));
                                }
                            }

                            $this->offerData['IBLOCK_ID'] = $sku['IBLOCK_ID'];
                            $this->offerData['ID'] = $idOffers;
                            $this->offerData['ACTIVE'] = 'Y';

                            $pr = $this->getProperty($sku['IBLOCK_ID'],
                                $code);

                            $this->offerData = array_merge($this->offerData,
                                $this->ItemByPropertyType($pr,
                                    $prop_string_values,
                                    $vals));
                            $this->isOfferPropertyCode = true;
                        } else {
                            $sku = \CCatalogSKU::GetInfoByOfferIBlock($Condition['INFOBLOCK']);
                            $infoBlock = $sku['PRODUCT_IBLOCK_ID'];
                            $pr = $this->getProperty($infoBlock,
                                $code);
                        }
                    }
                }
                if(!empty($pr))
                {
                    $this->AddItemByPropertyType($pr, $prop_string_values, $vals);
                }
            }
        }
    }

    private function getProperty($Iblock, $propertyCode)
    {
        $propertyKey = (intval($propertyCode)) ? 'ID' : 'CODE';
        $arFilter = array('IBLOCK_ID' => $Iblock);
        $arFilter[$propertyKey] = $propertyCode;

        return \CIBlockProperty::GetList(array(), $arFilter)->fetch();
    }

    private function AddItemByPropertyType($pr, $prop_string_values, $vals)
    {
        $this->AddItem($this->ItemByPropertyType($pr, $prop_string_values, $vals));
    }

    protected function ItemByPropertyType($pr, $prop_string_values, $vals)
    {
        if(!isset($pr['PROPERTY_TYPE']) || empty($pr['PROPERTY_TYPE']))
            return array();

        if($pr['PROPERTY_TYPE'] == 'S' && $pr['USER_TYPE'] != 'directory')
        {
            return array('PROPERTY_' . $pr['ID'] => $prop_string_values[$pr['ID']] ? current($prop_string_values[$pr['ID']]) : $vals);
        }
        elseif($pr['PROPERTY_TYPE'] == 'S' && $pr['USER_TYPE'] == 'directory')
        {
            return array('PROPERTY_' . $pr['ID'] => $vals);
        }
        elseif($pr['PROPERTY_TYPE'] == 'L' || $pr['PROPERTY_TYPE'] == 'E')
        {
            return array('PROPERTY_' . $pr['ID'] => $prop_string_values[$pr['ID']] ?: $vals);
        }
        else
        {
            return array('PROPERTY_' . $pr['ID'] . '_VALUE' => $vals);
        }

        return array();
    }

    public function ProductCount($writerType = false)
    {
        $count = 0;
        if($this->HasOfferProperty())
        {
            $rsProducts = \CIBlockElement::GetList(array(), $this->GetFields(), false, false, array('ID'));
            $iblockProductIds = array();
            while($arProduct = $rsProducts->fetch())
                array_push($iblockProductIds, $arProduct['ID']);

            if(!empty($iblockProductIds))
            {
                $rsProducts = \CIBlockElement::GetList(array(), $this->GetFields($this->HasOfferProperty()), false, false, array('ID'));

                $newIdOffers = array();
                while($arProduct = $rsProducts->Fetch())
                {
                    array_push($newIdOffers, $arProduct['ID']);
                }

                $idProducts = array();
                if(class_exists('CCatalogSku')) {
                    $arProducts = \CCatalogSKU::getProductList($newIdOffers,
                        0);

                    if (is_array($arProducts)) {
                        foreach ($arProducts as $arProduct) {
                            $idProducts[$arProduct['ID']] = $arProduct['ID'];
                        }
                    }
                }
                $count = count(array_intersect($iblockProductIds, $idProducts));
                unset($rsProducts, $newIdOffers, $arProducts, $idProducts);
            }
            else
                $count = 0;
        }
        else
        {
            if(mb_strpos($writerType, "TagWriter") !== false)
            {
                $count = \CIBlockElement::GetList(array(), $this->GetFields(), false, array("nTopCount" => 1), array('ID'))->SelectedRowsCount();
            }
            else
            {
                $count = \CIBlockElement::GetList(array(), $this->GetFields(), false, false, array('ID'))->SelectedRowsCount();
            }
        }

        return $count;
    }
}
?>