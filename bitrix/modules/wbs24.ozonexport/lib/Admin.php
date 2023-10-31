<?php
namespace Wbs24\Ozonexport;

use Bitrix\Main\Localization\Loc;

class Admin
{
    protected $wrappers;
    protected $cachedProperties;

    public function __construct($param = [])
    {
        $objects = $param['objects'] ?? [];
        $this->wrappers = new Wrappers($objects);
    }

    public function loadJs()
    {
        echo '<script src="/bitrix/js/wbs24.ozonexport/admin.js?20220111-2"></script>';
    }

    public function getSelectForPriceTypes($currentPriceId, $labelDefaultPriceType)
    {
        $selectCode =
            '<select name="BLOB[priceType]">'
            .'<option value=""'.($currentPriceId == "" || $currentPriceId == 0 ? ' selected' : '').'>'
                .$labelDefaultPriceType
            .'</option>'
        ;

        $dbRes = $this->wrappers->CCatalogGroup->GetListEx(
            array('SORT' => 'ASC'),
            array(),
            false,
            false,
            array('ID', 'NAME_LANG', 'NAME', 'BASE')
        );
        while ($arRes = $dbRes->Fetch()) {
            $priceNameLang = htmlspecialcharsEx($arRes['NAME_LANG']);
            if ($priceNameLang) $priceNameLang .= ' ';
            $selectCode .=
                '<option value="'.$arRes['ID'].'"'.($currentPriceId == $arRes['ID'] ? ' selected' : '').'>'
                    .'['.$arRes['ID'].'] '.$priceNameLang.'('.htmlspecialcharsEx($arRes['NAME']).')'
                .'</option>'
            ;
        }

        $selectCode .= '</select>';

        return $selectCode;
    }

    public function getSelectForOfferId($iblockId, $field, $currentValue)
    {
        Loc::loadMessages(__FILE__);

        $options = [
            [
                'ID' => '',
                'NAME' => 'ID',
                'CODE' => 'ID',
                'VALUE' => 'ID',
                'IBLOCK_ID' => 'all',
                'FOR_JS' => '',
            ],
            [
                'ID' => '',
                'NAME' => Loc::getMessage("XML_ID_LABEL"),
                'CODE' => 'XML_ID',
                'VALUE' => 'XML_ID',
                'IBLOCK_ID' => 'all',
                'FOR_JS' => '',
            ],
        ];
        $propertiesOptions = $this->getAllStringProperties();
        foreach ($propertiesOptions as $key => $option) {
            $propertiesOptions[$key]['VALUE'] = $option['CODE'];
            $propertiesOptions[$key]['FOR_JS'] = 'data-iblock-id="'.$option['IBLOCK_ID'].'"';
        }
        $options = array_merge($options, $propertiesOptions);

        $selectCode = $this->getSelect($field, $options, $currentValue);

        if ($iblockId) {
            if ($field == 'SET_OFFER_ID') {
                $iblockInfo = $this->getInfoByIblockId($iblockId);
                $iblockId = $iblockInfo['OFFERS_IBLOCK_ID'];
            }
            $jsCode =
                '<script>'
                .'document.addEventListener("DOMContentLoaded", function () {'
                    .'let ozon = new Wbs24Ozonexport();'
                    .'ozon.activateOptionsForCurrentIblock("'.$field.'", '.$iblockId.');'
                .'});'
                .'</script>'
            ;
        }

        return $selectCode.$jsCode;
    }

    public function getCatalogIblockIdsToOffersIblockIds()
    {
        $iblockList = [];

        $res = $this->wrappers->CIBlock->GetList();
        while ($iblock = $res->Fetch()) {
            $info = $this->getInfoByIblockId($iblock['ID']);
            if ($info['CATALOG_TYPE'] == 'X') {
                $iblockList[$iblock['ID']] = $info['OFFERS_IBLOCK_ID'];
            }
        }

        return $iblockList;
    }

    public function getInfoByIblockId($catalogIblockId)
    {
        $iblockInfo = $this->wrappers->CCatalog->GetByIDExt($catalogIblockId);

        return $iblockInfo;
    }

    public function getAllStringProperties()
    {
        if ($this->cachedProperties !== null) return $this->cachedProperties;

        $properties = [];
        $res = $this->wrappers->CIBlockProperty->GetList([], [
            'PROPERTY_TYPE' => 'S',
        ]);
        while ($property = $res->Fetch()) {
            $properties[] = [
                'ID' => $property['ID'],
                'NAME' => $property['NAME'],
                'CODE' => $property['CODE'],
                'IBLOCK_ID' => $property['IBLOCK_ID'],
            ];
        }

        $this->cachedProperties = $properties;

        return $properties;
    }

    public function getSelect($name, $options, $currentValue)
    {
        $code = '<select name="'.$name.'">';
        foreach ($options as $option) {
            $code .=
                '<option '.$option['FOR_JS'].' value="'.$option['VALUE'].'"'
                .($currentValue == $option['VALUE'] ? ' selected' : '')
                .' data-selected="'.($currentValue == $option['VALUE'] ? 'Y' : 'N').'"'
                .'>'.$option['NAME'].'</option>'
            ;
        }
        $code .= '</select>';

        return $code;
    }
}
