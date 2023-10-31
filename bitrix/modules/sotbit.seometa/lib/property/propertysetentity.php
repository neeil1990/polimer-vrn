<?
namespace Sotbit\Seometa\Property;

use Sotbit\Seometa\Price\PriceManager;

class PropertySetEntity
{
    private $data;
    private $meta = [];
    private $property = false;
    private $price = false;
    private $isCompressed = false;

    public function __construct(
        array &$dataEntity
    ) {
        $this->data = $dataEntity;

        if (
            mb_stripos($dataEntity['CLASS_ID'], 'CondIBProp') !== false
            || mb_stripos($dataEntity['CLASS_ID'], 'property') !== false
        ) {
            $this->constructProperty($dataEntity);
        } elseif (mb_stripos($dataEntity['CLASS_ID'], 'price') !== false) {
            $this->constructPrice($dataEntity);
        } else {
            throw new \Exception("Undefined type property for making [" . print_r($dataEntity,true) . "]");
        }
    }

    private function constructProperty(
        array &$dataEntity
    ) {
        $this->meta = explode(':', $dataEntity['CLASS_ID']);
        if(!empty($dataEntity['DATA']['value'])) {
            $this->meta[3] = $dataEntity['DATA']['value'];
        }

        $this->property = PropertyCollection::getInstance()->getProperty($this->meta[2]);
    }

    private function constructPrice(
        array &$dataEntity
    ) {
        $meta = str_replace('CondIB', '', $dataEntity['CLASS_ID']);
        $this->meta = explode('Price', $meta);
        $this->price = PriceManager::getInstance()->getPriceByCode($this->meta[1]);
    }

    public function getProperty(
    ) {
        return $this->property;
    }

    public function getPrice(
    ) {
        return $this->price;
    }

    public function compare(
        PropertySetEntity $propertySetEntity
    ) {
        $result = 1;
        if ($this->property->SORT == $propertySetEntity->property->SORT) {
            if ($this->property->ID == $propertySetEntity->property->ID) {
                $result = 0;
            } elseif ($this->property->ID > $propertySetEntity->property->ID) {
                $result = -1;
            }
        } elseif ($this->property->SORT > $propertySetEntity->property->SORT) {
            $result = -1;
        }

        return $result;
    }

    public function __get(
        $name
    ) {
        return $this->data[$name] ?? null;
    }

    public function getMetaValue(
    ) {
        return !empty($this->meta[3]) ? $this->meta[3] : null;
    }

    public function setValue(
        $value
    ) {
        $this->data['DATA']['value'] = $value;
    }

    public function isProperty(
    ) {
        return strcmp($this->meta[0], 'CondIBProp') == 0
            || mb_stripos($this->meta[0], 'property') !== false;
    }

    public function isPrice(
    ) {
        return mb_stripos($this->data['CLASS_ID'], 'price') !== false;
    }

    public function isEmptyValue(
    ) {
        if(is_array($this->data['DATA']['value'])) {
            return !empty(array_filter($this->data['DATA']['value'], fn($v) => empty($v)));
        } else if (!empty($this->data['DATA']['value'])) {
            return false;
        }

        return true;
    }

    public function getIblockId(
    ) {
        return $this->meta[1] ?? false;
    }

    public function getPropertyId(
    ) {
        return $this->meta[2] ?? false;
    }

    public function getMeta(
    ) {
        return $this->meta ?: false;
    }

    public function remove(
    ) {
        unset($this->data, $this->meta);
    }

    public function show(
    ) {
        if($this->isPrice()) {
            echo $this->data['CLASS_ID'].' - '.$this->data['DATA']['value'].';';
        } else {
            $value = [];
            foreach($this->data['DATA']['value'] as $propertyValue) {
                $value[] = $propertyValue->VALUE;
            }

            echo $this->data['CLASS_ID'] . ' - ' . implode(' | ', $value) . ';';
        }
    }

    public function compareValue(
        PropertySetEntity $setEntity
    ) {
        return $this->data == $setEntity->data;
    }

    public function exchangeValue(
        PropertyCollection $propertyCollection
    ) {
        if ($this->isProperty()) {
            $this->exchangePropertyValue($propertyCollection);
        } elseif ($this->isPrice()) {
            $this->exchangePriceValue();
        } else {
            throw new \Exception('Unknown type property set entity');
        }
    }

    private function exchangePriceValue(
    ) {
        $this->data['DATA'][mb_strtoupper($this->meta[0])] = $this->data['DATA']['value'];
    }

