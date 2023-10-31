<?php

namespace Sotbit\Seometa\Helper;

use Bitrix\Main\Config\Option;

class Settings {
    private static $siteSettings = [];
    private $data = [];

    private function __construct($siteID){
        $this->data = Option::getForModule(\CSeoMeta::MODULE_ID, $siteID);

        $this->setDefaultSettings();
    }

    private function setDefaultSettings() {
        if(!isset($this->data['FILTER_TYPE']))
            $this->data['FILTER_TYPE'] = 'bitrix_chpu';
    }

    public static function getSettingsForSite($siteID) {
       if(empty(self::$siteSettings[$siteID])){
           self::$siteSettings[$siteID] = new Settings($siteID);
       }
       return self::$siteSettings[$siteID];
    }

    public function __get($name) {
        return (isset($this->data[$name])) ? $this->data[$name] : null;
    }
}