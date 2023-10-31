<?php


class CIBlockMain
{
    static public function getIBlockArray($id)
    {
        $arFields = null;

        if($arFields = CIBlock::GetArrayByID($id))
            $arFields["GROUP_ID"] = CIBlock::GetGroupPermissions($id);

        return $arFields;
    }

    static public function getTypes()
    {
        $type = [];

        $IType = CIBlockType::GetList();
        while($arType = $IType->Fetch()){
            if($arIBType = CIBlockType::GetByIDLang($arType["ID"], LANG))
                $type[] = $arIBType;
        }

        return $type;
    }

    static public function getTypeNameByID($id)
    {
        $name = null;

        if($ar = CIBlock::GetByID($id)->GetNext())
            $name = $ar['NAME'];

        return $name;
    }

    static public function getIBlockProperties($id)
    {
        $arProps = [];
        $properties = CIBlockProperty::GetList([], ["IBLOCK_ID" => $id]);
        while ($props = $properties->GetNext())
            $arProps[] = $props;

        return $arProps;
    }

    static public function getSectionsTree($id)
    {
        $arTrees = [];

        $rsSection = CIBlockSection::GetTreeList(['IBLOCK_ID' => $id], []);
        while ($arSection = $rsSection->Fetch())
            $arTrees[] = $arSection;

        return $arTrees;
    }

    static public function getElementsWithProperties($id){
        $arElements = [];

        $elements = CIBlockElement::GetList(Array(), ["IBLOCK_ID" => $id], false, false, []);
        while ($element = $elements->GetNextElement()) {
            $arFields = $element->GetFields();
            $arProps = $element->GetProperties();

            self::ClearResult($arFields);
            self::ClearResult($arProps);

            $arFields['PROPERTY_VALUES'] = $arProps;

            $arElements[] = $arFields;
        }

        return $arElements;
    }

    static public function getElement($id)
    {
        $element = null;

        $el = CIBlockElement::GetByID($id);
        if($ar = $el->GetNext())
            $element = $ar;

        return $element;
    }

    static public function ClearResult(&$arFields)
    {
        foreach ($arFields as $key => $value)
        {
            if (0 === strpos($key,'~'))
            {
                unset($arFields[$key]);
            }
            else
            {
                if (true == is_array($value))
                    self::ClearResult($arFields[$key]);
            }
        }
    }
}
