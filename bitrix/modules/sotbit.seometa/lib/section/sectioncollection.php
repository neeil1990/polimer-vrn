<?php
namespace Sotbit\Seometa\Section;

class SectionCollection {
    private static $instance = false;
    private $cache = [];

    private function __construct() { }

    public static function getInstance() {
        if(self::$instance === false) {
            self::$instance = new SectionCollection();
        }

        return self::$instance;
    }

    public function setCollectionById($sectionsId) {
        $ids = array_diff($sectionsId, array_keys($this->cache));

        $rsSection = $this->getList($ids);

        while($arSection = $rsSection->fetch()) {
            $this->cache[$arSection['ID']] = new Section($arSection);
        }
    }

    public function getCollection($sectionsId) {
        $this->setCollectionById($sectionsId);

        return array_intersect_key($this->cache, array_flip($sectionsId));
    }

    public function getSectionById($sectionId) {
        if(!is_numeric($sectionId))
            return null;
        
        if(!empty($this->cache[$sectionId]))
            return $this->cache[$sectionId];

        $rsSection = $this->getList($sectionId);
        $arSection = $rsSection->fetch();
        if(!is_array($arSection)){
            return null;
        }
        $this->cache[$arSection['ID']] = new Section($arSection);
        
        return $this->cache[$arSection['ID']];
    }

    protected function getList($sectionId) {
        return \Bitrix\Iblock\SectionTable::getList([
            'filter' => [
                'ID' => $sectionId,
                'ACTIVE' => 'Y',
                'GLOBAL_ACTIVE' => 'Y'
            ],
            'select' => [
                'ID',
                'NAME',
                'CODE',
                'XML_ID',
                'IBLOCK_ID'
            ]
        ]);
    }
}