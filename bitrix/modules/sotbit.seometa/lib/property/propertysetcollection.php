<?

namespace Sotbit\Seometa\Property;

use Sotbit\Seometa\Helper\Iterator\Iterator;

class PropertySetCollection extends
    Iterator
{

    public function addSet(
        PropertySet $propertySet
    ) {
        $this->data[] = $propertySet;
    }

    public function filter(
        PropertyCollection $propertyCollection
    ) {
        $resultCollection = new PropertySetCollection();
        foreach ($this->data as $propertySet) {
            if ($propertySet->isPropertiesAvailable($propertyCollection)) {
                $resultCollection->addSet(clone $propertySet);
            }
        }

        if (!$resultCollection->getData() && !empty($this->data)) {
            $resultCollection->addSet(clone $this->data[0]);
        }

        return $resultCollection;
    }

    public function get(
        $index
    ) {
        return $this->data[$index];
    }

    public function remove(
    ) {
        foreach ($this->data as $propertySet) {
            $propertySet->remove();
        }

        unset($this->data);
    }
}