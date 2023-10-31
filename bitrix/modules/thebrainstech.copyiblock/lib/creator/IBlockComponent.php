<?php

class IBlockComponent extends IBlockCreator
{
    private $type;

    public function __construct($mainIBlockID, $type = null)
    {
        $this->mainIBlock = CIBlockMain::getIBlockArray($mainIBlockID);
        $this->type = $type;
    }

    public function create()
    {
        $this->createIBlock()->createProperties();
    }

    private function createIBlock()
    {
        $arFields = $this->mainIBlock;
        unset($arFields['ID']);
        $arFields["CODE"] = "";
        $arFields["API_CODE"] = "";
        $arFields["IBLOCK_TYPE_ID"] = $this->type ?: $arFields["IBLOCK_TYPE_ID"];

        $iBlock = new CIBlock;
        $this->IBlockID = $iBlock->Add($arFields);

        if(intval($this->IBlockID) <= 0)
            $this->error[] = $iBlock->LAST_ERROR;

        return $this;
    }

    private function createProperties()
    {
        $arProps = CIBlockMain::getIBlockProperties($this->mainIBlock['ID']);
        foreach($arProps as $prop){
            $prop = $this->propertyExtended($prop);
            CIBlockMain::ClearResult($prop);

            unset($prop['ID']);
            $prop["IBLOCK_ID"] = $this->IBlockID;

            $ibp = new CIBlockProperty;
            $PropID = $ibp->Add($prop);

            if(intval($PropID) <= 0)
                $this->error[] = $ibp->LAST_ERROR;
        }
    }

    private function propertyExtended($property)
    {
        if($property['PROPERTY_TYPE'] === "L"){
            $enums = CIBlockPropertyEnum::GetList([], Array("IBLOCK_ID" => $property["IBLOCK_ID"], "CODE" => $property["CODE"]));
            while($enum = $enums->GetNext()){
                $property["VALUES"][$enum['ID']] = $enum;
                $property["VALUES"][$enum['ID']]['XML_ID'] = $enum['ID'];
                $property["VALUES"][$enum['ID']]['EXTERNAL_ID'] = $enum['ID'];
            }
        }

        return $property;
    }
}
