<?

namespace Sotbit\Seometa\Property;

use Bitrix\Main\Config\Option;
use Sotbit\Seometa\Helper\Iterator\Iterator;
use Sotbit\Seometa\Helper\ParserCondition;
use \Bitrix\Main\Localization\Loc;

class PropertySet extends
    Iterator
{
    private $parserCondition = false;

    public function __construct(
    ) {
        $this->parserCondition = new ParserCondition();
    }

    public function add(PropertySetEntity $dataEntity, $condType = 'AND')
    {
        $tmpEntity = $dataEntity;
        foreach ($this->data as $index => $propertySetEntity) {
            if ($propertySetEntity->compare($tmpEntity) < 0) {
                $this->data[$index] = $tmpEntity;
                $tmpEntity = $propertySetEntity;
            } elseif ($propertySetEntity->compare($tmpEntity) === 0 && $condType === 'OR' && $dataEntity->getData()['DATA']['value'] === '') {
                $tmpEntity = false;
                break;
            }
        }
        if ($tmpEntity) {
            $this->data[] = $tmpEntity;
        }
    }

    public function isPropertiesAvailable(
        PropertyCollection $propertyCollection
    ) {
        $result = false;
        foreach ($this->data as $propertySetEntity) {
            if ($propertySetEntity->isProperty()) {
                $result = $propertyCollection->isPropertyAvailable($propertySetEntity->getPropertyId());
                if (!$result) {
                    return false;
                }
            }
        }

        return $result;
    }

    public function isValid(
    ) {
        foreach($this->data as $propertySetEntity) {
            if(
                $propertySetEntity->isEmptyValue()
                || (
                    !empty($propertySetEntity->getMetaValue())
                    && (
                        $propertySetEntity->getProperty()->currentKey != $propertySetEntity->getMetaValue()
                        && $propertySetEntity->getProperty()->PROPERTY_TYPE != 'N'
                        && count($propertySetEntity->DATA['value']) == 1
                    )
                )
            ) {
                return false;
            }
        }

        for ($i = 0; $i < count($this->data) - 1; $i++) {
            for($j = $i + 1; $j < count($this->data); $j++) {
                if($this->data[$i]->compareValue($this->data[$j])) {
                    return false;
                }
            }
        }

        return true;
    }

    public function hasEmptyPropertyValue(
    ) {
        foreach ($this->data as $propertySetEntity) {
            if ($propertySetEntity->isEmptyValue() && $propertySetEntity->isProperty()) {
                return true;
            }
        }

        return false || empty($this->data);
    }

    public function getEmptyProperties(
    ) {
        $result = [];

        foreach($this->data as $propertyEntity) {
            if($propertyEntity->isEmptyValue()) {
                $result[] = $propertyEntity;
            }
        }

        return $result;
    }

    public function get(
        $index
    ) {
        return $this->data[$index];
    }

    public function __clone(
    ) {
        foreach($this->data as $index => $entity) {
            $this->data[$index] = clone $entity;
        }
    }

    public function remove(
    ) {
        foreach($this->data as $propertySet) {
            $propertySet->remove();
        }

        unset($this->data);
    }

    public function show(
    ) {
        echo '[';
        foreach($this->data as $setEntity) {
            $setEntity->show();
        }
        echo ']<br>';
    }

    public function getPropertyNames(
    ) {
        $result = [];
        foreach ($this->data as $propertySetEntity) {
            if($propertySetEntity->isPrice()) {
                $result[] .= $propertySetEntity->getField('TITLE');
                $tmpRes = [];
                if(!is_array($propertySetEntity->getDataField('value'))) {
                    $items = [$propertySetEntity->getData()['DATA']];
                } else {
                    $items = $propertySetEntity->getDataField('value');
                }

                foreach ($items as $item) {
                    if(isset($item['MAX']) || isset($item['MAXFILTER'])) {
                        $tmpRes[1] = Loc::getMessage('PRICE_TO') . $item['value'];
                    } elseif (isset($item['MIN']) || isset($item['MINFILTER'])) {
                        $tmpRes[0] = Loc::getMessage('PRICE_FROM') . $item['value'];
                    }
                }
                ksort($tmpRes);
                $result = array_merge($result, $tmpRes);
            } else {
                $result[] .= $propertySetEntity->getProperty()->NAME;
                if(mb_stripos($propertySetEntity->getMeta()[0], 'filterproperty')) {
                    $arValues = array_map(
                        function ($value) {
                            return (int) $value;
                        },
                        $propertySetEntity->getField('VALUE')
                    );
                    asort($arValues);
                    $result[] = implode('-', $arValues);
                } else {
                    $result[] .= implode(', ', $propertySetEntity->getField('VALUE'));
                }
            }
        }

        return implode(' ', $result);
    }

    public function getPropertyValues() {
        $result = [];
        $arrParams = [
            'MAX',
            'MAXFILTER',
            'MIN',
            'MINFILTER'
        ];

        foreach ($this->data as $propertySetEntity) {
            if($propertySetEntity->isPrice()) {
                $priceCode = $propertySetEntity->getPrice()['CODE'];
                if(!is_array($propertySetEntity->getDataField('value'))) {
                    $items = [$propertySetEntity->getData()['DATA']];
                } else {
                    $items = $propertySetEntity->getDataField('value');
                }

                foreach ($items as $item) {
                    $priceProp = current(array_intersect(array_keys($item), $arrParams));
                    $result[$priceCode][$priceProp] = $item['value'];
                }
                asort($result[$priceCode]);
            } else {
                $value = $propertySetEntity->getField('VALUE');
                if(is_array($value) && $propertySetEntity->getField('TYPE')[0] === 'N') {
                    $value = array_map(function ($val){
                        if(is_numeric($val)){
                            return number_format($val, 0, ".", " ");
                        }
                        return $val;
                    }, $value);
                    asort($value);
                }
                $result[$propertySetEntity->getProperty()->CODE] = $value;
            }
        }

        return $result;
    }

    public function compressEntities(
    ) {
        $reset = false;
        for($i = 0; $i < count($this->data); $i++) {
            if($reset) {
                $i = 0;
                $reset = false;
            }

            if($this->data[$i]->isProperty()) {
                if ($i + 1 >= count($this->data) && $this->data[$i]->isProperty()) {
                    $this->data[$i]->wrapValue();
                }

                for ($j = $i + 1; $j < count($this->data); $j++) {
                    if ($this->data[$j]->isPrice()) {
                        continue;
                    }

                    $this->data[$j]->setCompress(false);
                    if ($this->data[$i]->compareProperty($this->data[$j])) {
                        $this->data[$j]->setCompress(true);
                        $this->data[$i]->mergeValue($this->data[$j]);
                        unset($this->data[$j]);
                        $reset = true;
                        $this->data = array_values($this->data);
                    } else {
                        $this->data[$i]->wrapValue();
                    }
                }
            } else if ($this->data[$i]->isPrice()) {
                for ($j = $i + 1; $j < count($this->data); $j++) {
                    if ($this->data[$j]->isProperty()) {
                        continue;
                    }

                    $this->data[$j]->setCompress(false);
                    if ($this->data[$i]->comparePrice($this->data[$j])) {
                        $this->data[$j]->setCompress(true);
                        $this->data[$i]->mergeValue($this->data[$j]);
                        unset($this->data[$j]);
                        $reset = true;
                        $this->data = array_values($this->data);
                    } else {
                        $this->data[$i]->wrapValue();
                    }
                }
            }
        }
    }

    public function resetValue(
    ) {
        for($i = 0; $i < count($this->data); $i++) {
                $this->data[$i]->resetValue();
        }
    }

    public function getFilter(
    ) {
        $result = [
            'LOGIC' => 'AND',
        ];
        for($i = 0; $i < count($this->data); $i++) {
            $result[] = $this->data[$i]->getFilterItem();
        }

        return $result;
    }

    protected function getConditionArray($sectionId) {
        $return = [
            'CLASS_ID' => 'CondGroup',
            'DATA' => [
                'All' => 'AND',
                'True' => 'True',
            ],
            'CHILDREN' => [
                [
                    'CLASS_ID' => 'CondIBSection',
                    'DATA' => [
                        'logic' => 'Equal',
                        'value' => $sectionId
                    ]
                ]
            ]
        ];
        for ($i = 0; $i < count($this->data); $i++) {
            $return['CHILDREN'][] = $this->data[$i]->getConditionArrayItem();
        }

        return $return;
    }

    public function getCountProducts($sectionId, $siteID, $iblockId) {
        $arFilter =  ['INCLUDE_SUBSECTIONS' => 'Y', 'ACTIVE' => 'Y'];
        if(Option::get('sotbit.seometa', 'PRODUCT_AVAILABLE_FOR_COND', 'N', $siteID) === 'Y'){
            $arFilter['AVAILABLE'] = 'Y';
        }
        $filter = $this->parserCondition->parseCondition($this->getConditionArray($sectionId), $arFilter);
        $filter['IBLOCK_ID'] = $iblockId;
        $priceEx = $filter['PRICE_EXIST'];
        unset($filter['PRICE_EXIST']);
        $rsElements = \CIBlockElement::GetList([], $filter, false, false, ["ID"]);
        while ($elem = $rsElements->Fetch()){
            $count[] = $elem['ID'];
        }
        if($priceEx){
            $filter = $this->parserCondition->parseCondition($this->getConditionArray($sectionId), $arFilter, $iblockId);
            $filter['IBLOCK_ID'] = $iblockId;
            $rsElements = \CIBlockElement::GetList([], $filter, false, false, ["ID"]);
            while ($elem = $rsElements->Fetch()){
                $count[] = $elem['ID'];
            }
        }
        return $count ? count(array_unique($count)) : '0';
    }
}