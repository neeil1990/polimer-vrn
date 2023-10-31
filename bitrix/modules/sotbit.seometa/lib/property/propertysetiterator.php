<?
namespace Sotbit\Seometa\Property;

class PropertySetIterator {
    private $propertySet;
    private $propertyCollection;
    private $needCombine = false;
    private $finished = false;
    private $emptyProperties = [];

    public function setProperties(PropertySet $propertySet, PropertyCollection $propertyCollection) {
        $this->propertySet = $propertySet;
        $this->propertyCollection = $propertyCollection;
        $this->needCombine = $propertySet->hasEmptyPropertyValue();
        $this->changePropertyValues();

        $this->finished = false;
        $this->emptyProperties = [];

        if($this->needCombine) {
            $this->emptyProperties = $propertySet->getEmptyProperties();
            $this->setExceptFirstProperty();
        }
    }

    private function changePropertyValues() {
        foreach ($this->propertySet as $propertySetEntity) {
            if(!$propertySetEntity->isEmptyValue()/* && $propertySetEntity->isProperty()*/) {
                $propertySetEntity->exchangeValue(PropertyCollection::getInstance());
            }
        }
    }

    private function setExceptFirstProperty() {
        for($i = 1; $i < count($this->emptyProperties); $i++) {
            if($this->emptyProperties[$i]->isProperty()) {
                $propertyId = $this->emptyProperties[$i]->getPropertyId();
                $property = $this->propertyCollection->getProperty($propertyId);

                if($property->current() === false) {
                    $property->next();
                }

                $this->emptyProperties[$i]->setValue($property->current());
            } elseif ($this->emptyProperties[$i]->isPrice()) {
                $this->emptyProperties[$i]->setValue(123);
            }
        }

        $this->emptyProperties[0]->getProperty()->rewind(false);
    }

    private function setSelectedProperty()
    {
        foreach ($this->propertySet->getData() as $property) {
            if($property->getMetaValue() && $property->getMetaValue() != $property->getProperty()->getCurrentKey()) {
                $property->getProperty()->setCurrentItem($property->getDataField('value'));
                $property->getProperty()->setCurrentKey($property->getMetaValue());
            }
        }
    }

    public function getNext() {
        $this->setSelectedProperty();

        if (!$this->needCombine && !$this->finished) {
            $this->finished = true;
            $this->propertySet->compressEntities();
            return true;
        }

        $this->propertySet->resetValue();

//        $this->setSelectedProperty();
        $result = $this->nextCombination();
//        c ценами надо сделать такое же
        $this->propertySet->compressEntities();

        return $result;
    }

    private function nextCombination($index = 0) {
        if(!isset($this->emptyProperties[$index]))
            return false;

        $propertyId = $this->emptyProperties[$index]->getPropertyId();
        $property = $this->propertyCollection->getProperty($propertyId);

        $property->next();

        if($property->valid()) {
            $this->emptyProperties[$index]->setValue($property->current());
            return true;
        } else {
            $property->rewind();
            $this->emptyProperties[$index]->setValue($property->current());
            return $this->nextCombination(++$index);
        }
    }

    public function rewind() {
        foreach ($this->emptyProperties as $property) {
            $property->getProperty()->setCurrentItem(false);
        }
    }
}

?>