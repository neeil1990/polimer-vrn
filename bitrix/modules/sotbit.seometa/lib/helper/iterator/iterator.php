<?php
namespace Sotbit\Seometa\Helper\Iterator;

class Iterator implements \Iterator {
    protected $data = [];
    protected $currentItem = false;
    protected $currentKey = false;

    public function current() {
        return $this->currentItem;
    }

    public function next() {
        $key = $this->getDateKeyIterator();

        if($key)
            $data = $this->legacy_each($this->data[$key]);
        else
            $data = $this->legacy_each($this->data);

        $this->currentItem = $data['value'];
        $this->currentKey = $data['key'];
    }

    public  function legacy_each(&$array){
        $key = key($array);
        $value = current($array);
        $each = is_null($key) ? false : [
            1        => $value,
            'value'    => $value,
            0        => $key,
            'key'    => $key,
        ];
        next($array);
        return $each;
    }

    public function key() {
        return $this->currentKey;
    }

    public function valid() {
        return $this->currentItem !== null;
    }

    public function rewind($next = true) {
        $key = $this->getDateKeyIterator();

        if($key) {
            reset($this->data[$key]);
            $data = current($this->data[$key]);
        } else {
            reset($this->data);
            $data = current($this->data);
        }

        if($next) {
            $this->next();
        } else {
            if(is_array($data)) {
                $this->currentItem = $data['value'];
                $this->currentKey = $data['key'];
            } else {
                $this->currentItem = $data;
                $this->currentKey = $data->ID;
            }
        }
    }

    protected function getDateKeyIterator() {
        return false;
    }

    public function & getData() {
        return $this->data;
    }

    public function setData(&$data) {
        $this->data = $data;
    }
}