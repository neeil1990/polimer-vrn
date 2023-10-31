<?php
namespace Wbs24\Ozonexport;

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

    public function verifyElementShowing($element)
    {
        $filterOn = $this->param['filterOn'] ?? false;
        if (!$filterOn) return true;

        $filter = $this->param['filter'] ?? false;
        if (!$filter) return true;

        $element['SECTION_ID'] = $element['SECTIONS'];

        if (strpos($filter, 'PROPERTY_') !== false) {
            $element = $this->addPropertiesToElement($element);
        }

        /* $log = date("Y.m.d H:i:s")."\r\n".print_r([$element, $filter], true)."\r\n\r\n";
        if ($log) {
            $handle = @fopen($_SERVER['DOCUMENT_ROOT']."/upload/elements_log.txt", "a");
            fwrite($handle, $log);
            fclose($handle);
        } */

        $allowShow = eval("return ${filter};") ? true : false;

        return $allowShow;
    }

    protected function addPropertiesToElement($element)
    {
        $elementId = $element['ID'] ?? false;
        $iblockId = $element['IBLOCK_ID'] ?? false;
        if (!$elementId || !$iblockId) return $element;

        $result = $this->wrappers->CIBlockElement->GetProperty($iblockId, $elementId);
        while ($property = $result->Fetch()) {
            $element['PROPERTY_'.$property['ID'].'_VALUE'] = [$property['VALUE']];
        }

        return $element;
    }
}
