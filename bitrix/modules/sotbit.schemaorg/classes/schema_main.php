<?
use Sotbit\Schemaorg\SchemaPageMetaTable;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

class SchemaMain {
    private $application;
    private static $data;
    private $memory;
    private $siteId;

    public function __construct($siteId = SITE_ID) {
        $this->memory = memory_get_usage();
        $this->siteId = $siteId;
        
        global $APPLICATION;
        $this->application = $APPLICATION;
    }

    public function setData($d){
        foreach ($d as $key => $item) {
            if(isset(self::$data[$item['@type']]))
                unset(self::$data[$item['@type']]);
            self::$data[$item['@type']] = $item;
        }
    }

    public function getData(){
        return self::$data;
    }

    public function unserializeData($jsonD){
        if(isset($jsonD) && !empty($jsonD))
//            foreach ($jsonD as $item) {
                $res[] = unserialize($jsonD);
//            }
//        else
//            return false;
//        foreach ($res as $k => &$re) {
//            if($re['@type'] != $ent)
//                unset($re[$k]);
//        }

        self::setData($res);
    }
}

?>