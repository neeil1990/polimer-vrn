<?php
namespace Wbs24\Sbermmexport;

class Filter
{
    protected $param;
    protected $wrappers;

    function __construct($param = [])
    {
        $this->setParam($param);

        $objects = $this->param['objects'] ?? [];
        $this->wrappers = new Wrappers($objects);
    }

    public function setParam($param)
    {
        foreach ($param as $name => $value) {
            if ($name == 'conditions') {
                $filter = $this->getFilterFromConditions($value);
                $this->param['filter'] = $filter;
            }
            $this->param[$name] = $value;
        }
    }

    protected function getFilterFromConditions($encodedConditions)
    {
        $conditions = unserialize(base64_decode($encodedConditions));

        $conditionTreeObject = new \CCatalogCondTree();
        $success = $conditionTreeObject->Init(BT_COND_MODE_GENERATE, BT_COND_BUILD_CATALOG);
        $filter = '';
        if ($success) {
            $filter = $conditionTreeObject->Generate($conditions, array("FIELD" => '$element'));
        }

        return $filter;
    }

    public function verifyElementShowing($element, $parent = [])
    {
        $filterOn = $this->param['filterOn'] ?? false;
        if (!$filterOn) return true;

        $filter = $this->param['filter'] ?? false;
        if (!$filter) return true;

        $element['SECTION_ID'] = $element['SECTIONS'];

        $propertyIds = $this->getNeededPropertyIds($filter);
        if ($propertyIds) {
            $element = $this->addPropertiesToElement($element, $propertyIds);
        }

        if ($parent && !$this->isAllPropertiesReady($element, $propertyIds)) {
            $parent = $this->addPropertiesToElement($parent, $propertyIds);
            $element = $this->mergeProperties($element, $parent);
        }
        $element = $this->addEmptyPropertiesToElement($element, $propertyIds);

        /* $log = date("Y.m.d H:i:s")."\r\n".print_r([$element, $filter], true)."\r\n\r\n";
        if ($log) {
            $handle = @fopen($_SERVER['DOCUMENT_ROOT']."/upload/elements_log.txt", "a");
            fwrite($handle, $log);
            fclose($handle);
        } */

        $allowShow = eval("return ${filter};") ? true : false;

        return $allowShow;
    }

    protected function getNeededPropertyIds($filter)
    {
        $propertyIds = [];

        preg_match_all('/PROPERTY_(\d+)_VALUE/', $filter, $match);
        if (!empty($match[1])) {
            foreach ($match[1] as $id) {
                $propertyIds[] = intval($id);
            }
        }

        return array_values(array_unique($propertyIds));
    }

    protected function isAllPropertiesReady($element, $propertyIds)
    {
        $ready = true;

        foreach ($propertyIds as $id) {
            if (!isset($element['PROPERTY_'.$id.'_VALUE'])) $ready = false;
        }

        return $ready;
    }

    protected function mergeProperties($element, $parent)
    {
        foreach ($parent as $key => $value) {
            if (substr($key, 0, 9) == 'PROPERTY_') {
                $element[$key] = $value;
            }
        }

        return $element;
    }

    protected function addPropertiesToElement($element, $propertyIds = [])
    {
        $elementId = $element['ID'] ?? false;
        $iblockId = $element['IBLOCK_ID'] ?? false;
        if (!$elementId || !$iblockId) return $element;

        $result = $this->wrappers->CIBlockElement->GetPropertyValues(
            $iblockId,
            ["ID" => $elementId],
            false,
            ["ID" => $propertyIds]
        );
        if ($properties = $result->Fetch()) {
            foreach ($properties as $id => $values) {
                if (!is_numeric($id)) continue;
                $element['PROPERTY_'.$id.'_VALUE'] = is_array($values) ? $values : [$values];
            }
        }

        return $element;
    }

    protected function addEmptyPropertiesToElement($element, $propertyIds)
    {
        foreach ($propertyIds as $id) {
            if (!isset($element['PROPERTY_'.$id.'_VALUE'])) {
                $element['PROPERTY_'.$id.'_VALUE'] = [];
            }
        }

        return $element;
    }
}
