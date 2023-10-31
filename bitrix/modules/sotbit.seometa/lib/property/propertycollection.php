<?
namespace Sotbit\Seometa\Property;

use Sotbit\Seometa\Helper\Iterator\Iterator;
use Sotbit\Seometa\Price\PriceManager;

class PropertyCollection extends Iterator {
    private static $instance = false;
    private $prices = [];

    private function __construct() { }

    public function getProperty($propertyId) {
        return (isset($this->data[$propertyId])) ? $this->data[$propertyId] : null;
    }

    public function isPropertyAvailable($propertyId) {
        return isset($this->data[$propertyId])/* && !$this->data[$propertyId]->isEmptyValue()*/;
    }

    public static function getInstance() {
        if(self::$instance === false) {
            self::$instance = new PropertyCollection();
        }

        return self::$instance;
    }

    public function clearValue() {
        if(is_array($this->data)) {
            foreach ($this->data as $property) {
                $property->clearValue();
            }
        }
    }

    public function setData(&$properties) {
        unset($this->data);
        foreach ($properties as $PID => $property) {
            $this->data[$PID] = new Property($property);
        }
    }

    public function setPrices(PriceManager $priceManager) {
        $this->prices = $priceManager->getData();
    }

    public function haveEmpty() {
        if(is_array($this->data)) {
            foreach ($this->data as $element) {
                if ($element->VALUES === array()) {
                    return true;
                }
            }
        }

        return false;
    }
}