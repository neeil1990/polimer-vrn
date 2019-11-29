<?
namespace Sotbit\Seometa;

class Tags
{
    public function GenerateTags($Conditions = array(), $WorkingConditions = array())
    {
        $writer = \Sotbit\Seometa\Link\TagWriter::getInstance($WorkingConditions);
        if(!$writer->isEmptyData())
            return $writer->getData();
        $link = \Sotbit\Seometa\Helper\Link::getInstance();

        foreach($Conditions as $Condition)
        {
            $link->Generate($Condition['ID'], $writer);
        }

        return $writer->getData();
    }

    /**
     * sort tags with need sort
     *
     * @param array $Tags
     * @param string $Sort
     * @param string $Order
     * @return array
     */
    public function SortTags($Tags = array(), $Sort = 'NAME', $Order = 'asc')
    {
        $return = array();

        if(isset($Tags) && is_array($Tags))
        {
            if($Sort == 'NAME')
            {
                $tmpTags = array();
                foreach($Tags as $i => $Tag)
                {
                    $tmpTags[$i] = $Tag['TITLE'];
                }
                if($Order == 'asc')
                {
                    asort($tmpTags);
                }
                else
                {
                    arsort($tmpTags);
                }
                foreach($tmpTags as $i => $Name)
                {
                    $return[] = $Tags[$i];
                }
            }
            elseif($Sort == 'RANDOM')
            {
                shuffle($Tags);
                $return = $Tags;
            }
            elseif($Sort == 'CONDITIONS')
            {
                if($Order == 'asc')
                {
                    $return = $Tags;
                }
                else
                {
                    $return = array_reverse($Tags);
                }
            }
            unset($Order);
            unset($Sort);
            unset($tmpTags);
            unset($i);
            unset($Name);
        }

        return $return;
    }

    /**
     * replace real url with chpu in tags
     * @param array $Tags
     * @return array
     */
    public function ReplaceChpuUrls($Tags = array())
    {
        $return = array();
        $urls = array();
        foreach($Tags as $i => $Tag)
        {
            if($Tag['URL'])
            {
                $urls[$i] = $Tag['URL'];
            }
        }
        $rsUrsl = \Sotbit\Seometa\SeometaUrlTable::getList(array(
            'filter' => array(
                'REAL_URL' => $urls,
                'ACTIVE' => 'Y',
                '!NEW_URL' => false
            ),
            'select' => array('NEW_URL','REAL_URL')
        ));
        while($arUrl = $rsUrsl->fetch())
        {
            $key = array_search($arUrl['REAL_URL'],$urls);
            if($Tags[$key]['URL'] && $Tags[$key]['URL'] == $arUrl['REAL_URL'])
            {
                $Tags[$key]['URL'] = $arUrl['NEW_URL'];
            }
        }
        if($Tags)
        {
            $return = $Tags;
        }
        unset($Tags);
        unset($Tag);
        unset($key);
        unset($arUrl);
        unset($urls);
        unset($i);

        return $return;
    }

    /**
     * set cnt tags to need
     * @param array $Tags
     * @param string $Cnt
     * @return array
     */
    public function CutTags($Tags = array(), $Cnt = '')
    {
        $return = array();
        if($Cnt && sizeof($Tags) > $Cnt)
        {
            $return = array_slice($Tags, 0, $Cnt);
        }
        else
        {
            $return = $Tags;
        }
        unset($Tags);
        unset($Cnt);
        return $return;
    }

    public static function findNeedSections($Sections = array(), $IncludeSubsections = 'Y')
    {
        if(!is_array($Sections))
        {
            $Sections = array(
                $Sections
            );
        }
        if($IncludeSubsections == 'Y' || $IncludeSubsections == 'A')
        {
            $rsSections = \Bitrix\Iblock\SectionTable::getList(array(
                'select' => array(
                    'LEFT_MARGIN',
                    'RIGHT_MARGIN',
                    'IBLOCK_ID',
                    'DEPTH_LEVEL'
                ),
                'filter' => array(
                    'ID' => $Sections
                )
            ));
            while($arParentSection = $rsSections->fetch())
            {
                $arFilter = array(
                    'IBLOCK_ID' => $arParentSection['IBLOCK_ID'],
                    '>LEFT_MARGIN' => $arParentSection['LEFT_MARGIN'],
                    '<RIGHT_MARGIN' => $arParentSection['RIGHT_MARGIN'],
                    '>DEPTH_LEVEL' => $arParentSection['DEPTH_LEVEL']
                );
                if($IncludeSubsections == 'A')
                {
                    $arFilter['GLOBAL_ACTIVE'] = 'Y';
                }
                $rsChildSections = \Bitrix\Iblock\SectionTable::getList(array(
                    'select' => array(
                        'ID',
                    ),
                    'filter' => $arFilter
                ));
                while($arChildSection = $rsChildSections->fetch())
                {
                    $Sections[] = $arChildSection['ID'];
                }
            }
        }
        $Sections = array_unique($Sections);
        return $Sections;
    }
}

?>