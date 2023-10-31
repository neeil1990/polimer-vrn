<?
namespace Sotbit\Seometa\Property;

use Sotbit\Seometa\Helper\Iterator\Iterator;


class Property extends Iterator {
    public function __construct(array &$data)    {
        $this->data = $data;
    }

    public function __get($name) {
        return isset($this->data[$name]) ? $this->data[$name] : false;
    }

   public function & getData()
   {
       return parent::getData();
   }

    public function setMinVal($value) {
        $this->data['MIN']['VALUE'] = $value;
    }

    public function setMaxVal($value) {
        $this->data['MAX']['VALUE'] = $value;
    }

    public function clearValue() {
        unset($this->data['VALUES']);
        $this->data['VALUES'] = [];
    }

    public function isEmptyValue() {
        return empty($this->data['VALUES']);
    }

    public function addValue(array $value, $key = false) {
        if($key == false) {
            $this->data['VALUES'][] = $value;
        } else {
            $this->data['VALUES'][$key] = $value;
        }
    }

    public function addValueObj(PropertyValue $propValue, $key = false) {
        if($key == false) {
            $this->data['VALUES'][] = $propValue;
        } else {
            //string property type. you need to do something with the key. Russian key can not be used, there may be problems
            if($this->needChangeKey()) {
                $key = $this->changeKey($key);
            }

            $this->data['VALUES'][$key] = $propValue;
        }
    }

    public function getValueBy($value) {
        if($this->needChangeKey()) {
            $value = $this->changeKey($value);
        }

        return $this->data['VALUES'][$value];
    }

    public function isTypeHighload() {
        return $this->data['PROPERTY_TYPE'] && $this->data['USER_TYPE'] == 'directory';
    }

    protected function getDateKeyIterator() {
        return 'VALUES';
    }

    private function needChangeKey() {
        return ($this->data['PROPERTY_TYPE'] == 'S' && empty($this->data['USER_TYPE']))
            || $this->data['PROPERTY_TYPE'] == 'N';
    }

    private function changeKey($key) {
        $pattern = '/\&(amp|quot|\#039|lt|gt)\;/';
        if($this->data['PROPERTY_TYPE'] == 'N') {
            $key = number_format($key, 4, '.', '');
        }

        if($this->data['PROPERTY_TYPE'] == 'S' && preg_match($pattern, $key) === 0) {
            $key = htmlspecialcharsbx($key);
        }

        return md5($key);
    }

    public function getType() {
        return ($this->isTypeHighload()) ? 'Ux' : $this->data['PROPERTY_TYPE'];
    }

    public function setCurrentItem($currItem) {
        $this->currentItem = $currItem;
    }

    public function getCurrentKey()
    {
        return $this->currentKey;
    }

    public function setCurrentKey($currKey) {
        $this->currentKey = $currKey;
    }

    public function next() {
        parent::next();
    }
}
