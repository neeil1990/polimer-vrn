<?php

class SectionDecorator extends IBlockCreatorDecorator
{
    public function __construct(IBlockCreator $creator)
    {
        $this->IBlockComponent = $creator;
    }

    public function create()
    {
        $this->IBlockComponent->create();
        $this->createSection();
    }

    private function createSection()
    {
        $this->IBlockID = $this->IBlockComponent->IBlockID;
        $this->mainIBlock = $this->IBlockComponent->mainIBlock;

        $arSections = [];
        $arTrees = CIBlockMain::getSectionsTree($this->mainIBlock['ID']);
        foreach ($arTrees as $arSection){
            $arSection['IBLOCK_ID'] = $this->IBlockID;
            $arSection['EXTERNAL_ID'] = $arSection['ID'];

            if(!$arSection['IBLOCK_SECTION_ID'])
                $arSection['IBLOCK_SECTION_ID'] = 0;

            $arSections[$arSection['IBLOCK_SECTION_ID']][$arSection['ID']] = $arSection;
        }

        $this->createSectionTree($arSections, 0);
    }

    private function createSectionTree($cats,$parent_id){
        if(is_array($cats) and isset($cats[$parent_id])){

            foreach($cats[$parent_id] as $cat){
                $prefix = "_Add";

                $cat['IBLOCK_SECTION_ID'] = str_replace($prefix, "", $parent_id);
                $bs = new CIBlockSection;
                $ID = $bs->Add($cat);

                $newIndex = $ID . $prefix;

                if(isset($cats[$cat['ID']])){
                    $cats[$newIndex] = $cats[$cat['ID']];
                    unset($cats[$cat['ID']]);
                }

                $this->createSectionTree($cats, $newIndex);
            }
        }
        else return null;
    }

}
