<?
namespace Sotbit\Seometa\Condition;

use \Sotbit\Seometa\Orm\ConditionTable;

class Condition {
    private $data = [];
    private $properties = [];

    public function __construct(int $conditionId) {
        $result = ConditionTable::getById($conditionId) -> fetch();

        if(is_array($result)) {
            $this->data = $result;
            $this->data['META'] = unserialize($this->data['META']);
        }
    }

    public function __get($fieldName) {
        return isset($this->data[$fieldName]) ? $this->data[$fieldName] : false;
    }

    public function getIblockId() {
        return $this->INFOBLOCK;
    }

    /**
     * @return array|false
     */
    public function getSites() {
        return unserialize($this->data['SITES']) ?: [];
    }

    public function getSections() {
        return ($this->SECTIONS) ? unserialize($this->SECTIONS) : false;
    }

    public function getRuleProperties() {
        $result = [];

        if($this->RULE && preg_match_all('/CondIBProp:(\d+):(\d+)/', $this->RULE, $match)) {
            array_walk($match[1], function ($value, $key, &$match) use (&$result) {
                $result[$value][] = $match[2][$key];
            }, $match);
        }

        if($this->RULE && preg_match_all('/CondIBM(in|ax)FilterProperty:(\d+):(\d+)/', $this->RULE, $match)) {
            array_walk($match[2], function ($value, $key, &$match) use (&$result) {
                $result[$value][] = $match[3][$key];
            }, $match);
        }

        return $result;
    }

    public function getTagProperties() {
        $result = [];

        if($this->TAG && preg_match_all('/=(ProductProperty|OfferProperty)\s+"(\S+)"\s*}/i', $this->TAG, $match)) {
            array_walk($match[1], function ($value, $key, &$match) use (&$result) {
                $result[$value][] = $match[2][$key];
            }, $match);
        }

        return $result;
    }

    public function getMeta($metaKey) {
        return isset($this->data['META'][$metaKey]) ? $this->data['META'][$metaKey] : null;
    }

    public function hasPrice() {
        return preg_match('/price/i', $this->data['META']['TEMPLATE_NEW_URL'])
            || preg_match('/price/i', $this->data['RULE'])
            || preg_match('/price/i', $this->data['TAG']);
    }
}
