<?
namespace Sotbit\Seometa;

use Bitrix\Iblock\SectionTable;
use Sotbit\Seometa\Helper\Linker;
use Sotbit\Seometa\Link\TagWriter;
use Sotbit\Seometa\Orm\SeometaUrlTable;

class Tags
{
    public function GenerateTags(
        $Conditions = [],
        $WorkingConditions = [],
        $countTags = 0,
        $countTagsPerCond = 0
    ) {
        $writer = TagWriter::getInstance($WorkingConditions, $countTags);
        if (!$writer->isEmptyData()) {
            return $writer->getData();
        }

        $link = Linker::getInstance();

        foreach ($Conditions as $Condition) {
            $link->Generate($writer, $Condition['ID'], [], $countTagsPerCond);
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
    public function SortTags(
        $Tags = [],
        $Sort = 'NAME',
        $Order = 'asc'
    ) {
        $return = [];
        if (isset($Tags) && is_array($Tags)) {
            switch ($Sort) {
                case 'NAME':
                    $tmpTags = [];
                    foreach ($Tags as $i => $Tag) {
                        $tmpTags[$i] = trim(strtolower($Tag['TITLE']));
                    }

                    if ($Order == 'asc') {
                        asort($tmpTags);
                    } else {
                        arsort($tmpTags);
                    }

                    foreach ($tmpTags as $i => $Name) {
                        $return[] = $Tags[$i];
                    }

                    break;
                case 'RANDOM':
                    shuffle($Tags);
                    $return = $Tags;
                    break;
                case 'CONDITIONS':
                    $return = $Tags;
                    if ($Order == 'asc') {
                        $return = array_reverse($Tags);
                    }

                    break;
                case 'URL_SORT':
                    uasort(
                        $Tags,
                        function ($aItem,$bItem) use ($Order) {
                            $result = 0;
                            if ($aItem['SORT'] != $bItem['SORT']) {
                                if ($Order == 'asc') {
                                    $result = ($aItem['SORT'] <=> $bItem['SORT']);
                                } elseif ($Order == 'desc') {
                                    $result = ($bItem['SORT'] <=> $aItem['SORT']);
                                }
                            }

                            return $result;
                        }
                    );

                    $return = $Tags;
                    break;
                case 'PRODUCT_COUNT':
                    uasort(
                        $Tags,
                        function ($a, $b) use ($Order) {
                            $result = 0;

                            if ($a['PRODUCT_COUNT'] != $b['PRODUCT_COUNT']) {
                                if ($Order == 'asc') {
                                    $result = ($a['PRODUCT_COUNT'] <=> $b['PRODUCT_COUNT']);
                                } elseif ($Order == 'desc') {
                                    $result = ($b['PRODUCT_COUNT'] <=> $a['PRODUCT_COUNT']);
                                }
                            }

                            return $result;
                        }
                    );

                    $return = $Tags;
                    break;
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
    public function ReplaceChpuUrls(
        $Tags = []
    ) {
        $return = [];
        $urls = [];
        foreach ($Tags as $i => $Tag) {
            if ($Tag['URL']) {
                $urls[$i] = $Tag['URL'];
            }
        }

        $rsUrsl = SeometaUrlTable::getList([
            'filter' => [
                'REAL_URL' => $urls,
                'ACTIVE' => 'Y',
                '!NEW_URL' => false
            ],
            'select' => ['NEW_URL', 'REAL_URL', 'SORT']
        ]);
        while ($arUrl = $rsUrsl->fetch()) {
            $key = array_search($arUrl['REAL_URL'],$urls);
            if($Tags[$key]['URL'] && $Tags[$key]['URL'] == $arUrl['REAL_URL'])
            {
                $Tags[$key]['URL'] = $arUrl['NEW_URL'];
                $Tags[$key]['SORT'] = $arUrl['SORT'];
            }
        }

        if ($Tags) {
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
    public function CutTags(
        $Tags = [],
        $Cnt = ''
    ) {
        $return = $Tags;
        if ($Cnt && sizeof($Tags) > $Cnt) {
            $return = array_slice($Tags, 0, $Cnt);
        }

        unset($Tags);
        unset($Cnt);

        return $return;
    }

    public static function findNeedSections(
        $Sections = [],
        $IncludeSubsections = 'Y'
    ) {
        if (!is_array($Sections)) {
            $Sections = [$Sections];
        }

        if ($IncludeSubsections == 'Y' || $IncludeSubsections == 'A') {
            $rsSections = SectionTable::getList([
                'select' => [
                    'LEFT_MARGIN',
                    'RIGHT_MARGIN',
                    'IBLOCK_ID',
                    'DEPTH_LEVEL'
                ],
                'filter' => [
                    'ID' => $Sections
                ]
            ]);
            while ($arParentSection = $rsSections->fetch()) {
                $arFilter = [
                    'IBLOCK_ID' => $arParentSection['IBLOCK_ID'],
                    '>LEFT_MARGIN' => $arParentSection['LEFT_MARGIN'],
                    '<RIGHT_MARGIN' => $arParentSection['RIGHT_MARGIN'],
                    '>DEPTH_LEVEL' => $arParentSection['DEPTH_LEVEL']
                ];
                if ($IncludeSubsections == 'A') {
                    $arFilter['GLOBAL_ACTIVE'] = 'Y';
                }

                $rsChildSections = SectionTable::getList([
                    'select' => [
                        'ID',
                    ],
                    'filter' => $arFilter
                ]);
                while ($arChildSection = $rsChildSections->fetch()) {
                    $Sections[] = $arChildSection['ID'];
                }
            }
        }

        return array_unique($Sections);
    }
}

?>