    private function exchangePropertyValue(
        PropertyCollection $propertyCollection
    ) {
        $propertyId = $this->getPropertyId();
        $property = $propertyCollection->getProperty($propertyId);
        $propertyValue = $property->getValueBy($this->data['DATA']['value']);
        if(!$propertyValue && $property->getType() == 'N') {
            $htmlKey = PropertyManager::makeHtmlKey($this->data['DATA']['value']);
            $crcKey = PropertyManager::makeCrcKey($htmlKey);
            $value = number_format($this->data['DATA']['value'], 4, '.', '');
            $filterPropertyID = '_'.$propertyId;
            $filterPropertyIDKey = $filterPropertyID.'_'.$crcKey;
            $ar = [
                "CONTROL_ID" => $filterPropertyID,
                "CONTROL_NAME" => $filterPropertyIDKey,
                "CONTROL_NAME_ALT" => $filterPropertyID,
                "HTML_VALUE_ALT" => $crcKey,
                "HTML_VALUE" => "Y",
                "VALUE" => $value,
                "SORT" => 0,
                "UPPER" => ToUpper($value),
                "FLAG" => null,
                "URL_ID" => $value,
            ];
            $newProp = new PropertyValue($ar);
            $propertyValue = $newProp;
        }

        $this->data['DATA']['value'] = $propertyValue;
    }

    public function getEntityType(
    ) {
        $result = 'PROPERTY';
        if (mb_strpos($this->data['CLASS_ID'], 'FilterProperty') !== false) {
            $result = 'FILTER';
        } elseif (mb_strpos($this->data['CLASS_ID'], 'Price') !== false) {
            $result = 'PRICE';
        }

        return $result;
    }

    public function getField(
        $key
    ) {
        $result = false;
        if ($this->isProperty()) {
            $result = $key != 'VALUE' && ($key == 'CODE' || $key == 'NAME')
                ? mb_strtolower($this->property->$key)
                : $this->getValues($key);
        } elseif ($this->isPrice()) {
            $result = $this->price[$key] ?? false;
        }

        return $result;
    }

    public function getData(
    ) {
        return $this->data ?? false;
    }

    public function getDataField(
        $key
    ) {
        return $this->data['DATA'][$key] ?? false;
    }

    private function getValues(
        $key
    ) {
        $result = [];
        if ($this->isProperty()) {
            foreach ($this->data['DATA']['value'] as $propertyValue) {
                $result[] = $propertyValue->$key;
            }
        } elseif ($this->isPrice()) {
            $result = $this->data['DATA']['value'];
        }

        return $result;
    }

    public function setCompress(
        $compressValue
    ) {
        $this->isCompressed = $compressValue;
    }

    public function isCompressed(
    ) {
        return $this->isCompressed;
    }

    public function mergeValue(
        PropertySetEntity $propertySetEntity
    ) {
        if ($this->isProperty()) {
            $this->mergePropertyValue($propertySetEntity);
        } elseif ($this->isPrice()) {
            $this->mergePriceValue($propertySetEntity);
        }
    }

    public function resetValue() {
        if (
            is_array($this->data['DATA']['value'])
            && count($this->data['DATA']['value']) < 2
        ) {
            $this->data['DATA']['value'] = $this->data['DATA']['value'][0];
        }
    }

    public function wrapValue(
    ) {
        if (!is_array($this->data['DATA']['value'])) {
            $this->data['DATA']['value'] = [$this->data['DATA']['value']];
        }
    }

    public function compareProperty(
        PropertySetEntity $propertySetEntity
    ) {
        return strcmp($this->data['CLASS_ID'], $propertySetEntity->data['CLASS_ID']) == 0
            || strcmp($this->meta[2], $propertySetEntity->meta[2]) == 0;
    }

    public function comparePrice(
        PropertySetEntity $propertySetEntity
    ) {
        return strcmp($this->meta[1], $propertySetEntity->meta[1]) == 0;
    }

    private function mergePropertyValue(
        PropertySetEntity $propertySetEntity
    ) {
        if (!is_array($this->data['DATA']['value'])) {
            $this->data['DATA']['value'] = [$this->data['DATA']['value']];
        }

        $this->data['DATA']['value'][] = $propertySetEntity->data['DATA']['value'];
    }

    private function mergePriceValue(
        PropertySetEntity $propertySetEntity
    ) {
        $tmp['value'] = array_merge([$this->data['DATA']], [$propertySetEntity->data['DATA']]);
        $this->data['DATA'] = $tmp;
    }

    public function getFilterItem(
    ) {
        $result = '';
        if ($this->isProperty()) {
            $result = ['PROPERTY_' . $this->property->ID => $this->data['DATA']['value'][0]->URL_ID];
        } elseif ($this->isPrice()) {
            $result = ['catalog_PRICE_' . $this->price['ID'] => $this->data['DATA']['value']];
        }

        return $result;
    }

    public function getConditionArrayItem(
    ) {
        if ($this->isPrice()) {
            $value = $this->data['DATA']['value'];
        } else {
            foreach ($this->data['DATA']['value'] as $item) {
                if ($item) {
                    $value[] = $item->getValueByType($this->property->getType());
                }
            }
        }

        return [
            'CLASS_ID' => $this->data['CLASS_ID'],
            'DATA' => [
                'logic' => $this->data['DATA']['logic'],
                'value' => $value
            ]
        ];
    }
}

