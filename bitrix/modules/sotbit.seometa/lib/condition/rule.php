<?
namespace Sotbit\Seometa\Condition;

use Sotbit\Seometa\Property\PropertyCollection;
use Sotbit\Seometa\Property\PropertySet;
use Sotbit\Seometa\Property\PropertySetCollection;
use Sotbit\Seometa\Property\PropertySetEntity;

class Rule
{
    private array $checkedProps = [];
    private string $currentCond = '';

    public function parse(Condition $condition, $siteID)
    {
        $rule = unserialize($condition->RULE);
        $cond = new \Sotbit\Seometa\Helper\Condition();
        $openCond = $cond->openGroups($rule);

        return $this->mapPropertySet($openCond['CHILDREN'], $siteID, $condition->REINDEX, $openCond['DATA']['All']);
    }

    private function checkANDCondition($condition, $siteID)
    {
        return !isset($this->checkedProps[$condition['CLASS_ID'] . ':' . $siteID]);
    }

    private function saveANDCondition($condition, $siteID)
    {
        if ($condition['DATA']['value'] === '') {
            $this->checkedProps[$condition['CLASS_ID'] . ':' . $siteID] = true;
        }
    }

    private function isConditionANDRepeated($condition, $siteID, $key)
    {
        $result = true;
        if($this->checkANDCondition($condition, $siteID)) {
            $this->saveANDCondition($condition, $siteID);
            $result = false;
        }

        return $result;
    }

    private function checkORCondition()
    {
        return !in_array($this->currentCond, $this->checkedProps);
    }

    private function saveORCondition($condition, $siteID, $key)
    {
        if ($condition['DATA']['value'] === '') {
            $this->checkedProps[$key] .= $condition['CLASS_ID'] . ':' . $siteID;
        } else {
            $this->checkedProps[$key] .= $condition['CLASS_ID'] . ':' . $siteID . ':' . $condition['DATA']['value'];
        }
    }

    private function isConditionORRepeated($condition, $siteID, $key) {
        $result = true;
        $this->currentCond .= $condition['CLASS_ID'] . ':' . $siteID;
        if($this->checkORCondition()) {
            $this->saveORCondition($condition, $siteID, $key);
            $result = false;
        }

        return $result;
    }

    private function mapPropertySet(array &$conditions, $siteID, $reindex, $condType) {
        $propertySetCollection = new PropertySetCollection();
        foreach ($conditions as $keyCond => $conditionSet) {
            $propertySet = new PropertySet();
            if (!$conditionSet) {
                return null;
            }

            foreach ($conditionSet as $key => $condition) {
                if ($condType === 'AND') {
                    if ($this->isConditionANDRepeated($condition, $siteID, $keyCond) && $reindex !== true) {
                        if (count($conditionSet) - 1 !== $key) {
                            continue;
                        }

                        continue 2;
                    }
                } else {
                    if ($this->isConditionORRepeated($condition, $siteID, $keyCond) && $reindex !== true) {
                        if (count($conditionSet) - 1 !== $key) {
                            continue;
                        }

                        $this->currentCond = '';
                        continue 2;
                    }
                }


                $propertySetEntity = new PropertySetEntity($condition);
                if(!$propertySetEntity->getProperty() && !$propertySetEntity->getPrice()){
                    continue;
                }
                $propertySet->add($propertySetEntity, $condType);
            }

            if($propertySet->getData()){
                $propertySetCollection->addSet($propertySet);
            }
            $this->currentCond = '';
        }

        return $propertySetCollection;
    }
}