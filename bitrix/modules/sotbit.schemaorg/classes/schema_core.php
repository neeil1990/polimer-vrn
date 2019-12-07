<?

use Sotbit\Schemaorg\SchemaTable;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

class SchemaCore {
    private function filterMetaArray($keyGroup, array &$arMetaTable) {
        $keyGroup = strtoupper($keyGroup);
        $active = unserialize($arMetaTable[$keyGroup.'_PROPS_ACTIVE']);
        if($arMetaTable['ACTIVE_'.$keyGroup] == 'Y')
            return array_filter($arMetaTable, function($value, $key) use ($keyGroup, $active) {
                return strpos($key, $keyGroup) === 0 && strpos($key, 'PROPS_ACTIVE') === false && isset($active[$key]) && $active[$key];
            }, ARRAY_FILTER_USE_BOTH);
        else
            return array();
    }
    
    private function getHost() {
        return $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'];
    }
    
    public function getRulePathExact($url, $siteId = SITE_ID) {
        return SchemaTable::getList(array(
            'filter' => array(
                'NAME' => $url,
                'SITE_ID' => $siteId,
            ),
            'order' => array('SORT' => 'DESC')
        ));
    }
    
    public function getRulePathTemplate($siteId = SITE_ID) {
        return \Sotbit\Schemaorg\SchemaTable::getList(array(
            'filter' => array(
                'LOGIC' => 'AND',
                array(
                    'LOGIC' => 'OR',
                    array('ACTIVE_OG' => 'Y'),
                    array('ACTIVE_TW' => 'Y'),
                ),
                '%NAME' => '*',
                'SITE_ID' => $siteId,
            ),
            'order' => array('SORT' => 'DESC')
        ));
    }
    
    public function getFirstRightRule(\Bitrix\Main\DB\Result $resultList, $url) {
        while($arList = $resultList->fetch()) {
            if($this->checkUrlTemplate($arList['NAME'], $url))
                return $arList;
        }
        
        return array();
    }
    
    public function checkUrlTemplate($template, $url) {
        $template = str_replace(array(
            '*',
            '/'
        ), array(
            '.*',
            "\/"
        ), $template);
        
        return preg_match('/^'.$template.'$/ui', $url);
    }
    
    public function checkUrlTemplateList(array $templateList, $url) {
        foreach($templateList as $template) {
            if($this->checkUrlTemplate($template, $url))
                return true;
        }
        
        return false;
    }
    
    public function getArRightRules(array $resultList, $url) {
        return array_filter($resultList, function($val, $key) use ($url) {
            $name = str_replace(array(
                '*',
                '/'
            ), array(
                '.*',
                "\/"
            ), $val['NAME']);
            
            return preg_match('/^'.$name.'$/ui', $url);
        }, ARRAY_FILTER_USE_BOTH);
    }
}

?>